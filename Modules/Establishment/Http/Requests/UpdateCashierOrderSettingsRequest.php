<?php

declare(strict_types=1);

namespace Modules\Establishment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashierOrderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable'],
            'scope' => ['required', 'in:company,branch'],
            'preset' => ['nullable', 'in:retail,qsr,multi_pos,custom'],
            'system_id_mode' => ['required', 'in:internal,dated_serial'],
            'reset_policy' => ['required', 'in:daily,shift,never'],
            'shift_source' => ['required', 'in:register_shift,employee_schedule'],
            'start_number' => ['required', 'integer', 'min:0', 'max:999999'],
            'padding' => ['required', 'integer', 'min:1', 'max:8'],
            'cycle_enabled' => ['nullable'],
            'cycle_max' => ['required', 'integer', 'min:1', 'max:999999'],
            'cycle_warn_on_reuse' => ['nullable'],
            'void_recycles_number' => ['nullable'],
            'allocation' => ['required', 'in:central,prefix,range'],
            'prefix_style' => ['required', 'in:letter,numeric,ref,custom'],
            'global_prefix' => ['nullable', 'string', 'max:8'],
            'separator' => ['nullable', 'string', 'max:1'],
            'format_pattern' => ['required', 'string', 'max:80'],
            'show_terminal_in_display' => ['nullable'],
            'show_on_cashier' => ['nullable'],
            'show_on_receipt' => ['nullable'],
            'show_on_kds' => ['nullable'],
            'show_on_queue_screen' => ['nullable'],
            'kitchen_same_as_display' => ['nullable'],
            'display_size' => ['required', 'in:normal,large,xlarge'],
            'search_by_display' => ['nullable'],
            'search_requires_date_when_cycled' => ['nullable'],
            'offline_policy' => ['required', 'in:refuse,terminal_prefix,local_temp'],
            'apply_dining_prefixes' => ['nullable'],
            'dining_type_prefixes' => ['nullable', 'array'],
            'dining_type_prefixes.*' => ['nullable', 'string', 'max:8'],
            'terminals' => ['nullable', 'array'],
            'terminals.*.enabled' => ['nullable'],
            'terminals.*.prefix' => ['nullable', 'string', 'max:8'],
            'terminals.*.range_from' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'terminals.*.range_to' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $flags = [
            'enabled',
            'cycle_enabled',
            'cycle_warn_on_reuse',
            'void_recycles_number',
            'show_terminal_in_display',
            'show_on_cashier',
            'show_on_receipt',
            'show_on_kds',
            'show_on_queue_screen',
            'kitchen_same_as_display',
            'search_by_display',
            'search_requires_date_when_cycled',
            'apply_dining_prefixes',
        ];

        $merge = [];
        foreach ($flags as $flag) {
            $merge[$flag] = $this->boolean($flag) ? '1' : '0';
        }

        $terminals = $this->input('terminals', []);
        if (is_array($terminals)) {
            foreach ($terminals as $id => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $terminals[$id]['enabled'] = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOL) ? '1' : '0';
            }
            $merge['terminals'] = $terminals;
        }

        $this->merge($merge);
    }
}
