<?php

/**
 * @deprecated -> \TcApi\Entity\ApiToken
 */
class Ext_TC_WDMVC_Token extends \TcApi\Entity\ApiToken {

	public static function getApplications() {
		
		$aApplications = [];
		$aApplications['gui2_api'] = L10N::t('API: Gui2', 'API');
		
		return $aApplications;
	}
	
	/**
	 * @param string $sToken
	 * @param string $sApplication
	 * @param string $sIp
	 * @param string $sHost
	 * @return boolean
	 */
	public static function validateToken($sToken, $sApplication, $sIp, $sHost = '') {

		$oTemp = new self();
		$aQuery = $oTemp->getListQueryData();

		$aQuery['sql'] = str_replace('WHERE', ' WHERE `applications`.`application` = :application AND `tc_wt`.`token` = :token AND', $aQuery['sql']);
		$aQuery['data']['application'] = $sApplication;
		$aQuery['data']['token'] = $sToken;

		$oDB = DB::getDefaultConnection();
		$aResult = $oDB->getCollection($aQuery['sql'], $aQuery['data']);

		foreach($aResult as $aData) {

			$oToken = static::getInstance($aData['id']);
			$aIps	= $oToken->ips;
			if(empty($aIps)) {
				return $oToken;
			}

			foreach($aIps as $sAllowedIp){
				
				$sAllowedIp = trim($sAllowedIp);
				$sIp		= trim($sIp);
				$sHost		= trim($sHost);
				
				if(
					$sIp == $sAllowedIp ||
					$sHost == $sAllowedIp
				){
					return $oToken;
				}
			}
		}

		return false;
	}
	


}
