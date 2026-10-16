<?php

namespace App\View\Components;

use App\Models\Business;
use App\Services\BusinessContext;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public function __construct(private BusinessContext $businessContext) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app', [
            'activeBusiness' => Business::query()->findOrFail($this->businessContext->id()),
        ]);
    }
}
