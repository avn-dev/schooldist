<?php

class Ext_TS_System_Checks_System_Tabs_NumberrangesTab extends Ext_TC_System_Checks_System_Tabs_AbstractMoved {

	protected function getMovedTabs(): array {

		return [
			'/gui2/page/Tc_numberranges' => '/gui2/page/Ts_numberranges'
		];
	}
}
