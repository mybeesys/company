<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\ShiftTable;
use Modules\Employee\Http\Requests\StoreShiftRequest;
use Modules\Employee\Models\Role;
use Modules\Employee\Models\Schedule;
use Modules\Employee\Models\Shift;
use Modules\Employee\Models\TimeSheetRule;
use Modules\Employee\Services\ShiftService;
use Modules\Establishment\Models\Establishment;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $table = new ShiftTable($request->table_type ?? 'default', $request);
            return $table->getShiftTable();
        }
        $roles = Role::get(['id', 'name']);
        $establishments = Establishment::active()->notMain()->get(['id', 'name']);
        $timeSheet_rules = TimeSheetRule::all();
        $columns = ShiftTable::getShiftColumns();
        $footers = ShiftTable::getShiftFooters();
        return view('employee::schedules.shift.index', compact('columns', 'footers', 'roles', 'timeSheet_rules', 'establishments'));
    }


    public function getShift(Request $request)
    {
        $establishments = Establishment::all()->pluck('id', 'name')->toArray();
        $day_times = ShiftService::getStartEndDayTime();
        return response()->json(['data' => ['establishments' => $establishments, 'start_of_day' => $day_times['start_of_day'] ? $day_times['start_of_day']->format('H:i') : '-', 'end_of_day' => $day_times['end_of_day'] ? $day_times['end_of_day']->format('H:i') : '-']]);
    }


    public function store(StoreShiftRequest $request)
    {
        DB::transaction(function () use ($request) {
            $employee_id = $request->employee_id;
            $startOfWeek = Carbon::parse($request->date)->startOfWeek()->format('Y-m-d');
            $endOfWeek = Carbon::parse($request->date)->endOfWeek()->format('Y-m-d');
            $schedule_id = Schedule::updateOrCreate(['start_date' => $startOfWeek], [
                'end_date' => $endOfWeek
            ])->id;
            $ids = [];
            foreach ($request->shift_repeater as $key => $item) {
                $startTime = $request->date . ' ' . $item['startTime'];
                $endTime = $request->date . ' ' . $item['endTime'];
                $shift_id = $item['shift_id'];
                $end_status = $item['end_status'];
                $break_duration = $end_status === 'break' ? (Carbon::parse($item['endTime'])->diffInMinutes(Carbon::parse($request->shift_repeater[$key + 1]['startTime']))) : null;
                $ids[] = Shift::updateOrCreate(['id' => $shift_id], [
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'employee_id' => $employee_id,
                    'schedule_id' => $schedule_id,
                    'establishment_id' => $item['establishment'],
                    'break_duration' => $break_duration
                ])->id;
            }
            Shift::where('employee_id', $employee_id)->whereDate('startTime', $request->date)->whereNotIn('id', $ids)->delete();
        });

        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function copy_shifts(Request $request)
    {
        $copy_from_start_date = Carbon::createFromFormat('d/m/Y', $request->copy_from_start_date);
        $copy_from_end_date = Carbon::createFromFormat('d/m/Y', $request->copy_from_end_date);
        $copy_to_start_date = Carbon::createFromFormat('d/m/Y', $request->start_date);
        $copy_to_end_date = Carbon::createFromFormat('d/m/Y', $request->end_date);
        $schedule_id = Schedule::firstOrCreate(['start_date' => $copy_from_start_date->format('Y-m-d'), 'end_date' => $copy_from_end_date->format('Y-m-d')])->id;
        $shifts = Shift::where('schedule_id', $schedule_id);

        $new_schedule_id = Schedule::firstOrCreate(['start_date' => $copy_to_start_date->format('Y-m-d'), 'end_date' => $copy_to_end_date->format('Y-m-d')])->id;
        $shiftsToDelete = Shift::where('schedule_id', $new_schedule_id);
        if (!empty($request->employee_ids)) {
            $shifts->whereIn('employee_id', $request->employee_ids);
            $shiftsToDelete->whereIn('employee_id', $request->employee_ids)?->delete();
        } else {
            $shiftsToDelete?->delete();
        }
        $diff = $copy_from_start_date->diffInDays($copy_to_start_date);

        if ($shifts->get()->isNotEmpty()) {
            foreach ($shifts->get() as $shift) {
                $new_shift = $shift->replicate();
                $new_shift->startTime = Carbon::parse($shift->startTime)->addDays($diff);
                $new_shift->endTime = Carbon::parse($shift->endTime)->addDays($diff);
                $new_shift->schedule_id = $new_schedule_id;
                $new_shift->save();
            }
        }
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

}
