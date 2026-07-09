<?php

namespace Modules\Screen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Screen\Models\Device;

class StorePlaylistRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $parseToArray = function ($value) {
            if (is_array($value)) {
                return $value;
            }
            if (is_null($value) || $value === '') {
                return [];
            }

            return array_values(array_filter(explode(',', (string) $value), fn ($v) => $v !== ''));
        };

        $this->merge([
            'establishments_ids' => $parseToArray($this->establishments_ids),
            'devices' => $parseToArray($this->devices),
            'selected_promos' => $parseToArray($this->selected_promos),
            'days_of_the_weak' => $parseToArray($this->days_of_the_weak),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $deviceIds = array_values(array_unique(array_map('intval', $this->input('devices', []))));
            $establishmentIds = array_values(array_unique(array_map('intval', $this->input('establishments_ids', []))));

            if ($deviceIds === []) {
                $validator->errors()->add('devices', __('screen::general.devices_required'));

                return;
            }

            $invalidDeviceIds = Device::idsNotMatchingEstablishments($deviceIds, $establishmentIds);

            if ($invalidDeviceIds !== []) {
                $deviceLabel = Device::codesForIds($invalidDeviceIds) ?: implode(', ', $invalidDeviceIds);
                $validator->errors()->add(
                    'devices',
                    __('screen::general.devices_not_in_establishments', ['devices' => $deviceLabel])
                );
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'days_settings' => ['required', 'in:every_day,days_of_the_weak,custom_date_time,manual'],
            'start_time' => [Rule::requiredIf(fn () => in_array(request('days_settings'), ['every_day', 'days_of_the_weak'])), 'date_format:H:i', 'nullable'],
            'days_of_the_weak' => ['required_if:days_settings,days_of_the_weak', 'array'],
            'days_of_the_weak.*' => ['in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'start_date_time' => ['required_if:days_settings,custom_date_time', 'nullable', 'date_format:Y-m-d H:i'],
            'transition_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'screen_orientation' => ['nullable', 'in:landscape,portrait'],
            'devices' => ['required', 'array'],
            'devices.*' => ['required', 'exists:screen_devices,id', 'integer'],
            'establishments_ids' => ['required', 'array'],
            'establishments_ids.*' => ['required', 'integer', 'exists:est_establishments,id'],
            'selected_promos' => ['required', 'array'],
            'selected_promos.*' => ['required', 'exists:screen_promos,id', 'integer'],
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
