<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\DiningType;
use Modules\Product\Models\PaymentCard;
use Modules\Product\Models\ServiceFee;
use Modules\Product\Models\TreeBuilder;

class DiningTypeController extends Controller
{
    public function getDiningTypes()
    {
        $digningTypes = DiningType::all();
        return response()->json($digningTypes);
    }

    public function getDiningTypesTree()
    {
        $digningTypes = DiningType::all();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($digningTypes ,null, 'diningType', null, null, null);
        return response()->json($tree);
    }

    public function index()
    {
        // Pass the posts to the view
        return view('product::paymentCard.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'active' => 'nullable|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $digningType = ServiceFee::where([['service_fee_id', '=', $validated['id']]])->first();
            if($digningType != null)
                return response()->json(["message"=>"SERVICE_FEE_CHILD_EXIST"]);

            $digningType = DiningType::find($validated['id']);
            $digningType->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $digningType = DiningType::where('name_ar', $validated['name_ar'])->first();
            if($digningType != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $digningType = DiningType::where('name_en', $validated['name_en'])->first();
            if($digningType != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $digningType = DiningType::create($validated);
        } else {
            $digningType = DiningType::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($digningType != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $digningType = DiningType::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($digningType != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $digningType = DiningType::find($validated['id']);
            $digningType->name_ar = $validated['name_ar'];
            $digningType->name_en = $validated['name_en'];
            $digningType->active = $validated['active'];
            $digningType->save();
        }
        return response()->json(["message" => "Done"]);
    }

}
