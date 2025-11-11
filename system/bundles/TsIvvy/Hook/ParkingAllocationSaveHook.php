<?php

namespace TsIvvy\Hook;

use Core\Service\Hook\AbstractHook;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Service\Synchronization;

class ParkingAllocationSaveHook extends AbstractHook {

	public function run(\Ext_Thebing_Accommodation_Allocation $allocation) {

		if(!ExternalApp::isActive() || !$allocation->isParking()) {
			return;
		}

		//Synchronization::syncEntityToIvvy($allocation);
		Synchronization::writeEntityToStack($allocation);

	}

}
