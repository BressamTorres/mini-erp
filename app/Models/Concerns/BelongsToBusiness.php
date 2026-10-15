<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Services\BusinessContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function (Model $model): void {
            $businessId = app(BusinessContext::class)->id();
            $assignedBusinessId = $model->getAttribute('business_id');

            if ($assignedBusinessId !== null && (int) $assignedBusinessId !== $businessId) {
                throw new LogicException('Não é permitido criar um registro para outra empresa.');
            }

            $model->setAttribute('business_id', $businessId);
        });
    }

    /** @return BelongsTo<Business, $this> */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
