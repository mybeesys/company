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
            return response()->json(['token' => $employee->createToken($employee->email)->plainTextToken, 'timecard_id' => $timeCard['id']]);
        } else {
            if ($timeCard['status_code'] == 409) {
                return response()->json([
                    'error' => $timeCard['message'],
                    'token' => $employee->createToken($employee->email)->plainTextToken,
                    'timecard_id' => $timeCard['id']
                ], $timeCard['status_code']);
            } else {
                return response()->json(['error' => $timeCard['message']], $timeCard['status_code']);
            }
        }
    }

    public function destroy(LogoutRequest $request)
    {
        $updated = $this->timeCardService->storeClockOutTimeCard($request->validated('timecard_id'), Carbon::parse($request->validated('clock_out_time')));
        if ($updated) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => __('employee::ApiResponses.logged_out')], 200);
        } else {
            return response()->json(['error' => __('employee::ApiResponses.server_error')], 500);
        }
    }
}