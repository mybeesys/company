<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\FranchiseCompany;
use Modules\Franchise\Entities\FranchiseContract;
use Yajra\DataTables\Facades\DataTables;
use DB;

class FranchiseCompanyController extends Controller
{
    /**
     * عرض قائمة الشركات مع الفلترة الذكية
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FranchiseCompany::query();

            // منطق التقسيم المطلوب:
            if ($request->view_type == 'new_no_contract') {
                // شركات جديدة بلا أي عقد
                $query->doesntHave('contracts');
            } elseif ($request->view_type == 'active_contracts') {
                // شركات لديها عقود سارية المفعول حالياً
                $query->whereHas('contracts', function($q) {
                    $q->where('status', 'active')
                      ->where('end_date', '>=', now()->format('Y-m-d'));
                });
            } elseif ($request->view_type == 'expired_contracts') {
                // شركات عقودها منتهية (ولا تملك أي عقد ساري آخر)
                $query->whereHas('contracts', function($q) {
                    $q->where('end_date', '<', now()->format('Y-m-d'));
                })->whereDoesntHave('contracts', function($q) {
                    $q->where('end_date', '>=', now()->format('Y-m-d'));
                });
            }

            return Datatables::of($query)
                ->addColumn('actions', function($row) {
                    return view('franchise::companies.partials.actions', compact('row'));
                })
                ->editColumn('status_label', function($row) {
                    // تصنيف لوني سريع في الجدول
                    if ($row->contracts()->count() == 0) return '<span class="badge badge-light-warning">جديد - بلا عقد</span>';
                    return $row->contracts()->where('end_date', '>=', now())->exists() 
                        ? '<span class="badge badge-light-success">فعال</span>' 
                        : '<span class="badge badge-light-danger">منتهي</span>';
                })
                ->rawColumns(['actions', 'status_label'])
                ->make(true);
        }
        return view('franchise::companies.index');
    }

    /**
     * حفظ شركة جديدة
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'city'    => 'required',
            'vat_no'  => 'required|unique:franchise_companies,vat_no',
            'mobile'  => 'required',
            'email'   => 'required|email|unique:franchise_companies,email',
            'account' => 'required', // الحساب المالي
        ]);

        FranchiseCompany::create($data);

        return response()->json(['success' => true, 'message' => 'تمت إضافة الممنوح بنجاح']);
    }

    /**
     * عرض تفصيلي للشركة مع سجل عقودها
     */
    public function show($id)
    {
        $company = FranchiseCompany::with(['contracts' => function($q) {
            $q->orderBy('start_date', 'desc');
        }])->findOrFail($id);

        return view('franchise::companies.show', compact('company'));
    }

    /**
     * تحديث بيانات الشركة
     */
    public function update(Request $request, $id)
    {
        $company = FranchiseCompany::findOrFail($id);
        $data = $request->validate([
            'name_ar' => 'required',
            'vat_no'  => 'required|unique:franchise_companies,vat_no,'.$id,
            'email'   => 'required|email|unique:franchise_companies,email,'.$id,
        ]);

        $company->update($request->all());
        return response()->json(['success' => true, 'message' => 'تم تحديث البيانات بنجاح']);
    }

    /**
     * حذف الشركة (مع التحقق من وجود عقود)
     */
    public function destroy($id)
    {
        $company = FranchiseCompany::findOrFail($id);
        
        if ($company->contracts()->exists()) {
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف الشركة لوجود عقود مرتبطة بها']);
        }

        $company->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الممنوح بنجاح']);
    }
}