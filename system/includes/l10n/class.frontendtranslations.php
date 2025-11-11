<?php

class L10N_FrontendTranslations extends WDBasic {

	protected $_sTable = 'system_translations';
	
	protected $_sTableAlias = 'st';
	
	protected $_aFormat = array(

	);

	protected $_bAutoFormat = true;

	/**
	 * @inheritdoc
	 */
	public function save() {

		$aLanguages = array_keys(Factory::executeStatic('Util', 'getLanguages', 'frontend'));
		$aIntersectionData = $this->getIntersectionData();

		// Wenn Sprachfeld verändert wurde: Update-Lock setzen
		foreach($aLanguages as $sLanguage) {
			if(isset($aIntersectionData[$sLanguage])) {
				$this->update_lock = 1;
				break;
			}
		}

		return parent::save();
	}

	public function delete() {

		$sSql = "
				DELETE FROM
					#table
				WHERE
					`id` = :id
				LIMIT 1
			";
		$aSql = array('table'=>$this->_sTable, 'id'=>$this->id);
		DB::executePreparedQuery($sSql, $aSql);

		return true;

	}

}