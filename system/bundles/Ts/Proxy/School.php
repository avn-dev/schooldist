<?php

namespace Ts\Proxy;

use Illuminate\Support\Str;

class School extends \Ts\Proxy\AbstractProxy {
	
	protected $sEntityClass = 'Ext_Thebing_School';
	
	public function getSlug() {
		return Str::slug($this->oEntity->getName());
	}
	
}
