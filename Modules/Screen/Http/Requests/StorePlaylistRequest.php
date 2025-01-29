<?php

namespace Modules\Screen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaylistRequest extends FormRequest
{

    public function prepareForValidation()
    {
        $this->merge([
            'establishments_ids' => is_array(explode(',', $this->establishments_ids)) ? explode(',', $this->establishments_ids) : [explode(',', $this->establishments_ids)],
            'devices' => is_array(explode(',', $this->devices)) ? explode(',', $this->devices) : [explode(',', $this->devices)],
            'days_of_the_weak' => is_array(explode(',', $this->days_of_the_weak)) ? explode(',', $this->days_of_the_weak) : [explode(',', $this->days_of_the_weak)],
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'days_settings' => ['required', 'in:every_day,days_of_the_weak,custom_date_time,manual'],
            'start_time' => [Rule::requiredIf(fn() => in_array(request('days_settings'), ['every_day', 'days_of_the_weak'])), 'date_format:H:i', 'nullable'],
            'days_of_the_weak' => ['required_if:days_settings,days_of_the_weak', 'array'],
            'days_of_the_weak.*' => ['in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'start_date_time' => ['required_if:days_settings,custom_date_time', 'nullable', 'date_format:Y-m-d H:i:s'],
            // 'devices' => ['required', 'array'],
            // 'devices.*' => ['required', 'exists:screen_devices,id', 'integer'],
            'establishments_ids' => ['required', 'array'],
            'establishments_ids.*' => ['required', 'integer', 'exists:est_establishments,id'],
            'selected_promos' => ['required', 'array'],
            'selected_promos.*' => ['required', 'exists:screen_promos,id', 'integer']
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
