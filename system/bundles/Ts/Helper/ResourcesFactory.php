<?php

namespace Ts\Helper;

use Illuminate\Support\Collection;
use Tc\Interfaces\ResourcesFactory as Factory;

class ResourcesFactory implements Factory
{
	public function getPaymentMethods(): Collection
	{
		return \Ext_Thebing_Admin_Payment::query()
			->orderBy('position')
			->get();
	}
}