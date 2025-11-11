<?php

class Ext_TS_Inquiry_Gui2 extends Ext_Thebing_Gui2 {

	public function executeGuiCreatedHook() {

		parent::executeGuiCreatedHook();

		// Wenn es Inboxen gibt, dann ID der Inbox zum Recht hinzufügen
		$iInbox = (int)$this->getOption('inbox_id');

		if($iInbox > 0) {
			$this->access .= '_'.$iInbox;
		}

	}

}