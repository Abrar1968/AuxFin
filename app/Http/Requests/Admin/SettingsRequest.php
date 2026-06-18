<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AuthorizedRequest;

class SettingsRequest extends AuthorizedRequest
{
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'updateGeneral' => [
                'company_name' => ['required', 'string', 'max:200'],
                'company_email' => ['required', 'email', 'max:200'],
                'currency' => ['required', 'string', 'max:8'],
                'timezone' => ['required', 'string', 'max:64'],
                'available_cash' => ['required', 'numeric', 'min:0'],
            ],
            'updateLatePolicy' => [
                'late_days_per_unit' => ['required', 'integer', 'min:1', 'max:10'],
                'deduction_unit_type' => ['required', 'in:full_day,half_day'],
                'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:180'],
                'office_start_time' => ['required', 'date_format:H:i'],
                'carry_forward' => ['required', 'boolean'],
            ],
            'updateTaxPolicy' => [
                'corporate_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            ],
            'updateLoanPolicy' => [
                'max_loan_multiplier' => ['required', 'integer', 'min:1', 'max:12'],
                'max_repayment_months' => ['required', 'integer', 'min:1', 'max:60'],
                'cooling_period_months' => ['required', 'integer', 'min:0', 'max:24'],
                'concurrent_loans' => ['required', 'integer', 'min:1', 'max:3'],
            ],
            'createHoliday' => [
                'name' => ['required', 'string', 'max:200'],
                'date' => ['required', 'date', 'unique:public_holidays,date'],
                'is_optional' => ['nullable', 'boolean'],
            ],
            default => [],
        };
    }
}
