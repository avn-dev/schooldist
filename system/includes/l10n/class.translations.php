<?php

class L10N_BackendTranslations extends WDBasic {

	protected $_sTable = 'language_data';
	
	protected $_sTableAlias = 'ld';
	
	protected $_aFormat = array(

	);

	protected $_bAutoFormat = true;

	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {

			$mValidate = array();

			if(
				$this->file_id == 0 &&
				$this->use == 0
			) {
				$mValidate[] = 'GLOBAL_TRANSLATION_USE_ERROR';
			}

		}

		if(empty($mValidate))
		{
			return true;
		}

		return $mValidate;

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