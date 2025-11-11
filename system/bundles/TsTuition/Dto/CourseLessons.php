<?php

namespace TsTuition\Dto;

use TsTuition\Enums\LessonsUnit;

class CourseLessons
{
	public function __construct(
		private array $lessons,
		private LessonsUnit $unit,
		private bool $fix = true
	) {}

	public function getLessons(): array
	{
		return $this->lessons;
	}

	public function getUnit(): LessonsUnit
	{
		return $this->unit;
	}

	public function isFix(): bool
	{
		return $this->fix;
	}
}