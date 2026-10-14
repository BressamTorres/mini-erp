<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ExternalOrderConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCatalogSaleRequest;
use App\Services\CatalogSaleService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CatalogSaleController extends Controller
{
    public function __construct(private CatalogSaleService $sales) {}

    public function store(StoreCatalogSaleRequest $request): JsonResponse
    {
        try {
            $result = $this->sales->create($request->payload());
        } catch (ExternalOrderConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        $sale = $result['sale'];

        return response()->json([
            'id' => $sale->id,
            'number' => $sale->formattedNumber(),
            'total' => $sale->total,
            'created' => $result['created'],
        ], $result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
