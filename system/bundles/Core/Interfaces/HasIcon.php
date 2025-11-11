<?php

namespace Core\Interfaces;

interface HasIcon
{
	public function icon(string $icon): static;
	
	public function getIcon(): ?string;
}