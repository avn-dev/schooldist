<?php

namespace Admin\Traits\RouterAction;

trait Moveable
{
	protected bool $moveable = true;

	public function moveable(bool $moveable): static
	{
		$this->moveable = $moveable;
		return $this;
	}
}