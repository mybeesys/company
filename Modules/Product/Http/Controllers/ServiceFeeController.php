<?php
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\ServiceFee;
use Modules\Product\Models\ServiceFeeDiningType;
use Modules\Product\Models\ServiceFeePaymentCard;
use Modules\Product\Models\TreeBuilder;

class ServiceFeeController extends Controller
{
    public function getServiceFeesTree()
    {
        $stations = ServiceFee::all();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($stations ,null, 'serviceFee', null, null, null);
        return response()->json($tree);
    }

    public function index()
    {
        // Pass the posts to the view
        return view('product::serviceFee.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'service_fee_type' => 'required|numeric',
            'amount' => 'required|numeric',
            'application_type' => 'required|numeric',
            'calculation_method' => 'required|numeric',
            'taxable' => 'required|boolean',
            'active' => 'required|boolean',
            'auto_apply_type'=> 'nullable|numeric',
            'from_date'=> 'nullable|date',
            'to_date'=> 'nullable|date',
            'credit_type'=> 'nullable|numeric',
            'guestCount'=> 'nullable|numeric',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $serviceFee = ServiceFee::find($validated['id']);
            $serviceFee->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $serviceFee = ServiceFee::where('name_ar', $validated['name_ar'])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $serviceFee = ServiceFee::where('name_en', $validated['name_en'])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            DB::transaction(function () use ($serviceFee, $request, $validated) {
                $serviceFee = ServiceFee::create($validated);
                if(isset($request['cards'])){
                    foreach ($request['cards'] as $newCard) {
                        if(isset($newCard)){
                            $paymentCard = new ServiceFeePaymentCard();
                            $paymentCard->payment_card_id = $newCard['payment_card_id'];
                            $paymentCard->service_fee_id = $serviceFee->id;
                            $paymentCard = $paymentCard->save();

                        }
                    }
                }
                if(isset($request['diningTypes'])){
                    foreach ($request['diningTypes'] as $newDiningType) {
                        if(isset($newDiningType)){
                            $digningType = new ServiceFeeDiningType();
                            $digningType->dining_type_id = $newDiningType['dining_type_id'];
                            $digningType->service_fee_id = $serviceFee->id;
                            $digningType = $digningType->save();
                        }
                    }
                }
            });
        } else {
            $serviceFee = ServiceFee::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $serviceFee = ServiceFee::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $serviceFee = ServiceFee::find($validated['id']);
            $serviceFee->name_ar = $validated['name_ar'];
            $serviceFee->name_en = $validated['name_en'];
            $serviceFee->service_fee_type = $validated['service_fee_type'];
            $serviceFee->amount = $validated['amount'];
            $serviceFee->application_type = $validated['application_type'];
            $serviceFee->calculation_method = $validated['calculation_method'];
            $serviceFee->taxable = $validated['taxable'];
            $serviceFee->active = $validated['active'];
            $serviceFee->minimum = isset($validated['minimum']) ? $validated['minimum'] : null;
            $serviceFee->auto_apply_type = isset($validated['auto_apply_type']) ? $validated['auto_apply_type'] : null;
            $serviceFee->from_date = isset($validated['from_date']) ? $validated['from_date'] : null;
            $serviceFee->to_date = isset($validated['to_date']) ? $validated['to_date'] : null;
            $serviceFee->credit_type = isset($validated['credit_type']) ? $validated['credit_type'] : null;
            $serviceFee->guestCount = isset($validated['guestCount']) ? $validated['guestCount'] : null;
            DB::transaction(function () use ($serviceFee, $request) {
                $serviceFee->save();
                ServiceFeePaymentCard::where('service_fee_id', '=', $serviceFee->id)->delete();
                ServiceFeeDiningType::where('service_fee_id', '=', $serviceFee->id)->delete();
                if(isset($request['cards'])){
                    foreach ($request['cards'] as $newCard) {
                        if(isset($newCard)){
                            $paymentCard = new ServiceFeePaymentCard();
                            $paymentCard->payment_card_id = $newCard['payment_card_id'];
                            $paymentCard->service_fee_id = $serviceFee->id;
                            $paymentCard = $paymentCard->save();

                        }
                    }
                }
                if(isset($request['diningTypes'])){
                    foreach ($request['diningTypes'] as $newDiningType) {
                        if(isset($newDiningType)){
                            $digningType = new ServiceFeeDiningType();
                            $digningType->dining_type_id = $newDiningType['dining_type_id'];
                            $digningType->service_fee_id = $serviceFee->id;
                            $digningType = $digningType->save();
                        }
                    }
                }
            });
        }
        return response()->json(["message" => "Done"]);
    }

    public function edit($id)
    {
        $serviceFee  = ServiceFee::find($id);
        $serviceFee->cards = $serviceFee->cards;
        $serviceFee->diningTypes = $serviceFee->diningTypes;
        return view('product::serviceFee.edit', compact('serviceFee'));
    }

    public function create()
    {
        $serviceFee  = new ServiceFee();
        $serviceFee->service_fee_type = 0;
        $serviceFee->application_type = 0;
        $serviceFee->calculation_method = 0;
        $serviceFee->taxable = 0;
        $serviceFee->active = 0;
        $serviceFee->cards = [];
        $serviceFee->diningTypes = [];
        return view('product::serviceFee.create', compact('serviceFee'));
    }

}
