<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\CustomMenu;
use Modules\Product\Models\PaymentCard;
use Modules\Product\Models\ServiceFee;
use Modules\Product\Models\ServiceFeePaymentCard;
use Modules\Product\Models\TreeBuilder;

class PaymentCardController extends Controller
{
    public function getPaymentCards()
    {
        $stations = PaymentCard::all();
        return response()->json($stations);
    }

    public function getPaymentCardsTree()
    {
        $stations = PaymentCard::all();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($stations ,null, 'paymentCard', null, null, null);
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
            $serviceFee = ServiceFeePaymentCard::where([['service_fee_id', '=', $validated['id']]])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"SERVICE_FEE_CHILD_EXIST"]);

            $paymentCard = PaymentCard::find($validated['id']);
            $paymentCard->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $paymentCard = PaymentCard::where('name_ar', $validated['name_ar'])->first();
            if($paymentCard != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $paymentCard = PaymentCard::where('name_en', $validated['name_en'])->first();
            if($paymentCard != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $paymentCard = PaymentCard::create($validated);
        } else {
            $paymentCard = PaymentCard::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($paymentCard != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $paymentCard = PaymentCard::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($paymentCard != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $paymentCard = PaymentCard::find($validated['id']);
            $paymentCard->name_ar = $validated['name_ar'];
            $paymentCard->name_en = $validated['name_en'];
            $paymentCard->active = $validated['active'];
            $paymentCard->save();
        }
        return response()->json(["message" => "Done"]);
    }

}
