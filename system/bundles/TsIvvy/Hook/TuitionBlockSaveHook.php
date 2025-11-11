<?php

namespace TsIvvy\Hook;

use Core\Service\Hook\AbstractHook;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Service\Synchronization;

class TuitionBlockSaveHook extends AbstractHook {

	public function run(\Ext_Thebing_School_Tuition_Block $block) {

		if(!ExternalApp::isActive() || $block->class_id <= 0) {
			return;
		}

		//Synchronization::syncEntityToIvvy($block);
		Synchronization::writeEntityToStack($block);

	}

}
