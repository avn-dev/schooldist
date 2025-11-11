<?php

namespace Admin\Interfaces\RouterAction;

use Admin\Dto\Component\Parameters;
use Admin\Instance;
use Admin\Interfaces\RouterAction;

interface StorableRouterAction extends RouterAction
{
	public function storable(string $key, string $icon, string|array $text): static;

	public function isStorable(): bool;

	public function getStorableKey(): ?string;

	public function getStorableIcon(): ?string;

	public function getStorableText(): string|array;

	public function getStorablePayload(Instance $admin): array;

	public function getStorableParameters(Instance $admin): ?Parameters;

	public function source(string $class, string $key): static;

	public function getSource(): ?array;

	public function hasSource(): bool;
}