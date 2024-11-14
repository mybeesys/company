<?php

namespace Modules\Employee\Services;


class ShiftFilters
{
    public function __construct(protected array $filters) {
    }

    public function applyFilters($request, $employees)
    {
        foreach ($this->filters as $filter) {
            $filterValue = $request->{$filter} ?? null;

            if ($filterValue && $filterValue !== 'all') {
                $this->{$filter}($filterValue, $employees);
            }
        }
    }

    public function filter_role($value, $employees)
    {
        $employees->where(function ($query) use ($value) {
            $query->whereHas('roles', fn($query) => $query->where('role_id', $value))
                ->orWhereHas('establishmentRoles', fn($query) => $query->where('role_id', $value));
        });
    }

    public function filter_establishment($value, $employees)
    {
        $value === 'all_establishments' ? $employees->whereHas('roles') : $employees->whereHas('establishments', fn($query) => $query->where('establishment_id', $value));
    }

    public function filter_employee_status($value, $employees)
    {
        $employees->where('isActive', $value);
    }
}
