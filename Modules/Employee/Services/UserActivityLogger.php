<?php

namespace Modules\Employee\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\UserActivityLog;

class UserActivityLogger
{
    public const EVENT_AUTH = 'auth';

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_VIEWED = 'viewed';

    public const EVENT_EXPORT = 'export';

    public const EVENT_OTHER = 'other';

    public function record(
        string $event,
        ?Employee $employee = null,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $logName = null,
        ?Request $request = null,
        ?int $statusCode = null,
    ): ?UserActivityLog {
        if (! config('employee.activity_log.enabled', true)) {
            return null;
        }

        $employee ??= auth()->user();

        if (! $employee instanceof Employee) {
            return null;
        }

        $req = $request ?? (app()->runningInConsole() ? null : request());

        $row = [
            'employee_id' => $employee->id,
            'event' => $event,
            'log_name' => $logName,
            'description' => Str::limit($description ?? '', 512, ''),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'route_name' => $req?->route()?->getName(),
            'http_method' => $req?->method(),
            'url' => $req ? Str::limit($req->fullUrl(), 2048, '') : null,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
            'status_code' => $statusCode,
            'properties' => $properties === [] ? null : $properties,
            'created_at' => now(),
        ];

        return UserActivityLog::query()->create($row);
    }

    public function logFromRequest(Request $request, int $statusCode): void
    {
        if (! $this->shouldLogRequest($request)) {
            return;
        }

        $employee = auth()->user();
        if (! $employee instanceof Employee) {
            return;
        }

        $event = $this->inferEvent($request);
        $description = app(UserActivityPresenter::class)->describeRequest($request, $event);
        $properties = [
            'input' => $this->sanitizedInput($request),
            'route_parameters' => $this->routeParameterSummary($request),
            'subjects' => $this->snapshotRouteSubjects($request),
        ];

        $subject = $this->firstRouteModel($request);

        $this->record(
            event: $event,
            employee: $employee,
            description: $description,
            subject: $subject,
            properties: $properties,
            logName: $this->inferLogName($request),
            request: $request,
            statusCode: $statusCode,
        );
    }

    public function logAuth(string $action, ?Employee $employee = null, array $context = []): void
    {
        $labels = [
            'login' => __('employee::activity.auth_login'),
            'logout' => __('employee::activity.auth_logout'),
            'tenant_switch' => __('employee::activity.auth_tenant_switch'),
            'login_failed' => __('employee::activity.auth_login_failed'),
        ];

        $this->record(
            event: self::EVENT_AUTH,
            employee: $employee,
            description: $labels[$action] ?? $action,
            properties: array_merge(['auth_action' => $action], $context),
            logName: 'auth',
        );
    }

    private function shouldLogRequest(Request $request): bool
    {
        if (! config('employee.activity_log.enabled', true)) {
            return false;
        }

        $path = ltrim($request->path(), '/');

        foreach (config('employee.activity_log.excluded_path_prefixes', []) as $prefix) {
            if ($prefix !== '' && str_starts_with($path, ltrim($prefix, '/'))) {
                return false;
            }
        }

        $routeName = (string) $request->route()?->getName();
        foreach (config('employee.activity_log.excluded_route_patterns', []) as $pattern) {
            if ($routeName !== '' && preg_match($pattern, $routeName)) {
                return false;
            }
        }

        $method = strtoupper($request->method());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if ($method !== 'GET') {
            return false;
        }

        foreach (config('employee.activity_log.excluded_get_prefixes', []) as $prefix) {
            if ($prefix !== '' && str_starts_with($path, ltrim($prefix, '/'))) {
                return false;
            }
        }

        if ($request->ajax() && str_contains($path, 'datatable')) {
            return false;
        }

        $routeNameLower = strtolower($routeName);
        $pathLower = strtolower($path);

        foreach (['export', 'print', 'pdf', 'download', 'destroy', 'delete', 'force-delete', 'restore'] as $needle) {
            if (str_contains($routeNameLower, $needle) || str_contains($pathLower, $needle)) {
                return true;
            }
        }

        return ! $request->ajax();
    }

    private function inferEvent(Request $request): string
    {
        $method = strtoupper($request->method());
        $routeName = strtolower((string) $request->route()?->getName());
        $path = strtolower($request->path());

        if (str_contains($routeName, 'export') || str_contains($path, 'export')) {
            return self::EVENT_EXPORT;
        }

        if ($method === 'DELETE' || str_contains($routeName, 'delete') || str_contains($routeName, 'destroy')) {
            return self::EVENT_DELETED;
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if (str_contains($routeName, '.store') || str_contains($routeName, 'create')) {
                return self::EVENT_CREATED;
            }

            return self::EVENT_UPDATED;
        }

        return self::EVENT_VIEWED;
    }

    private function inferLogName(Request $request): ?string
    {
        $segments = explode('/', $request->path());

        return $segments[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizedInput(Request $request): array
    {
        $except = config('employee.activity_log.redact_input_keys', []);
        $data = $request->except($except);

        return $this->truncateDeep($data, 8, 2000);
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameterSummary(Request $request): array
    {
        $out = [];
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if ($value instanceof Model) {
                $out[$key] = class_basename($value).':'.$value->getKey();
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function snapshotRouteSubjects(Request $request): ?array
    {
        $snapshots = [];
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if ($value instanceof Model) {
                $snapshots[$key] = $this->modelSnapshot($value);
            }
        }

        return $snapshots === [] ? null : $snapshots;
    }

    /**
     * @return array<string, mixed>
     */
    private function modelSnapshot(Model $model): array
    {
        $attributes = $model->attributesToArray();
        foreach (config('employee.activity_log.redact_input_keys', []) as $key) {
            unset($attributes[$key]);
        }

        return $this->truncateDeep($attributes, 6, 1500);
    }

    private function firstRouteModel(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $value) {
            if ($value instanceof Model) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function truncateDeep(array $data, int $maxDepth, int $maxJsonLength): array
    {
        $walk = function (mixed $value, int $depth) use (&$walk, $maxDepth): mixed {
            if ($depth > $maxDepth) {
                return '[truncated]';
            }
            if (is_array($value)) {
                $out = [];
                foreach ($value as $k => $v) {
                    $out[$k] = $walk($v, $depth + 1);
                }

                return $out;
            }
            if (is_string($value) && strlen($value) > 500) {
                return Str::limit($value, 500, '…');
            }

            return $value;
        };

        $result = $walk($data, 0);
        $encoded = json_encode($result);
        if ($encoded !== false && strlen($encoded) > $maxJsonLength) {
            return ['_note' => 'payload_truncated', 'preview' => Str::limit($encoded, $maxJsonLength, '…')];
        }

        return is_array($result) ? $result : [];
    }
}
