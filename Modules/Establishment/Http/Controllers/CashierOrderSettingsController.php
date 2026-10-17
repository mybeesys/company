<?php

declare(strict_types=1);

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Establishment\Http\Requests\UpdateCashierOrderSettingsRequest;
use Modules\Establishment\Models\EstPos;
use Modules\Establishment\Services\CashierOrderSettings;
use Modules\Product\Models\DiningType;

class CashierOrderSettingsController extends Controller
{
    public function edit()
    {
        $settings = CashierOrderSettings::get();
        $cashierDevices = $this->cashierDevices();
        $settings = old() ? CashierOrderSettings::normalize(old() + $settings) : $settings;

        return view('establishment::settings.cashier-orders', [
            'settings' => $settings,
            'preview' => CashierOrderSettings::preview($settings, $this->previewContext($settings, $cashierDevices)),
            'cashierDevices' => $cashierDevices,
            'diningTypes' => DiningType::query()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function update(UpdateCashierOrderSettingsRequest $request)
    {
        $saved = CashierOrderSettings::save($request->validated());

        return to_route('cashier-settings.orders')
            ->with('success', __('establishment::responses.updated_successfully', [
                'name' => __('establishment::cashier-orders.page_title'),
            ]))
            ->with('preview', CashierOrderSettings::preview($saved, $this->previewContext($saved, $this->cashierDevices())));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function previewContext(array $settings, $cashierDevices = null): array
    {
        $firstPrefix = (string) ($settings['global_prefix'] ?? '');
        foreach ($settings['terminals'] ?? [] as $row) {
            if (is_array($row) && ($row['enabled'] ?? true) && ($row['prefix'] ?? '') !== '') {
                $firstPrefix = (string) $row['prefix'];
                break;
            }
        }
        if ($firstPrefix === '' && $cashierDevices) {
            $first = $cashierDevices->first();
            if ($first && $first->ref) {
                $firstPrefix = preg_replace('/[^\p{L}\p{N}]/u', '', (string) $first->ref) ?: 'A';
            }
        }

        $diningPrefix = '';
        foreach ($settings['dining_type_prefixes'] ?? [] as $prefix) {
            if ((string) $prefix !== '') {
                $diningPrefix = (string) $prefix;
                break;
            }
        }

        return [
            'seq' => 45,
            'prefix' => $firstPrefix !== '' ? $firstPrefix : 'A',
            'dining_prefix' => $diningPrefix !== '' ? $diningPrefix : 'D',
            'date' => date('Ymd'),
            'shift' => 'S1',
        ];
    }

    private function cashierDevices()
    {
        return EstPos::query()
            ->with('establishment:id,name,name_en')
            ->where('type', 'cashier')
            ->orderBy('name')
            ->get();
    }
}
