<?php

namespace Cms\Entity;

class Redirection extends \WDBasic {

    protected $_sTable = 'cms_redirections';

    protected $_sTableAlias = 'cms_r';

    protected $_bAutoFormat = true;

	// Format
	protected $_aFormat = array(
		'url' => array(
			'validate'			=> 'REGEX',
			'validate_value'	=> '^\/[a-zA-Z\-_0-9\/\.]*$'
		),
		'target' => array(
			'required' => true
		)
	);

	/**
	 * Gibt ein Array mit verfügbaren Codes zurück
	 * @return array 
	 */
	public static function getHttpStatuscodes() {
		
		$aHttpStatuscodes = array();
		
		$aHttpStatuscodes['http_301'] = 'HTTP/1.1 301 Moved Permanently';
		$aHttpStatuscodes['http_302'] = 'HTTP/1.1 302 Found';

		return $aHttpStatuscodes;
		
	}

	public static function getByUrl($iSiteId, $sUrl) {
		
		$sSql = "
			SELECT
				`id`
			FROM
				`cms_redirections` `cms_r`
			WHERE
				`active` = 1 AND
				`site_id` = :site_id AND
				`url` LIKE :url			
			";
		$aSql = array(
			'site_id' => (int)$iSiteId,
			'url' => $sUrl
		);
		$iRedirectionId = \DB::getQueryOne($sSql, $aSql);

		if(!empty($iRedirectionId)) {

			$oRedirection = self::getInstance($iRedirectionId);
		
			return $oRedirection;

		}

		return null;
	
	}

	public function save() {

		$mSave = parent::save();

		// Routen aktualisieren
		$oRoutingService = new \Core\Service\RoutingService();
		$oRoutingService->buildRoutes();

		return $mSave;
	}
	
}