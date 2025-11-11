<?php

class Ext_TC_Communication_EmailAccount_Gui2_Selection_Folder extends Ext_Gui2_View_Selection_Abstract {
	
	private static $aCache = [];


	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		/* @var Ext_TC_Communication_Imap $oWDBasic */
		$bImap = (bool) $oWDBasic->imap;
		$aFolders = [];

		if(
			$bImap &&
			!empty($oWDBasic->imap_host) &&
			!empty($oWDBasic->imap_auth) &&
			(
				($oWDBasic->imap_auth === 'oauth2' && !empty($oWDBasic->oauth2_data)) ||
				($oWDBasic->imap_auth === 'password' && !empty($oWDBasic->imap_user) && !empty($oWDBasic->imap_pass))
			)
		) {

			$sCacheKey = md5($oWDBasic->email);

			if(
				!isset(self::$aCache[$sCacheKey]) ||
				empty(self::$aCache[$sCacheKey])
			) {
				try {

					$aFolders = $oWDBasic->getImapClient()->getFolders(false)->pluck('path', 'path')->toArray();

					self::$aCache[$sCacheKey] = $aFolders;
				} catch (\Throwable $e) {
					$aFolders = [];
				}
			} else {
				$aFolders = self::$aCache[$sCacheKey];
			}			
						
		}
		
		if(empty($aFolders)) {
			$aFolders = Ext_TC_Util::addEmptyItem($aFolders, L10N::t('Keine Ordner gefunden'));
		} else {
			$aFolders = Ext_TC_Util::addEmptyItem($aFolders, L10N::t('Bitte wählen'));
		}
		
		return $aFolders;
	}

}
