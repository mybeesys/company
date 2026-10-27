<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\UserActivityLog;
use Modules\Employee\Services\UserActivityLogger;
use Yajra\DataTables\Facades\DataTables;

class UserActivityReportController extends Controller
{
    public function index(): View
    {
        $employees = Employee::query()
            ->where('ems_access', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'email', 'user_name']);

        return view('employee::user-activity.index', [
            'employees' => $employees,
            'eventTypes' => $this->eventTypeOptions(),
        ]);
    }

    public function usersData(Request $request): JsonResponse
    {
        $query = Employee::query()
            ->where('ems_access', true)
            ->select('emp_employees.*')
            ->selectSub(
                UserActivityLog::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('emp_activity_logs.employee_id', 'emp_employees.id'),
                'activity_count'
            )
            ->selectSub(
                UserActivityLog::query()
                    ->select('created_at')
                    ->whereColumn('emp_activity_logs.employee_id', 'emp_employees.id')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'last_activity_at'
            );

        return DataTables::of($query)
            ->addColumn('display_name', function (Employee $row) {
                return e((string) ($row->translated_name ?? $row->name ?? '—'));
            })
            ->addColumn('last_activity_at_fmt', function (Employee $row) {
                if (empty($row->last_activity_at)) {
                    return '<span class="text-muted">—</span>';
                }

                return e(\Carbon\Carbon::parse($row->last_activity_at)->format('Y-m-d H:i'));
            })
            ->addColumn('activity_count_fmt', function (Employee $row) {
                $count = (int) ($row->activity_count ?? 0);

                return '<span class="badge badge-light-primary">'.number_format($count).'</span>';
            })
            ->addColumn('action', function (Employee $row) {
                $url = route('activity.show', ['employee' => $row->id]);

                return '<a href="'.e($url).'" class="btn btn-sm btn-light-primary">'
                    .e(__('employee::activity.open_timeline'))
                    .'</a>';
            })
            ->rawColumns(['last_activity_at_fmt', 'activity_count_fmt', 'action'])
            ->make(true);
    }

    public function show(Employee $employee): View
    {
        return view('employee::user-activity.show', [
            'employee' => $employee,
            'eventTypes' => $this->eventTypeOptions(),
        ]);
    }

    public function activityData(Request $request, Employee $employee): JsonResponse
    {
        $query = UserActivityLog::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id');

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        return DataTables::of($query)
            ->addColumn('created_at_fmt', fn (UserActivityLog $row) => $row->created_at?->format('Y-m-d H:i:s') ?? '')
            ->addColumn('event_badge', function (UserActivityLog $row) {
                return $this->eventBadge($row->event);
            })
            ->addColumn('description_fmt', fn (UserActivityLog $row) => e($row->description ?? '—'))
            ->addColumn('context', function (UserActivityLog $row) {
                $parts = array_filter([
                    $row->http_method,
                    $row->route_name,
                    $row->ip_address,
                ]);

                return e(implode(' · ', $parts));
            })
            ->addColumn('action', function (UserActivityLog $row) {
                $url = route('activity.entry', ['log' => $row->id]);

                return '<button type="button" class="btn btn-sm btn-light js-activity-detail" data-url="'.e($url).'">'
                    .e(__('employee::activity.details'))
                    .'</button>';
            })
            ->rawColumns(['event_badge', 'action'])
            ->make(true);
    }

    public function activityEntry(UserActivityLog $log): JsonResponse
    {
        return response()->json([
            'id' => $log->id,
            'event' => $log->event,
            'description' => $log->description,
            'created_at' => $log->created_at?->toIso8601String(),
            'route_name' => $log->route_name,
            'http_method' => $log->http_method,
            'url' => $log->url,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'status_code' => $log->status_code,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'properties' => $log->properties,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function eventTypeOptions(): array
    {
        return [
            '' => __('employee::activity.all_events'),
            UserActivityLogger::EVENT_AUTH => __('employee::activity.event_auth'),
            UserActivityLogger::EVENT_CREATED => __('employee::activity.event_created'),
            UserActivityLogger::EVENT_UPDATED => __('employee::activity.event_updated'),
            UserActivityLogger::EVENT_DELETED => __('employee::activity.event_deleted'),
            UserActivityLogger::EVENT_VIEWED => __('employee::activity.event_viewed'),
            UserActivityLogger::EVENT_EXPORT => __('employee::activity.event_export'),
            UserActivityLogger::EVENT_OTHER => __('employee::activity.event_other'),
        ];
    }

    private function eventBadge(string $event): string
    {
        $map = [
            UserActivityLogger::EVENT_AUTH => 'badge-light-info',
            UserActivityLogger::EVENT_CREATED => 'badge-light-success',
            UserActivityLogger::EVENT_UPDATED => 'badge-light-warning',
            UserActivityLogger::EVENT_DELETED => 'badge-light-danger',
            UserActivityLogger::EVENT_VIEWED => 'badge-light-secondary',
            UserActivityLogger::EVENT_EXPORT => 'badge-light-primary',
        ];
        $class = $map[$event] ?? 'badge-light';

        $label = $this->eventTypeOptions()[$event] ?? $event;

        return '<span class="badge '.$class.'">'.e($label).'</span>';
    }
}
