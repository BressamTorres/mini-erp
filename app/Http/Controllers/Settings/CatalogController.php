<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\CatalogUrlService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function show(Request $request, CatalogUrlService $catalogUrls): View
    {
        $business = $request->user()->business()->firstOrFail();
        $catalogUrl = $catalogUrls->forBusiness($business);

        return view('settings.catalog', [
            'business' => $business,
            'catalogUrl' => $catalogUrl,
            'whatsAppUrl' => $catalogUrls->whatsAppUrl($catalogUrl),
            'qrCodeUrl' => $catalogUrls->qrCodeUrl($catalogUrl),
        ]);
    }
}
