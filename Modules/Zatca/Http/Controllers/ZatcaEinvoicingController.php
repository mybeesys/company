<?php

namespace Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Zatca\Http\Controllers\Concerns\LoadsZatcaDocumentListings;
use Modules\Zatca\Models\ZatcaSetting;

class ZatcaEinvoicingController extends Controller
{
    use LoadsZatcaDocumentListings;

    public function index(Request $request): View
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();
        $activeTab = old('active_tab', session('active_tab', request('tab', 'send')));
        if (! in_array($activeTab, ['send', 'returns'], true)) {
            $activeTab = 'send';
        }

        $sellListing = $this->loadSellInvoiceListing($request);
        $returnListing = $this->loadSellReturnListing($request);

        return view('zatca::einvoicing.index', array_merge($sellListing, $returnListing, [
            'setting' => $setting,
            'activeTab' => $activeTab,
            'zatcaListingRoute' => 'zatca.einvoicing.index',
        ]));
    }
}
