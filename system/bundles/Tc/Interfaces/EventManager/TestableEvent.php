<?php

namespace Tc\Interfaces\EventManager;

use Tc\Gui2\Data\EventManagementData;
use Tc\Interfaces\Events\Settings;

interface TestableEvent
{
	public static function buildTestEvent(Settings $settings): static;

	public static function prepareTestingGui2Dialog(\Ext_Gui2_Dialog $dialog, EventManagementData $data): void;
}