<?php

namespace Admin\Interfaces\RouterAction;

use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Interfaces\RouterAction;

interface StoreableRouterAction extends RouterAction
{
	public function storeable(string $key, string $icon, string|array $text): static;

	public function isStoreable(): bool;

	public function getStoreableKey(): ?string;

	public function getStorableIcon(): ?string;

	public function getStoreableText(): string|array;

	public function getStoreablePayload(Instance $admin): array;

	public function source(string $class, string $key): static;

	public function getSource(): ?array;

	public function hasSource(): bool;
}