<?php

namespace App\Services;

use App\Models\Business;
use Closure;
use LogicException;
use Spatie\Permission\PermissionRegistrar;

class BusinessContext
{
    private ?int $businessId = null;

    public function __construct(private PermissionRegistrar $permissions) {}

    public function set(Business|int $business): void
    {
        $this->businessId = $business instanceof Business
            ? (int) $business->getKey()
            : $business;

        $this->permissions->setPermissionsTeamId($this->businessId);
    }

    public function clear(): void
    {
        $this->businessId = null;
        $this->permissions->setPermissionsTeamId(null);
    }

    public function has(): bool
    {
        return $this->businessId !== null;
    }

    public function id(): int
    {
        if ($this->businessId === null) {
            throw new LogicException('O contexto da empresa não foi definido.');
        }

        return $this->businessId;
    }

    public function run(Business|int $business, Closure $callback): mixed
    {
        $previousBusinessId = $this->businessId;
        $this->set($business);

        try {
            return $callback();
        } finally {
            if ($previousBusinessId === null) {
                $this->clear();
            } else {
                $this->set($previousBusinessId);
            }
        }
    }
}
