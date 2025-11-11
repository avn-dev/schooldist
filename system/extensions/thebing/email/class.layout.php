<?php

class Ext_Thebing_Email_Layout extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_email_layouts';

	// Tabellenalias
	//protected $_sTableAlias = 'kdr';
	
	public function save($bLog = true) {
		global $user_data;

		// Alter oder neuer Eintrag
		if($this->_aData['id'] > 0) {
			$bInsert = false;
		} else {
			$bInsert = true;
		}

		$this->client_id = (int)$user_data['client'];;

		parent::save();

		return $this;
	}

	public static function getList($bForSelect = false) {
		global $user_data;

		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`active` = 1 AND
				`client_id` = :client_id
			ORDER BY
				`name`
			";
		$aSql = array (
			'table' => 'kolumbus_email_layouts',
			'client_id' => $user_data['client']
		);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($bForSelect) {
			$aResult = Ext_Thebing_Util::convertArrayForSelect($aResult);
		}

		return $aResult;
	}

	public static function getImages() {
		$sSql = '
			SELECT
				`id`, 
				`filename`, 
				`description`
			FROM
				`tc_upload`
			WHERE
				`category` = :category AND
				`active` = 1';

		$aSql = ['category' => 6];

		return \DB::getPreparedQueryData($sSql, $aSql);
	}

	static public function getBlankInstance() {
		
		$oBlank = new self;
		$oBlank->html = '{email_content}';
		
		return $oBlank;
	}
	
}
