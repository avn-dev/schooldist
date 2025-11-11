<?php

namespace Gui2\Service\InfoIcon;

class Hashing {
	
	public static function encode(\Ext_Gui2 $oGui2, $sDialogId, $sField) {
		return implode('.', [
			$oGui2->hash,
			$sDialogId,
			$sField
		]);
	}
	
	public static function decode($sKey) {
				
		$aExplode = explode('.', $sKey);
		
		return [
			'gui_hash' => $aExplode[0],
			'dialog_id' => $aExplode[1],
			'field' => $aExplode[2],
		];
	}
	
}

