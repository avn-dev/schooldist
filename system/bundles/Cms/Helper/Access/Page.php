<?php

namespace Cms\Helper\Access;

class Page {

	/**
	 * @var \Cms\Entity\Page
	 */
	private $oPage;
	
	/**
	 * @var \Access_Backend
	 */
	private $oAccess;
	
	private static $aRightsCache = [];
	
	public function __construct(\Cms\Entity\Page $oPage) {
		$this->oPage = $oPage;
		$this->oAccess = \Access::getInstance();
	}

	function checkrightonpage($right, $page_id, $altright="admin") {

		$objWebDynamicsDAO = new \Cms\Helper\Data;

		$aOptions = array();
		$aOptions['right'] = $right;
		$aOptions['page_id'] = $page_id;
		$aOptions['alternative_right'] = $altright;

		\System::wd()->executeHook('check_right_on_page', $aOptions);

		if(isset($aOptions['return'])) {
			return $aOptions['return'];
		}

		$arrPageData = $objWebDynamicsDAO->getPageData($aOptions['page_id']);

		if(
			$aOptions['right'] == "edit" && 
			!$arrPageData['editable']
		) {
			return false;
		}

		if(
			$this->oAccess->hasRight($aOptions['right']) && 
			warrantychecker($arrPageData['author'], $arrPageData['warrant'], $aOptions['alternative_right'])
		) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Prüft das angegebene recht auf der Seite
	 * Zuerst direkt auf der Seite und falls es auf der Seite gesetzt ist, werden alle
	 * untergeordneten Ordner durchlaufen
	 *
	 * @param string $sRight
	 * @param integer $iPageId
	 * @param string $sAltRight
	 * @return bool
	 */
	function checkRoleRightInPath($sRight, $iRoleId) {
		
		$iPageId = $this->oPage->id;
		
		$objWebDynamicsDAO = new \Cms\Helper\Data;

		$iRightId = $this->getRightId($sRight);

		// recht auf der seite
		$bCheck = $this->checkItemPageRight('role', $iRightId, $iRoleId);
		if($bCheck === true) {
			return true;
		}

		$aPageData = $objWebDynamicsDAO->getPageData($iPageId);

		// recht in
		$file_data = explode("/", $aPageData["path"]);
		$h=0;
		$link = "";

		while(count($file_data) > $h) {
			$sSql = "SELECT `id`, `title` FROM `cms_pages` WHERE `path` = '".$link."' AND `file` = 'index' AND `site_id` = ".(int)$aPageData['site_id']." AND (language = '".$aPageData['language']."' OR language = '') ORDER BY language DESC";
			$aDir = \DB::getQueryRow($sSql);

			// recht auf der seite mit dem benutzer
			$bCheck = $this->checkItemPageRight('role', $iRightId, $iRoleId);

			if($bCheck === true) {
				return true;
			}

			$link .= $file_data[$h]."/";
			$h++;
		}

		return false;

	}
	function checkRightInPath($sRight, $iUserId=false) {
		
		$objWebDynamicsDAO = new \Cms\Helper\Data;

		$iPageId = $this->oPage->id;
		
		if(!$iUserId) {
			$iUserId = $this->oAccess->id;
		}

		$iRightId = $this->getRightId($sRight);

		$aOptions = array();
		$aOptions['right'] = $sRight;
		$aOptions['right_id'] = $iRightId;
		$aOptions['page_id'] = $iPageId;
		$aOptions['alternative_right'] = 'page_admin';

		\System::wd()->executeHook('check_right_in_path', $aOptions);

		if(isset($aOptions['return'])) {
			return $aOptions['return'];
		}

		$aPageData = $objWebDynamicsDAO->getPageData($aOptions['page_id']);

		if(
			$aOptions['right'] == "edit" && 
			!$aPageData['editable']
		) {
			return false;
		}

		// recht auf der seite
		$bCheck = $this->checkPageRight($aOptions['page_id'], $aOptions, $iUserId);
		if($bCheck === true) {
			return true;
		}

		// recht in
		$file_data = explode("/", $aPageData["path"]);
		$h=0;
		$link = "";

		while(count($file_data) > $h) {
			$sSql = "SELECT `id`, `title` FROM `cms_pages` WHERE `path` = '".$link."' AND `file` = 'index' AND `site_id` = ".(int)$aPageData['site_id']." AND (language = '".$aPageData['language']."' OR language = '') ORDER BY language DESC";
			$aDir = \DB::getQueryRow($sSql);

			// recht auf der seite mit dem benutzer
			$bCheck = $this->checkPageRight($aDir['id'], $aOptions, $iUserId);

			if($bCheck === true) {
				return true;
			}

			$link .= $file_data[$h]."/";
			$h++;
		}

		return false;
	}

	function checkPageRight($iPageId, $aOptions, $iUserId=false) {
		global $session_data;

		if(!$iUserId) {
			global $user_data;
			$iUserId = $user_data['id'];
		}

		$bReturn = true;

		if(!isset($session_data['page_right'][$iPageId][$aOptions['right_id']][$iUserId])) {

			$bReturn = $this->executeCheckPageRight($iPageId, $aOptions, $iUserId);

			$session_data['page_right'][$iPageId][$aOptions['right_id']][$iUserId] = $bReturn;

		} else {

			$bReturn = $session_data['page_right'][$iPageId][$aOptions['right_id']][$iUserId];

		}

		return $bReturn;

	}

	function executeCheckPageRight($iPageId, $aOptions, $iUserId=false) {
		
		$objWebDynamicsDAO = new \Cms\Helper\Data;

		if(!$iUserId) {
			global $user_data;
			$iUserId = $user_data['id'];
		}

		$aPageData = $objWebDynamicsDAO->getPageData($iPageId);

		$bCheckUser = $this->checkItemPageRight('user', $aOptions['right_id'], $iUserId);

		$bCheckRole = false;
		$aRoles = \Access_Backend::getUserRoles($iUserId);
		foreach((array)$aRoles as $iRole) {
			$bCheck = $this->checkItemPageRight('role', $aOptions['right_id'], $iRole);
			if($bCheck === true) {
				$bCheckRole = true;
			}
		}

		// alternativ recht? (admin recht)
		if(
			$this->oAccess->hasRight($aOptions['alternative_right'], $iUserId) ||
			(
				$this->oAccess->hasRight($aOptions['right'], $iUserId) &&
				(
					(
						$aPageData['author'] == $iUserId &&
						$bCheckUser !== false 
					) ||
					$bCheckUser === true || 
					(
						$bCheckUser !== false &&
						$bCheckRole === true 
					)
				)
			)
		) {
			return true;
		}

		return false;

	}

	// TODO: @MK Diese Funktion wird im Frontend mehrmals mit gleichen Parametern aufgerufen - cachen??? (siehe vorbereitetes)

	//$aCheckItemPageRightCache = array();

	function checkItemPageRight($sItem, $iRightId, $iItemId) {

		$sCacheKey = 'Access\Page::checkItemPageRight:'.$this->oPage->id.':'.$sItem.':'.$iRightId.':'.$iItemId;
		
		if(array_key_exists($sCacheKey, self::$aRightsCache)) {

			$sSql = "
					SELECT 
						* 
					FROM 
						`cms_pages_rights`
					WHERE 
						`page_id` = :page_id AND 
						`item` = :item AND 
						`item_id` = :item_id AND 
						`right_id` = :right_id
					";
			$aSql = array(
				'page_id'=>(int)$this->oPage->id,
				'item'=>$sItem,
				'item_id'=>(int)$iItemId,
				'right_id'=>(int)$iRightId
			);
			$aCheck = \DB::getPreparedQueryData($sSql, $aSql);

			if(!empty($aCheck)) {
				if($aCheck[0]['granted'] == 1) {
					self::$aRightsCache[$sCacheKey] = true;
				} else {
					self::$aRightsCache[$sCacheKey] = false;
				}
			} else {
				self::$aRightsCache[$sCacheKey] = null;
			}

		}

		return self::$aRightsCache[$sCacheKey];
	}

	function getRightId($sRight) {
		global $session_data;

		if(!isset($session_data['right_id'][$sRight])) {

			$sSql = "SELECT `id` FROM `system_rights` WHERE `right` = :right LIMIT 1";
			$aSql = array('right'=>$sRight);
			$iRight = \DB::getQueryOne($sSql, $aSql);

			$session_data['right_id'][$sRight] = (int)$iRight;

		}

		return $session_data['right_id'][$sRight];

	}

	static public function checkPageAccess($page_data=false, $user_data=false) {

		if(!$page_data) {
			global $page_data;
		}
		if(!$user_data) {
			global $user_data;
		}

		$aAccess = array_intersect_key((array)$page_data['access'],(array)$user_data['access']);

		if(($page_data['access'] == "" || count($aAccess)>0) || $user_data['cms']) {
			return true;
		} else {
			return false;
		}

	}
}
