<?php

namespace Admin\Interfaces\Component;

use Admin\Dto\Component\Parameters;

interface HasParameters
{
	public function setParameters(Parameters $parameters): static;

	public function getParameterValues(): ?Parameters;

	public function validate(array $values): array;
}