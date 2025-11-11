<?php

namespace Cms\Helper;

class Trash {
	
	public static function dump(\WDBasic $oEntity, $iPageId=null, $iParentId=null) {

		$oEntity->active = 2;
		$oEntity->save();

		$iCurrentUserId = 0;
		
		$oAccess = \Access::getInstance();
		if($oAccess instanceof \Access_Backend) {
			$iCurrentUserId = $oAccess->id;
		}

		$aTrash = [
			'trash_id' => $oEntity->id,
			'page_id' => $iPageId,
			'parent_id' => $iParentId,
			'tablename' => $oEntity->getTableName(),
			'title' => (string)$oEntity,
			'user' => $iCurrentUserId
		];
		
		\DB::insertData('system_trash', $aTrash);
		
		$sText = 'Aus "'.$oEntity->getTableName().'" wurde Element "'.(string)$oEntity.'" in den Papierkorb verschoben';
		\Log::enterLog($iPageId, $sText);
		
		return true;
	}
	
}
