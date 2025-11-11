<?php

namespace Admin\Traits\RouterAction;

trait Closable
{
	protected bool $closable = true;

	public function closable(bool $payload): static
	{
		$this->closable = $payload;
		return $this;
	}
}