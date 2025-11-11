<?php

namespace Core\Interfaces;

interface Optionable
{
	public function getOptionValue(): string|int;

	public function getOptionText(): string;
}