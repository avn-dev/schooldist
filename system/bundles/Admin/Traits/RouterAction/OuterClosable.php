<?php

namespace Admin\Traits\RouterAction;

trait OuterClosable
{
	use Closable {
		closable as protected parentClosable;
	}

	protected bool $outerClosable = true;

	public function closable(bool $payload): static
	{
		$this->outerClosable($payload);
		return $this->parentClosable($payload);
	}

	public function outerClosable(bool $payload): static
	{
		if ($this->closable) {
			$this->outerClosable = $payload;
		}
		return $this;
	}
}