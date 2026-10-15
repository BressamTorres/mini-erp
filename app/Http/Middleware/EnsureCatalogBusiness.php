<?php

namespace App\Http\Middleware;

use App\Services\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCatalogBusiness
{
    public function __construct(private BusinessContext $businessContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->header('X-Business-ID');

        if (
            $businessId !== null
            && (! ctype_digit($businessId) || (int) $businessId !== $this->businessContext->id())
        ) {
            abort(403, 'A empresa informada não corresponde ao token do Catálogo.');
        }

        return $next($request);
    }
}
