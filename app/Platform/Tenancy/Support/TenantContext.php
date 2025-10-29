<?php

namespace App\Platform\Tenancy\Support;

use App\Platform\Tenancy\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function ensureTenant(): Tenant
    {
        if (!$this->tenant) {
            throw new \RuntimeException('Tenant context has not been set.');
        }

        return $this->tenant;
    }
}

