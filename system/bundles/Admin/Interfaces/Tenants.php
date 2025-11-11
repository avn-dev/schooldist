<?php

namespace Admin\Interfaces;

use Admin\Dto\TenantDto;
use Admin\Http\InterfaceResponse;
use Illuminate\Support\Collection;

interface Tenants
{
	public function getOptions(): Collection;

	public function switchTenant(TenantDto $tenant): bool|InterfaceResponse;
}