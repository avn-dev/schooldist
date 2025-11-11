<?php

namespace Ts\Hook;

use Core\Service\Hook\AbstractHook;
use Ts\Handler\System\Buttons;

class ControlSidebarButtons extends AbstractHook {

	public function run(&$buttonList)
	{
		$buttonList[] = Buttons\SchoolSelect::class;
	}

}
