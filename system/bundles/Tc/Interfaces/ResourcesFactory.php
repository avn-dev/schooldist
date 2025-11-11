<?php

namespace Tc\Interfaces;

use Illuminate\Support\Collection;

interface ResourcesFactory
{
	public function getPaymentMethods(): Collection;
}