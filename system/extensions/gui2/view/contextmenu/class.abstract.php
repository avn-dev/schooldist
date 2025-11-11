<?php

/**
 * Kontextmenü-Klasse zum Ableiten
 * 
 * Baut einen einzelnen Eintrag des Kontextmenüs zu dieser ID
 */
abstract class Ext_Gui2_View_ContextMenu_Abstract implements Ext_Gui2_View_ContextMenu_Interface {
	
	public function getOptions($aResultData) {

		$aOptions = array(
			array(
				'key' => '',
				'name' => ''
			)
		);

		return $aOptions;

	}

}
