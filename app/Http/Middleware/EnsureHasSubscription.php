<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
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
        $company = Company::find(get_company_id());
        if (!$company->subscription) {
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
