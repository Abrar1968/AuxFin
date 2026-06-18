<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
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

    public function updateGeneral(SettingsRequest $request): JsonResponse
    {
        $payload = $request->validated();

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

    public function updateLatePolicy(SettingsRequest $request): JsonResponse
    {
        $payload = $request->validated();

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

    public function updateTaxPolicy(SettingsRequest $request): JsonResponse
    {
        $payload = $request->validated();

        Setting::query()->updateOrCreate(
            ['key' => 'tax_policy'],
            ['value' => $payload]
        );

        return response()->json([
            'message' => 'Tax policy updated successfully.',
            'tax_policy' => $payload,
        ]);
    }

    public function updateLoanPolicy(SettingsRequest $request): JsonResponse
    {
        $payload = $request->validated();

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

    public function createHoliday(SettingsRequest $request): JsonResponse
    {
        $payload = $request->validated();

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
