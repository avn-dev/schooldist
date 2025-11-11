<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TC_Flexible_Option extends Ext_TC_Basic {

	const OPTION_SEPARATOR_KEY = "%separator%";
	
	// Tabellenname
	protected $_sTable = 'tc_flex_sections_fields_options';

	protected $_sTableAlias = 'kfsfo';

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'user_id';

	protected $_aValues = array();

	protected $_aJoinTables = array(
		'kfsfov' => array(
			'table'=>'tc_flex_sections_fields_options_values',
			'primary_key_field'=>'option_id',
			'autoload' => false
		),
		'values' => [
			'table' => 'tc_flex_sections_fields_options_values',
			'primary_key_field'=>'option_id',
			'autoload' => false,
			'cloneable' => true
		]
	);

	public function  __set($sName, $mValue){
		if( mb_strpos($sName, '_title') !== false) {
			$sLanguage = str_replace('_title', '', $sName);
			$this->_aValues[$sLanguage] = $mValue;
		} elseif($sName == 'lang_id') {
			
		} else {
			parent::__set($sName, $mValue);
		}
	}

	public function  __get($sName){

		if( mb_strpos($sName, '_title') !== false) {
			$sLanguage = str_replace('_title', '', $sName);
			return $this->getTitle($sLanguage);
		} else {
			return parent::__get($sName);
		}
	}

	public function saveTitle($sLang, $sTitle){


		$aSql = array();
		$aSql['option_id'] = (int)$this->id;
		$aSql['lang_id'] = $sLang;
		$aSql['title'] = $sTitle;

		$sOldTitle = $this->getTitle($sLang);

		if($sOldTitle == null){
			$sSql = "INSERT INTO
							`tc_flex_sections_fields_options_values`
						SET
							`option_id` = :option_id,
							`lang_id` = :lang_id,
							`title` = :title
					";
			DB::executePreparedQuery($sSql, $aSql);
		}else{
			$sSql = "UPDATE
							`tc_flex_sections_fields_options_values`
						SET
							`title` = :title
						WHERE
							`option_id` = :option_id AND
							`lang_id` = :lang_id
					";
			DB::executePreparedQuery($sSql, $aSql);
		}

	}

	public function getTitle($sLang){
		$sSql = "SELECT
						*
					FROM
						`tc_flex_sections_fields_options_values`
					WHERE
						`option_id` = :option_id AND
						`lang_id` = :lang_id
					LIMIT 1
					";
		$aSql = array();
		$aSql['option_id'] = (int)$this->id;
		$aSql['lang_id'] = $sLang;

		$aResult = DB::getQueryRow($sSql, $aSql);

		if(isset($aResult['title'])){
			return $aResult['title'];
		}else{
			return null;
		}
	}

	public function save($bLog = true) {

		$bOriginalForceUpdateUser = $this->bForceUpdateUser;
		$this->bForceUpdateUser = true;

		$aValues = $this->_aValues;

		parent::save($bLog);

		foreach((array)$aValues as $sLanguage=>$sValue) {
			$this->saveTitle($sLanguage, $sValue);
		}

		$this->bForceUpdateUser = $bOriginalForceUpdateUser;

		return $this;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$sInterfaceLanguage = System::getInterfaceLanguage();

		$aSqlParts['select'] .= ",
			`kfsfov_title`.`title` `title`
		";

		$aSqlParts['from'] .= " INNER JOIN
			`tc_flex_sections_fields_options_values` `kfsfov_title` ON
				`kfsfov_title`.`option_id` = `kfsfo`.`id` AND
				`kfsfov_title`.`lang_id` = '{$sInterfaceLanguage}'
		";

	}

}