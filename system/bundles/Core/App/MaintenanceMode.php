<?php

namespace Core\App;

use Illuminate\Contracts\Foundation\MaintenanceMode as LaravelMaintenanceMode;

class MaintenanceMode implements LaravelMaintenanceMode
{
	public function activate(array $payload): void
	{
		// TODO: Implement activate() method.
	}

	public function deactivate(): void
	{
		// TODO: Implement deactivate() method.
	}

	public function active(): bool
	{
		return false;
	}

	public function data(): array
	{
		// TODO: Implement data() method.
	}
}