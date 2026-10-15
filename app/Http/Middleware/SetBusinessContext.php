<?php

namespace App\Http\Middleware;

use App\Services\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetBusinessContext
{
    public function __construct(private BusinessContext $businessContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->user()?->business_id;

        abort_if($businessId === null, 403, 'Usuário sem empresa vinculada.');

        return $this->businessContext->run(
            (int) $businessId,
            fn (): Response => $next($request),
        );
    }
}
