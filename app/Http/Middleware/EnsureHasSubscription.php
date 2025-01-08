<?php

namespace App\Http\Middleware;

use Closure;
use DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        if (!$company->subscribed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('responses.no_subscription_found')
                ]);
            }
            $domain = tenant()->domains->first()->domain;
            $protocol = request()->secure() ? 'https://' : 'http://';

            return redirect(str_replace(tenant('id') . '.', $protocol, $domain));
        }
        return $next($request);
    }
}
