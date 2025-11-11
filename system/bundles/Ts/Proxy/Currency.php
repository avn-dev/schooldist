<?php

namespace Ts\Proxy;

class Currency extends \Ts\Proxy\AbstractProxy {
	
	protected $sEntityClass = 'Ext_Thebing_Currency';

	/**
	 * return Ext_Thebing_Currency $oEntity->getSign()
	 *
	 * @return string
	 */
	public function getSign(): string {
		return $this->oEntity->getSign();
	}
}