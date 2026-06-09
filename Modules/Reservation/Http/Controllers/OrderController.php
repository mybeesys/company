<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Setting;
use Modules\Product\Models\Category;
use Modules\Product\Models\CustomMenu;
use Modules\Product\Models\CustomMenuItem;
use Modules\Product\Models\CustomMenuTime;
use Modules\Product\Models\CustomMenuTimeDetail;
use Modules\Reservation\Models\MenuFeedback;
use Modules\Reservation\Models\MenuToken;
use Modules\Reservation\Models\Order;
use Modules\Reservation\Models\OrderItem;
use Modules\Reservation\Support\MenuAllergenDefinitions;
use Throwable;

class OrderController extends Controller
{
    private function parseAllergenVisibleKeysFromRequest(Request $request): ?array
    {
        $raw = $request->input('allergen_visible_keys');
        if ($raw === null || $raw === '') {
            return null;
        }
        $arr = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($arr)) {
            return null;
        }

        return MenuAllergenDefinitions::normalizeStoredKeys($arr);
    }

    /**
     * @return array<int|string, array{map_lat?: mixed, map_lng?: mixed, map_label?: mixed}>
     */
    private function parseEstLocationsFromRequest(Request $request): array
    {
        $raw = $request->input('est_locations');
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function validateEstLocations(array $estIds, array $estLocations): ?string
    {
        foreach ($estIds as $estId) {
            $estId = (int) $estId;
            $row = $estLocations[(string) $estId] ?? $estLocations[$estId] ?? null;
            $lat = is_array($row) ? ($row['map_lat'] ?? null) : null;
            $lng = is_array($row) ? ($row['map_lng'] ?? null) : null;
            if ($lat === null || $lat === '' || $lng === null || $lng === '') {
                return "Missing location for establishment #{$estId}";
            }
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return "Invalid location coordinates for establishment #{$estId}";
            }
        }

        return null;
    }

    /**
     * Display a listing of the resource.
     */
    public function menu($id)
    {
        $tenant = tenancy()->tenant;
        $tenantId = $tenant->id;
        $table = ['tenant' => $tenantId, 'code' => $id];

        return view('reservation::order.menu', compact('table'));
    }

    /**
     * توليد التوكن بناءً على الأفرع والمنتجات المختارة
     */
    public function generateToken(Request $request)
    {
        $data = $request->all();
        $coverPath = null;

        if ($request->hasFile('cover')) {
            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            $file = $request->file('cover');
            $filePath = '/product/images';
            $fileExtension = $file->getClientOriginalExtension();
            $fileName = Str::random(10).'.'.$fileExtension;

            $file->storeAs($filePath, $fileName, 'public');
            $coverPath = 'storage/'.'tenant'.$tenantId.$filePath.'/'.$fileName;
        }

        $estIds = is_string($data['est_ids'] ?? null) ? json_decode($data['est_ids'], true) : ($data['est_ids'] ?? []);
        $productIds = is_string($data['products'] ?? null) ? json_decode($data['products'], true) : ($data['products'] ?? []);
        if (! is_array($estIds) || count($estIds) === 0) {
            return response()->json(['message' => 'est_ids required'], 422);
        }
        if (! is_array($productIds)) {
            $productIds = [];
        }

        $sectionFlags = [];
        if (! empty($data['section_flags'])) {
            $sectionFlags = is_string($data['section_flags'])
                ? (json_decode($data['section_flags'], true) ?: [])
                : (array) $data['section_flags'];
        }

        $rules = [
            'title' => 'nullable|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'custom_menu_id' => 'nullable|integer|exists:product_custom_menus,id',
            'map_lat' => 'nullable|numeric',
            'map_lng' => 'nullable|numeric',
            'map_label' => 'nullable|string|max:500',
            'est_locations' => 'nullable',
            'allergy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];

        if (! empty($sectionFlags['todays_menu'])) {
            $rules['custom_menu_id'] = 'required|integer|exists:product_custom_menus,id';
        }
        if (! empty($sectionFlags['location'])) {
            $rules['map_lat'] = 'nullable|numeric';
            $rules['map_lng'] = 'nullable|numeric';
        }
        if (! empty($sectionFlags['allergy_info'])) {
            $rules['allergy_document'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:10240';
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $allergyPath = null;
        if ($request->hasFile('allergy_document')) {
            $allergyPath = $request->file('allergy_document')->store('menu-allergy', 'public');
        }

        $estLocations = $this->parseEstLocationsFromRequest($request);
        if (! empty($sectionFlags['location'])) {
            if (empty($estLocations) && ($request->input('map_lat') !== null || $request->input('map_lng') !== null)) {
                $estLocations = [
                    (string) (int) $estIds[0] => [
                        'map_lat' => $request->input('map_lat'),
                        'map_lng' => $request->input('map_lng'),
                        'map_label' => $request->input('map_label'),
                    ],
                ];
            }
            $err = $this->validateEstLocations($estIds, $estLocations);
            if ($err) {
                return response()->json(['message' => $err], 422);
            }
        } else {
            $estLocations = [];
        }

        $token = Str::random(30);

        try {
            MenuToken::create([
                'est_id' => (int) $estIds[0],
                'est_ids' => $estIds,
                'est_locations' => $estLocations,
                'title' => $data['title'] ?? '',
                'sub_title' => $data['sub_title'] ?? '',
                'products' => $productIds,
                'custom_menu_id' => $request->input('custom_menu_id') ?: null,
                'map_lat' => $request->input('map_lat'),
                'map_lng' => $request->input('map_lng'),
                'map_label' => $request->input('map_label'),
                'allergy_document_path' => $allergyPath,
                'section_flags' => $sectionFlags,
                'allergen_visible_keys' => $this->parseAllergenVisibleKeysFromRequest($request),
                'cover' => $coverPath,
                'token' => $token,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => config('app.debug')
                    ? ('Could not save menu token: ' . $e->getMessage())
                    : 'Could not save menu token. Run tenant migrations for menu_tokens (e.g. est_ids, section_flags).',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'token' => $token,
        ]);
    }

    public function customMenusForQr()
    {
        $menus = CustomMenu::query()
            ->restrictByFranchise()
            ->where('active', 1)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return response()->json($menus);
    }

    public function customMenuSchedule(int $id)
    {
        $customMenu = CustomMenu::query()->findOrFail($id);
        $date = CustomMenuTime::query()
            ->where('custommenu_id', $customMenu->id)
            ->orderByDesc('id')
            ->with('times')
            ->first();

        $defaultTimes = collect(range(1, 7))->map(function ($dayNo) {
            return [
                'day_no' => $dayNo,
                'from_time' => '00:00:00',
                'to_time' => '23:59:59',
                'active' => 0,
            ];
        });

        if ($date) {
            $times = $defaultTimes->map(function ($row) use ($date) {
                $existing = $date->times->firstWhere('day_no', $row['day_no']);
                if ($existing) {
                    return [
                        'day_no' => (int) $existing->day_no,
                        'from_time' => $existing->from_time ?: '00:00:00',
                        'to_time' => $existing->to_time ?: '23:59:59',
                        'active' => (int) $existing->active,
                    ];
                }

                return $row;
            })->values();

            return response()->json([
                'id' => $date->id,
                'from_date' => $date->from_date,
                'to_date' => $date->to_date,
                'no_date_limit' => (bool) ($date->no_date_limit ?? false),
                'times' => $times,
            ]);
        }

        return response()->json([
            'id' => null,
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'no_date_limit' => false,
            'times' => $defaultTimes->values(),
        ]);
    }

    public function updateCustomMenuSchedule(Request $request, int $id)
    {
        $customMenu = CustomMenu::query()->findOrFail($id);
        $noDateLimit = filter_var($request->input('no_date_limit'), FILTER_VALIDATE_BOOLEAN);

        $dateRules = $noDateLimit
            ? [
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date',
            ]
            : [
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ];

        $validated = $request->validate(array_merge($dateRules, [
            'no_date_limit' => 'sometimes|boolean',
            'times' => 'required|array|min:1',
            'times.*.day_no' => 'required|integer|min:1|max:7',
            'times.*.from_time' => 'required|date_format:H:i:s',
            'times.*.to_time' => 'required|date_format:H:i:s',
            'times.*.active' => 'required|boolean',
        ]));

        $fromDate = $noDateLimit ? '2000-01-01' : $validated['from_date'];
        $toDate = $noDateLimit ? '2099-12-31' : $validated['to_date'];

        foreach ($validated['times'] as $row) {
            if (($row['from_time'] ?? null) >= ($row['to_time'] ?? null)) {
                return response()->json([
                    'message' => app()->getLocale() === 'ar'
                        ? 'وقت البداية يجب أن يكون قبل وقت النهاية.'
                        : 'From time must be earlier than to time.',
                ], 422);
            }
        }

        DB::transaction(function () use ($customMenu, $validated, $fromDate, $toDate, $noDateLimit) {
            $menuTime = CustomMenuTime::query()
                ->where('custommenu_id', $customMenu->id)
                ->orderByDesc('id')
                ->first();

            if (! $menuTime) {
                $menuTime = CustomMenuTime::create([
                    'custommenu_id' => $customMenu->id,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'no_date_limit' => $noDateLimit,
                    'active' => 1,
                ]);
            } else {
                $menuTime->from_date = $fromDate;
                $menuTime->to_date = $toDate;
                $menuTime->no_date_limit = $noDateLimit;
                $menuTime->active = 1;
                $menuTime->save();
            }

            foreach ($validated['times'] as $row) {
                $detail = CustomMenuTimeDetail::query()
                    ->where('custommenu_time_id', $menuTime->id)
                    ->where('day_no', $row['day_no'])
                    ->first();

                if (! $detail) {
                    $detail = new CustomMenuTimeDetail;
                    $detail->custommenu_time_id = $menuTime->id;
                    $detail->day_no = $row['day_no'];
                }
                $detail->from_time = $row['from_time'];
                $detail->to_time = $row['to_time'];
                $detail->active = (int) $row['active'];
                $detail->save();
            }
        });

        return response()->json(['status' => true]);
    }

    public function storeMenuFeedback(Request $request, string $token)
    {
        if (! MenuToken::where('token', $token)->exists()) {
            return response()->json(['message' => 'Invalid token'], 404);
        }

        $validated = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        MenuFeedback::create([
            'token' => $token,
            'stars' => $validated['stars'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function menuFeedbackIndex()
    {
        $feedbacks = MenuFeedback::query()
            ->leftJoin('menu_tokens', 'menu_feedbacks.token', '=', 'menu_tokens.token')
            ->select(
                'menu_feedbacks.*',
                'menu_tokens.title as menu_title',
                'menu_tokens.sub_title as menu_sub_title'
            )
            ->orderByDesc('menu_feedbacks.id')
            ->paginate(40);

        return view('reservation::order.menu-feedback-index', compact('feedbacks'));
    }

    /**
     * عرض المنيو البسيط بناءً على التوكن
     */
    public function menuSimple(Request $request, $token)
    {
        $menuToken = MenuToken::where('token', $token)->first();

        if (! $menuToken) {
            abort(404, 'الرابط غير صالح أو انتهت صلاحيته');
        }

        $sectionKeys = ['todays_menu', 'location', 'smart_menu', 'allergy_info', 'photos', 'feedback', 'info'];
        $menuSectionFlags = [];
        $persisted = is_array($menuToken->section_flags) ? $menuToken->section_flags : [];
        foreach ($sectionKeys as $key) {
            if (array_key_exists($key, $persisted)) {
                $menuSectionFlags[$key] = filter_var($persisted[$key], FILTER_VALIDATE_BOOLEAN);
            } else {
                $menuSectionFlags[$key] = $request->query($key, '1') === '1';
            }
        }

        $allowedEstIds = is_array($menuToken->est_ids) ? $menuToken->est_ids : [];
        $allowedEstIds = array_values(array_unique(array_filter(array_map('intval', $allowedEstIds))));

        $establishment_id = (int) ($menuToken['est_id'] ?? 0);
        $requestedEstId = $request->query('est_id');
        $requestedEstId = is_numeric($requestedEstId) ? (int) $requestedEstId : null;
        if ($requestedEstId && in_array($requestedEstId, $allowedEstIds, true)) {
            $establishment_id = $requestedEstId;
        }
        $title = $menuToken['title'];
        $subTitle = $menuToken['sub_title'];
        $product_ids = is_array($menuToken->products) ? $menuToken->products : [];
        $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));

        $useCustomMenu = ($menuSectionFlags['todays_menu'] ?? false) && $menuToken->custom_menu_id;
        if ($useCustomMenu) {
            $fromCustom = CustomMenuItem::query()
                ->where('custommenu_id', (int) $menuToken->custom_menu_id)
                ->pluck('product_id')
                ->unique()
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            // If today's menu is on but the custom menu has no rows yet, keep token product IDs
            if (count($fromCustom) > 0) {
                $product_ids = $fromCustom;
            }
        }

        $categories = collect();
        if (count($product_ids) > 0) {
            // Token / QR explicitly selects products — do not hide them behind show_in_menu
            $categories = Category::where('active', 1)
                ->whereHas('products', function ($q) use ($product_ids) {
                    $q->whereIn('id', $product_ids);
                })
                ->with(['products' => function ($q) use ($product_ids) {
                    $q->whereIn('id', $product_ids)
                        ->with([
                            'subcategory:id,name_ar,name_en',
                            'tax:id,name,amount',
                            'modifiers.modifierItem:id,name_ar,name_en,type',
                            'combos:id,product_id,name_ar,name_en',
                            'unitTransfers' => function ($unitQuery) {
                                $unitQuery->select('id', 'product_id', 'unit1', 'unit2', 'primary')
                                    ->orderByDesc('primary');
                            },
                        ])
                        ->orderBy('id');
                }])
                ->orderBy('id')
                ->get();
        }

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $establishment = Establishment::find($establishment_id);

        $socialKeys = ['social_whatsapp', 'social_facebook', 'social_instagram', 'social_snapchat', 'social_x', 'menu_cover_image'];
        $socialLinks = Setting::whereIn('key', $socialKeys)->pluck('value', 'key')->toArray();

        $companyLogoUrl = ($company && ! empty($company->logo))
            ? central_public_storage_url_for_path((string) $company->logo)
            : asset('assets/media/avatars/blank.png');

        $mapLat = $menuToken->map_lat;
        $mapLng = $menuToken->map_lng;
        $mapLabel = $menuToken->map_label;
        $estLocations = is_array($menuToken->est_locations) ? $menuToken->est_locations : [];
        $locRow = $estLocations[(string) (int) $establishment_id] ?? $estLocations[(int) $establishment_id] ?? null;
        if (is_array($locRow)) {
            $mapLat = $locRow['map_lat'] ?? $mapLat;
            $mapLng = $locRow['map_lng'] ?? $mapLng;
            $mapLabel = $locRow['map_label'] ?? $mapLabel;
        }
        $allergyDocumentUrl = $menuToken->allergy_document_path
            ? tenant_public_storage_url_for_db_path((string) $menuToken->allergy_document_path)
            : null;

        $customMenu = $menuToken->custom_menu_id
            ? CustomMenu::query()->find($menuToken->custom_menu_id)
            : null;

        $feedbackToken = $token;
        $openingState = $this->resolveMenuOpeningStatus($menuToken->custom_menu_id);

        $storedAllergenKeys = $this->resolveMenuTokenAllergenVisibleKeys($menuToken);
        $allergenFilterKeyIcons = MenuAllergenDefinitions::filterMapForDisplay($storedAllergenKeys);

        $info = [
            'establishment' => $establishment,
            'title' => $title,
            'sub_title' => $subTitle,
            'social' => $socialLinks,
            'sections' => $menuSectionFlags,
            'opening' => $openingState,
        ];

        $menuEstablishments = [];
        if (count($allowedEstIds) > 0) {
            $menuEstablishments = Establishment::query()
                ->whereIn('id', $allowedEstIds)
                ->get(['id', 'name', 'name_en']);
        }

        return view('reservation::order.menuSimple', compact(
            'info',
            'company',
            'categories',
            'establishment',
            'title',
            'subTitle',
            'socialLinks',
            'menuSectionFlags',
            'companyLogoUrl',
            'mapLat',
            'mapLng',
            'mapLabel',
            'allergyDocumentUrl',
            'customMenu',
            'feedbackToken',
            'menuToken',
            'openingState',
            'allergenFilterKeyIcons',
            'menuEstablishments',
            'establishment_id'
        ));
    }

    /**
     * Keys allowed in the customer allergy filter UI; supports JSON string / legacy rows.
     *
     * @return list<string>|null
     */
    private function resolveMenuTokenAllergenVisibleKeys(MenuToken $menuToken): ?array
    {
        $raw = $menuToken->allergen_visible_keys;
        if (is_array($raw)) {
            return empty($raw) ? [] : MenuAllergenDefinitions::normalizeStoredKeys($raw);
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return empty($decoded) ? [] : MenuAllergenDefinitions::normalizeStoredKeys($decoded);
            }
        }

        return null;
    }

    public function menuQR()
    {
        return view('reservation::order.menuQR');
    }

    public function menuQrTokens()
    {
        $tokens = MenuToken::query()
            ->leftJoin('product_custom_menus as pcm', 'pcm.id', '=', 'menu_tokens.custom_menu_id')
            ->select('menu_tokens.*', 'pcm.name_ar as custom_menu_name_ar', 'pcm.name_en as custom_menu_name_en')
            ->orderByDesc('menu_tokens.id')
            ->limit(100)
            ->get();

        $allEstIds = collect($tokens)->flatMap(function ($t) {
            return is_array($t->est_ids) ? $t->est_ids : [];
        })->filter()->unique()->values();
        $estMap = Establishment::whereIn('id', $allEstIds)->pluck('name', 'id');

        $rows = $tokens->map(function ($t) use ($estMap) {
            $estIds = is_array($t->est_ids) ? $t->est_ids : [];
            $estNames = collect($estIds)->map(fn ($id) => $estMap[$id] ?? "#{$id}")->values()->all();
            $opening = $this->resolveMenuOpeningStatus($t->custom_menu_id);

            return [
                'id' => $t->id,
                'token' => $t->token,
                'title' => $t->title,
                'sub_title' => $t->sub_title,
                'products' => is_array($t->products) ? $t->products : [],
                'est_ids' => $estIds,
                'est_names' => $estNames,
                'custom_menu_id' => $t->custom_menu_id,
                'custom_menu_name_ar' => $t->custom_menu_name_ar,
                'custom_menu_name_en' => $t->custom_menu_name_en,
                'map_lat' => $t->map_lat,
                'map_lng' => $t->map_lng,
                'map_label' => $t->map_label,
                'est_locations' => is_array($t->est_locations ?? null) ? $t->est_locations : null,
                'section_flags' => is_array($t->section_flags) ? $t->section_flags : [],
                'allergy_document_path' => $t->allergy_document_path,
                'allergen_visible_keys' => is_array($t->allergen_visible_keys ?? null) ? $t->allergen_visible_keys : null,
                'opening_status' => $opening['status'],
                'opening_hours_text' => $opening['hours_text'],
                'opening_source' => $opening['source'],
                'created_at' => optional($t->created_at)->format('Y-m-d H:i:s'),
            ];
        })->values();

        return response()->json($rows);
    }

    private function resolveMenuOpeningStatus($customMenuId): array
    {
        if (! $customMenuId) {
            return ['status' => 'unknown', 'hours_text' => null, 'source' => 'none'];
        }

        $today = Carbon::today()->toDateString();
        $dayNo = Carbon::now()->dayOfWeek; // 0..6
        $altDayNo = $dayNo === 0 ? 7 : $dayNo; // fallback for systems using 1..7
        $nowTime = Carbon::now()->format('H:i:s');

        $menuTime = CustomMenuTime::query()
            ->where('custommenu_id', $customMenuId)
            ->where('active', 1)
            ->where(function ($q) use ($today) {
                $q->where('no_date_limit', 1)
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereDate('from_date', '<=', $today)
                            ->whereDate('to_date', '>=', $today);
                    });
            })
            ->with(['times' => function ($q) use ($dayNo, $altDayNo) {
                $q->whereIn('day_no', [$dayNo, $altDayNo])->where('active', 1);
            }])
            ->first();

        if (! $menuTime || $menuTime->times->isEmpty()) {
            return ['status' => 'closed', 'hours_text' => null, 'source' => 'custom_menu_no_schedule'];
        }

        $periods = $menuTime->times
            ->map(function ($t) {
                $from = $t->from_time ? substr((string) $t->from_time, 0, 5) : '--:--';
                $to = $t->to_time ? substr((string) $t->to_time, 0, 5) : '--:--';

                return ['from' => $from, 'to' => $to, 'open_now' => ($t->from_time <= now()->format('H:i:s') && now()->format('H:i:s') <= $t->to_time)];
            })
            ->values();

        $isOpen = $periods->contains(fn ($p) => $p['open_now']);
        $hoursText = $periods->map(fn ($p) => "{$p['from']} - {$p['to']}")->implode(' , ');

        return [
            'status' => $isOpen ? 'open' : 'closed',
            'hours_text' => $hoursText,
            'source' => 'custom_menu',
        ];
    }

    public function updateMenuQrToken(Request $request, int $id)
    {
        $token = MenuToken::findOrFail($id);
        $data = $request->all();

        $estIds = is_string($data['est_ids'] ?? null) ? json_decode($data['est_ids'], true) : ($data['est_ids'] ?? []);
        $productIds = is_string($data['products'] ?? null) ? json_decode($data['products'], true) : ($data['products'] ?? []);
        $sectionFlags = is_string($data['section_flags'] ?? null)
            ? (json_decode($data['section_flags'], true) ?: [])
            : ((array) ($data['section_flags'] ?? []));

        $rules = [
            'title' => 'nullable|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'custom_menu_id' => 'nullable|integer|exists:product_custom_menus,id',
            'map_lat' => 'nullable|numeric',
            'map_lng' => 'nullable|numeric',
            'map_label' => 'nullable|string|max:500',
            'est_locations' => 'nullable',
            'allergy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
        if (! empty($sectionFlags['todays_menu'])) {
            $rules['custom_menu_id'] = 'required|integer|exists:product_custom_menus,id';
        }
        if (! empty($sectionFlags['location'])) {
            $rules['map_lat'] = 'nullable|numeric';
            $rules['map_lng'] = 'nullable|numeric';
        }
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        if (! is_array($estIds) || count($estIds) === 0) {
            return response()->json(['message' => 'est_ids required'], 422);
        }
        if (! is_array($productIds)) {
            $productIds = [];
        }

        $allergyPath = $token->allergy_document_path;
        if ($request->hasFile('allergy_document')) {
            if ($allergyPath) {
                Storage::disk('public')->delete($allergyPath);
            }
            $allergyPath = $request->file('allergy_document')->store('menu-allergy', 'public');
        }

        $estLocations = $this->parseEstLocationsFromRequest($request);
        if (! empty($sectionFlags['location'])) {
            if (empty($estLocations) && ($request->input('map_lat') !== null || $request->input('map_lng') !== null)) {
                $estLocations = [
                    (string) (int) $estIds[0] => [
                        'map_lat' => $request->input('map_lat'),
                        'map_lng' => $request->input('map_lng'),
                        'map_label' => $request->input('map_label'),
                    ],
                ];
            }
            $err = $this->validateEstLocations($estIds, $estLocations);
            if ($err) {
                return response()->json(['message' => $err], 422);
            }
        } else {
            $estLocations = [];
        }

        $payload = [
            'est_id' => (int) $estIds[0],
            'est_ids' => $estIds,
            'est_locations' => $estLocations,
            'title' => $data['title'] ?? '',
            'sub_title' => $data['sub_title'] ?? '',
            'products' => $productIds,
            'custom_menu_id' => $request->input('custom_menu_id') ?: null,
            'map_lat' => $request->input('map_lat'),
            'map_lng' => $request->input('map_lng'),
            'map_label' => $request->input('map_label'),
            'allergy_document_path' => $allergyPath,
            'section_flags' => $sectionFlags,
        ];
        if ($request->has('allergen_visible_keys')) {
            $payload['allergen_visible_keys'] = $this->parseAllergenVisibleKeysFromRequest($request);
        }
        $token->update($payload);

        return response()->json(['status' => true, 'token' => $token->token, 'id' => $token->id]);
    }

    public function deleteMenuQrToken(int $id)
    {
        $token = MenuToken::findOrFail($id);
        if ($token->allergy_document_path) {
            Storage::disk('public')->delete($token->allergy_document_path);
        }
        $token->delete();

        return response()->json(['status' => true]);
    }

    public function products(Request $request)
    {
        $categories = Category::where('active', 1)->whereHas('childrenWithProducts')
            ->with(['childrenWithProducts' => function ($query) {
                $query->with(['productsForSale']);
            }])
            ->get();

        return response()->json($categories);
    }

    public function generateOrdNo()
    {
        $prefix = 'ORD';
        $lastOrd = Order::orderBy('no', 'desc')->first();
        $newOrdNumber = $prefix.'000001';

        if ($lastOrd) {
            preg_match('/(\d+)/', $lastOrd->no, $matches);
            $lastNumber = (int) $matches[0];
            $newOrdNumber = $prefix.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }

        return $newOrdNumber;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'establishment_id' => 'nullable|numeric',
            'table_code' => 'required|string',
            'order_status' => 'required|numeric',
        ]);

        $validated['no'] = $this->generateOrdNo();
        $validated['order_date'] = date('Y-m-d');

        // إذا لم يرسل الفرع، نأخذ الفرع الرئيسي أو الأول
        if (! $request->establishment_id) {
            $est = Establishment::where('is_main', 1)->first() ?? Establishment::first();
            $validated['establishment_id'] = $est->id;
        }

        DB::transaction(function () use ($validated, $request) {
            $order = Order::create($validated);
            if (isset($request['items'])) {
                foreach ($request['items'] as $item) {
                    $item['order_id'] = $order->id;
                    $item['item_total_price'] = $item['item_price'] * $item['quantity'];
                    OrderItem::create($item);
                }
            }
        });

        return response()->json(['status' => true, 'message' => 'Order created successfully']);
    }
}
