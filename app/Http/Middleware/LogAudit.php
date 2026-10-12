<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes one audit_logs row per write request (POST/PUT/PATCH/DELETE).
 *
 * Route parameters may be Eloquent models (route model binding) — e.g.
 * /projects/{project} resolves to a Project instance, not an ID — so the
 * auditable id is taken via getKey() when the parameter is a Model.
 */
class LogAudit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => strtolower($request->method()),
                'auditable_type' => $request->route()?->getName() ?? $request->path(),
                'auditable_id' => $this->resolveAuditableId($request),
                // input() excludes uploaded files, which don't serialize to JSON
                'new_values' => Arr::except($request->input(), [
                    'password', 'password_confirmation', '_token', '_method',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }

    /**
     * First route parameter that is a bound model (its key) or numeric.
     */
    private function resolveAuditableId(Request $request): int
    {
        foreach ($request->route()?->parameters() ?? [] as $value) {
            if ($value instanceof Model) {
                return (int) $value->getKey();
            }
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return 0;
    }
}
