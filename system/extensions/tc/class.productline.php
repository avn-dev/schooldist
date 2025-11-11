<?php

/**
 * Productline WDBASIC
 *
 * @since 30.05.2011
 * 
 * @property int $id
 * @property timestamp $changed
 * @property timestamp $created
 * @property date $valid_util
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 */

class Ext_TC_Productline extends Ext_TC_Basic {

	protected $_sTable = 'tc_productlines';

	protected $_sTableAlias = 'tc_p';
	
	protected $_sPlaceholderClass = 'Ext_TC_Placeholder_Productline';

	protected $_aFormat = array(
		'valid_until' => array(
			'format' => 'DATE'
		)
	);

	protected $_aJoinTables = array(
		'languages_tc_p_i18n' => array(
			'table' => 'tc_productlines_i18n',
	 		'foreign_key_field'=> array('language_iso', 'name', 'resource_name'),
	 		'primary_key_field'=> 'productline_id'
		)

	);

	public function getName($sLang = ''){
		$sName = $this->getI18NName('languages_tc_p_i18n', 'name', $sLang);
		return $sName;
	}

	####################################
	## CACHE METHODEN
	####################################

	/**
	 * Delete the Object Cache
	 * ->>NICHT ID Bezogen<<--
	 */
	public function deleteCache() {
		WDCache::delete('Ext_TC_Productline::getSelectOptions');
	}

	####################################
	## METHODE MIT CACHE
	## ->>NICHT ID Bezogen<<--
	####################################

	/**
	 * get Array for select
	 * @param string $sLanguage
	 * @return array
	 */
	public static function getSelectOptions($sLanguage = ''){

		$mList = WDCache::get('Ext_TC_Productline::getSelectOptions');

		if($mList === null){
			$mList = array();
		}

		if(empty($sLanguage))
		{
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		if(!isset($mList[$sLanguage])){

			$oTemp = new self();
			$mList[$sLanguage] = $oTemp->getArrayListI18N(array('name'), true, $sLanguage);

			WDCache::set('Ext_TC_Productline::getSelectOptions', 86400, $mList);

		}

		return $mList[$sLanguage];
	}

	###################################
	## METHODE MIT CACHE
	## ->>ID Bezogen<<--
	####################################

	public function save($bLog = true) {

		$this->deleteCache();

		return parent::save($bLog);

	}

}