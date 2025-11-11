<?php

namespace Admin\Traits\RouterAction;

use Admin\Enums\Size;

trait Resizeable
{
	protected Size $size = Size::MEDIUM;

	public function size(Size $size): static
	{
		$this->size = $size;
		return $this;
	}
}