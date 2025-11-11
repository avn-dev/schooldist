<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

interface SystemButton
{
	public function getKey(): string;

	public function getIcon(): string;

	public function getTitle(): string;

	public function hasRight(\Access $access): bool;

	public function getOptions(): array;

	public function handle(Request $request): bool|InterfaceResponse;

	public function isActive(): bool;

}
