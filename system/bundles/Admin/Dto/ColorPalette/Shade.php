<?php

namespace Admin\Dto\ColorPalette;

use Admin\Dto\Color;

class Shade
{
	public function __construct(
		private int $base,
		private Color $color,
	) {}

	public function getBase(): int
	{
		return $this->base;
	}

	public function getColor(): Color
	{
		return $this->color;
	}

}