<?php

namespace TsStudentApp\Properties;

interface Property
{
	public function property(): string;

	public function data(): mixed;

	public function destroy(): bool;
}