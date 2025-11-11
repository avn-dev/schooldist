<?php

namespace Ts\Proxy;

class Season extends \Ts\Proxy\AbstractProxy {
	
	protected $sEntityClass = 'Ext_Thebing_Marketing_Saison';
	
	public function getFrom() {
		return new \Carbon\Carbon($this->oEntity->valid_from);
	}
	
	public function getUntil() {
		return new \Carbon\Carbon($this->oEntity->valid_until);
	}
	
}