<?php

class Ext_TC_System_Checks_System_Tabs_ApiTokenTabs extends Ext_TC_System_Checks_System_Tabs_AbstractMoved {

	protected function getMovedTabs(): array {

		return [
			'/admin/extensions/tc/admin/wdmvc_token.html' => '/gui2/page/TcApi_api_token'
		];

	}
}
