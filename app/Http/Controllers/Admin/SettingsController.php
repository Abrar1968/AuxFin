<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PublicHoliday;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getGeneral(): JsonResponse
    {
        $defaults = [
            'company_name' => 'AuxFin',
            'company_email' => 'finance@auxfin.local',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'available_cash' => 0,
        ];

        $general = Setting::getValue('general_settings', $defaults);
        if (! is_array($general)) {
            $general = $defaults;
        }

        return response()->json([
            'general_settings' => array_merge($defaults, $general),
        ]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'company_name' => ['required', 'string', 'max:200'],
            'company_email' => ['required', 'email', 'max:200'],
            'currency' => ['required', 'string', 'max:8'],
            'timezone' => ['required', 'string', 'max:64'],
            'available_cash' => ['required', 'numeric', 'min:0'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'general_settings'],
            ['value' => $payload]
        );

        return response()->json([
            'message' => 'General settings updated successfully.',
            'general_settings' => $payload,
        ]);
    }

    public function getLatePolicy(): JsonResponse
    {
        $defaults = [
            'late_days_per_unit' => 2,
            'deduction_unit_type' => 'full_day',
            'grace_period_minutes' => 15,
            'office_start_time' => '09:00',
            'carry_forward' => false,
        ];

        $policy = Setting::getValue('late_policy', $defaults);

        if (is_string($policy)) {
            $decoded = json_decode($policy, true);
            $policy = is_array($decoded) ? $decoded : $defaults;
        }

        if (! is_array($policy)) {
            $policy = $defaults;
        }

        return response()->json([
            'late_policy' => $policy,
        ]);
    }

    public function updateLatePolicy(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'late_days_per_unit' => ['required', 'integer', 'min:1', 'max:10'],
            'deduction_unit_type' => ['required', 'in:full_day,half_day'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'office_start_time' => ['required', 'date_format:H:i'],
            'carry_forward' => ['required', 'boolean'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'late_policy'],
            ['value' => $payload]
        );

        return response()->json([
            'message' => 'Late policy updated successfully.',
            'late_policy' => $payload,
        ]);
    }

    public function getLoanPolicy(): JsonResponse
    {
        $defaults = [
            'max_loan_multiplier' => 3,
            'max_repayment_months' => 12,
            'cooling_period_months' => 3,
            'concurrent_loans' => 1,
        ];

        $policy = Setting::getValue('loan_policy', $defaults);
        if (! is_array($policy)) {
            $policy = $defaults;
        }

        return response()->json([
            'loan_policy' => array_merge($defaults, $policy),
        ]);
    }

    public function getTaxPolicy(): JsonResponse
    {
        $defaults = [
            'corporate_tax_rate' => 30,
        ];

        $policy = Setting::getValue('tax_policy', $defaults);
        if (! is_array($policy)) {
            $policy = $defaults;
        }

        return response()->json([
            'tax_policy' => array_merge($defaults, $policy),
        ]);
    }

    public function updateTaxPolicy(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'corporate_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'tax_policy'],
            ['value' => $payload]
        );

        return response()->json([
            'message' => 'Tax policy updated successfully.',
            'tax_policy' => $payload,
        ]);
    }

    public function updateLoanPolicy(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'max_loan_multiplier' => ['required', 'integer', 'min:1', 'max:12'],
            'max_repayment_months' => ['required', 'integer', 'min:1', 'max:60'],
            'cooling_period_months' => ['required', 'integer', 'min:0', 'max:24'],
            'concurrent_loans' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'loan_policy'],
            ['value' => $payload]
        );

        return response()->json([
            'message' => 'Loan policy updated successfully.',
            'loan_policy' => $payload,
        ]);
    }

    public function holidays(Request $request): JsonResponse
    {
        $rows = PublicHoliday::query()
            ->orderBy('date')
            ->paginate($request->integer('per_page', 50));

        return response()->json($rows);
    }

    public function createHoliday(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'date' => ['required', 'date', 'unique:public_holidays,date'],
            'is_optional' => ['nullable', 'boolean'],
        ]);

        $holiday = PublicHoliday::query()->create([
            'name' => $payload['name'],
            'date' => $payload['date'],
            'is_optional' => (bool) ($payload['is_optional'] ?? false),
        ]);

        return response()->json([
            'message' => 'Holiday created successfully.',
            'holiday' => $holiday,
        ], 201);
    }

    public function deleteHoliday(int $id): JsonResponse
    {
        $holiday = PublicHoliday::query()->findOrFail($id);
        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted successfully.']);
    }
}
