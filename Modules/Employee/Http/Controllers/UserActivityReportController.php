<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\UserActivityLog;
use Modules\Employee\Services\UserActivityLogger;
use Modules\Employee\Services\UserActivityPresenter;
use Yajra\DataTables\Facades\DataTables;

class UserActivityReportController extends Controller
{
    public function __construct(protected UserActivityPresenter $presenter) {}

    public function index(): View
    {
        $stats = [
            'dashboard_users' => Employee::query()->where('ems_access', true)->count(),
            'events_today' => UserActivityLog::query()->whereDate('created_at', today())->count(),
            'events_week' => UserActivityLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'last_log_at' => UserActivityLog::query()->max('created_at'),
        ];

        return view('employee::user-activity.index', [
            'stats' => $stats,
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
            ->addColumn('user_cell', function (Employee $row) {
                $name = e((string) ($row->translated_name ?? $row->name ?? '—'));
                $initial = mb_strtoupper(mb_substr((string) ($row->name ?? '?'), 0, 1));

                return '<div class="d-flex align-items-center gap-3">'
                    .'<span class="symbol symbol-40px symbol-circle bg-light-primary text-primary fw-bold">'.$initial.'</span>'
                    .'<div><div class="fw-semibold text-gray-900">'.$name.'</div>'
                    .'<div class="text-muted fs-7">'.e((string) $row->user_name).'</div></div></div>';
            })
            ->addColumn('last_activity_at_fmt', function (Employee $row) {
                if (empty($row->last_activity_at)) {
                    return '<span class="text-muted">'.e(__('employee::activity.no_activity_yet')).'</span>';
                }

                $at = \Carbon\Carbon::parse($row->last_activity_at);
                $when = $this->presenter->formatWhen($at);

                return '<div class="text-gray-900">'.$when['absolute'].'</div>'
                    .'<div class="text-muted fs-7">'.$when['relative'].'</div>';
            })
            ->addColumn('activity_count_fmt', function (Employee $row) {
                $count = (int) ($row->activity_count ?? 0);

                return '<span class="badge badge-light-primary fs-7">'.number_format($count).' '
                    .e(__('employee::activity.events_short'))
                    .'</span>';
            })
            ->addColumn('action', function (Employee $row) {
                $url = route('activity.show', ['employee' => $row->id]);

                return '<a href="'.e($url).'" class="btn btn-sm btn-primary">'
                    .e(__('employee::activity.open_timeline'))
                    .'</a>';
            })
            ->rawColumns(['user_cell', 'last_activity_at_fmt', 'activity_count_fmt', 'action'])
            ->make(true);
    }

    public function show(Employee $employee): View
    {
        $summary = [
            'total' => UserActivityLog::query()->where('employee_id', $employee->id)->count(),
            'today' => UserActivityLog::query()->where('employee_id', $employee->id)->whereDate('created_at', today())->count(),
            'deletions' => UserActivityLog::query()->where('employee_id', $employee->id)->where('event', UserActivityLogger::EVENT_DELETED)->count(),
        ];

        return view('employee::user-activity.show', [
            'employee' => $employee,
            'summary' => $summary,
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
            ->addColumn('when_cell', function (UserActivityLog $row) {
                $when = $this->presenter->formatWhen($row->created_at);

                return '<div class="fw-semibold text-gray-900">'.$when['absolute'].'</div>'
                    .'<div class="text-muted fs-7">'.$when['relative'].'</div>';
            })
            ->addColumn('event_badge', fn (UserActivityLog $row) => $this->eventBadge($row->event))
            ->addColumn('headline_cell', function (UserActivityLog $row) {
                return '<div class="text-gray-900 fw-semibold">'.e($this->presenter->headline($row)).'</div>';
            })
            ->addColumn('area_cell', function (UserActivityLog $row) {
                return '<span class="badge badge-light-secondary">'.e($this->presenter->areaLabel($row)).'</span>';
            })
            ->addColumn('action', function (UserActivityLog $row) {
                $url = route('activity.entry', ['log' => $row->id]);

                return '<button type="button" class="btn btn-sm btn-light-primary js-activity-detail" data-url="'.e($url).'">'
                    .e(__('employee::activity.details'))
                    .'</button>';
            })
            ->rawColumns(['when_cell', 'event_badge', 'headline_cell', 'area_cell', 'action'])
            ->make(true);
    }

    public function activityEntry(UserActivityLog $log): JsonResponse
    {
        $when = $this->presenter->formatWhen($log->created_at);

        return response()->json([
            'headline' => $this->presenter->headline($log),
            'event_label' => $this->presenter->eventLabel($log->event),
            'area_label' => $this->presenter->areaLabel($log),
            'when' => $when['absolute'],
            'when_relative' => $when['relative'],
            'sections' => $this->presenter->detailSections($log),
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

        $label = $this->presenter->eventLabel($event);

        return '<span class="badge '.$class.'">'.e($label).'</span>';
    }
}
