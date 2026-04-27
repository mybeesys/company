<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Category;
use Modules\Product\Models\CustomMenu;
use Modules\Product\Models\CustomMenuItem;
use Modules\Reservation\Models\MenuFeedback;
use Modules\Reservation\Models\Order;
use Modules\Reservation\Models\OrderItem;
use Illuminate\Support\Str;
use Modules\General\Models\Setting;
use Modules\Reservation\Models\MenuToken;

class OrderController extends Controller
{
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
            $fileName = Str::random(10) . '.' . $fileExtension;

            $file->storeAs($filePath, $fileName, 'public');
            $coverPath = 'storage/' . 'tenant' . $tenantId . $filePath . '/' . $fileName;
        }

        $estIds = is_string($data['est_ids'] ?? null) ? json_decode($data['est_ids'], true) : ($data['est_ids'] ?? []);
        $productIds = is_string($data['products'] ?? null) ? json_decode($data['products'], true) : ($data['products'] ?? []);
        if (!is_array($estIds) || count($estIds) === 0) {
            return response()->json(['message' => 'est_ids required'], 422);
        }
        if (!is_array($productIds)) {
            $productIds = [];
        }

        $sectionFlags = [];
        if (!empty($data['section_flags'])) {
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
            'allergy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];

        if (!empty($sectionFlags['todays_menu'])) {
            $rules['custom_menu_id'] = 'required|integer|exists:product_custom_menus,id';
        }
        if (!empty($sectionFlags['location'])) {
            $rules['map_lat'] = 'required|numeric';
            $rules['map_lng'] = 'required|numeric';
        }
        if (!empty($sectionFlags['allergy_info'])) {
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

        $token = Str::random(30);

        try {
            MenuToken::create([
                'est_id' => (int) $estIds[0],
                'est_ids' => $estIds,
                'title' => $data['title'] ?? '',
                'sub_title' => $data['sub_title'] ?? '',
                'products' => $productIds,
                'custom_menu_id' => $request->input('custom_menu_id') ?: null,
                'map_lat' => $request->input('map_lat'),
                'map_lng' => $request->input('map_lng'),
                'map_label' => $request->input('map_label'),
                'allergy_document_path' => $allergyPath,
                'section_flags' => $sectionFlags,
                'cover' => $coverPath,
                'token' => $token,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Could not save menu token. Run tenant migrations for menu_tokens (e.g. est_ids, section_flags).',
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

    public function storeMenuFeedback(Request $request, string $token)
    {
        if (!MenuToken::where('token', $token)->exists()) {
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

        if (!$menuToken) {
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

        $establishment_id = $menuToken['est_id'];
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
                    $q->whereIn('id', $product_ids)->orderBy('id');
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
        $allergyDocumentUrl = $menuToken->allergy_document_path
            ? tenant_public_storage_url_for_db_path((string) $menuToken->allergy_document_path)
            : null;

        $customMenu = $menuToken->custom_menu_id
            ? CustomMenu::query()->find($menuToken->custom_menu_id)
            : null;

        $feedbackToken = $token;

        $info = [
            'establishment' => $establishment,
            'title' => $title,
            'sub_title' => $subTitle,
            'social' => $socialLinks,
            'sections' => $menuSectionFlags,
        ];

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
            'menuToken'
        ));
    }

    public function menuQR()
    {
        return view('reservation::order.menuQR');
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
        $newOrdNumber = $prefix . '000001';

        if ($lastOrd) {
            preg_match('/(\d+)/', $lastOrd->no, $matches);
            $lastNumber = (int)$matches[0];
            $newOrdNumber = $prefix . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
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
        $validated['order_date'] = date("Y-m-d");

        // إذا لم يرسل الفرع، نأخذ الفرع الرئيسي أو الأول
        if (!$request->establishment_id) {
            $est = Establishment::where('is_main', 1)->first() ?? Establishment::first();
            $validated['establishment_id'] = $est->id;
        }

        DB::transaction(function () use ($validated, $request) {
            $order = Order::create($validated);
            if (isset($request["items"])) {
                foreach ($request["items"] as $item) {
                    $item['order_id'] = $order->id;
                    $item['item_total_price'] = $item['item_price'] * $item['quantity'];
                    OrderItem::create($item);
                }
            }
        });

        return response()->json(['status' => true, 'message' => 'Order created successfully']);
    }
}
