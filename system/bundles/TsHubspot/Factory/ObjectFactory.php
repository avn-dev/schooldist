<?php

namespace TsHubspot\Factory;

class ObjectFactory {

	private $entity;

	public function __construct($entity) {
		$this->entity = $entity;
	}

	public function getService() {

		if ($this->entity instanceof \Ext_Thebing_Agency) {
			return \TsHubspot\Service\Agency::class;
		} elseif ($this->entity instanceof \Ext_Thebing_Agency_Contact) {
			return \TsHubspot\Service\AgencyContact::class;
		} elseif ($this->entity instanceof \Ext_TS_Inquiry) {
			return \TsHubspot\Service\Inquiry::class;
		}
	}

}