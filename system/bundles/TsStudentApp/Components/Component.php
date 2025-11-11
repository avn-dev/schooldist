<?php

namespace TsStudentApp\Components;

interface Component
{
	public function getKey(): string;

	public function toArray(): array;
}