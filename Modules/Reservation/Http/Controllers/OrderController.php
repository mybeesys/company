<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
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

        // معالجة رفع الصورة إذا وجدت
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

        $token = Str::random(30);

        // التعديل هنا: استلام est_ids كمصفوفة وتحويلها لـ JSON
        // نستخدم json_decode لأن الفرونت إند يرسلها كـ FormData Stringified
        $estIds = is_string($data['est_ids']) ? json_decode($data['est_ids'], true) : $data['est_ids'];
        $productIds = is_string($data['products']) ? json_decode($data['products'], true) : $data['products'];

        $menuToken = MenuToken::create([
            'est_id'    => is_array($estIds) ? $estIds[0] : $estIds, // نخزن الأول كمرجع أساسي إذا كان الحقل لا يدعم مصفوفة
            'est_ids'   => $estIds, // تأكد أن جدول MenuToken يحتوي على هذا الحقل أو يستخدم JSON
            'title'     => $data['title'] ?? '',
            'sub_title' => $data['sub_title'] ?? '',
            'products'  => $productIds ?? [],
            'cover'     => $coverPath,
            'token'     => $token,
        ]);

        return response()->json([
            'status' => true,
            'token'  => $token
        ]);
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

        // استلام بارامترات الأقسام من الرابط (التي أرسلها الفرونت إند)
        $sections = [
            'todays_menu'  => $request->query('todays_menu', '1'),
            'location'     => $request->query('location', '1'),
            'smart_menu'   => $request->query('smart_menu', '1'),
            'allergy_info' => $request->query('allergy_info', '1'),
            'photos'       => $request->query('photos', '1'),
            'feedback'     => $request->query('feedback', '1'),
            'info'         => $request->query('info', '1'),
        ];

        $establishment_id = $menuToken['est_id'];
        $title = $menuToken['title'];
        $subTitle = $menuToken['sub_title'];
        $product_ids = $menuToken->products ?? [];

        // جلب الفئات والمنتجات المختارة فقط
        $categories = Category::where('active', 1)
            ->whereHas('products', function ($q) use ($product_ids) {
                $q->whereIn('id', $product_ids)->where('show_in_menu', 1);
            })
            ->with(['products' => function ($q) use ($product_ids) {
                $q->whereIn('id', $product_ids)->where('show_in_menu', 1);
            }])->get();

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $establishment = Establishment::find($establishment_id);

        $socialKeys = ['social_whatsapp', 'social_facebook', 'social_instagram', 'social_snapchat', 'social_x', 'menu_cover_image'];
        $socialLinks = Setting::whereIn('key', $socialKeys)->pluck('value', 'key')->toArray();

        $info = [
            'establishment' => $establishment,
            'title' => $title,
            'sub_title' => $subTitle,
            'social' => $socialLinks,
            'sections' => $sections 
        ];

        return view('reservation::order.menuSimple', compact('info', 'company', 'categories', 'establishment', 'title', 'subTitle', 'socialLinks', 'sections'));
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
