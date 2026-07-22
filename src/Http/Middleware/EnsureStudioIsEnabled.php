<?php

namespace Imran\BlueprintStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudioIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('blueprint-studio.enabled', true)) {
            abort(404);
        }

        $allowed = config('blueprint-studio.allowed_environments', ['local']);

        if (! app()->environment($allowed) && ! config('blueprint-studio.force_enable', false)) {
            abort(404, 'Blueprint Studio is disabled in this environment.');
        }

        return $next($request);
    }
}
