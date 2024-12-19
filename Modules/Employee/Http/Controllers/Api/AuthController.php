<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Employee\Http\Requests\Api\Auth\LoginRequest;
use Modules\Employee\Http\Requests\Api\Auth\LogoutRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\Api\TimeCardService;

class AuthController extends Controller
{

    public function __construct(protected TimeCardService $timeCardService)
    {
    }
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $employee = Employee::where('pin', $request->pin)->first();

        if (!$employee->pos_is_active) {
            return response()->json(['message' => __('employee::ApiResponses.account_disabled')], 401);
        }
        $employee->tokens()->delete();

        $timeCard = $this->timeCardService->storeClockInTimecard($employee->id, $request->validated('establishment_id'), $request->validated('clock_in_time'), $request->validated('date'));

        if ($timeCard['status']) {
            return response()->json(['employee_id' => $employee->id, 'timecard_id' => $timeCard['id']]);
        } else {
            if ($timeCard['status_code'] == 409) {
                return response()->json([
                    'message' => $timeCard['message'],
                ], $timeCard['status_code']);
            } else {
                return response()->json(['error' => $timeCard['message']], $timeCard['status_code']);
            }
        }
    }

    public function destroy(LogoutRequest $request)
    {
        $updated = $this->timeCardService->storeClockOutTimeCard($request->validated('timecard_id'), Carbon::parse($request->validated('clock_out_time')));
        return response()->json(['message' => $updated['message']], $updated['status_code']);
    }
}