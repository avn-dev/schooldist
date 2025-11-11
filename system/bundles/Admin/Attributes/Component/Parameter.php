<?php

namespace Admin\Attributes\Component;

#[\Attribute]
class Parameter
{
	public function __construct(
		public readonly string $name,
		public readonly array $rules = [], // TODO
	) {}
}