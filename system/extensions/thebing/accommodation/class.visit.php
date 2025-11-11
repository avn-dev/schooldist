<?php

/**
 * @TODO Ist fast dasselbe wie Ext_Thebing_Teacher_Comments
 *
 * @property $id 
 * @property $changed 	
 * @property $created 	
 * @property $active 	
 * @property $user_id 	
 * @property $date 	
 * @property $title 	
 * @property $subject_id 	
 * @property $activity_id 	
 * @property $text 	
 * @property $documents 	
 * @property $visitor 	
 * @property $acc_id
*/
class Ext_Thebing_Accommodation_Visit extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_accommodation_visits';

	protected $_sTableAlias = 'kav';

	protected $_aFormat = array(
		'date' => array(
			'required'	=> true,
			'validate'	=> 'DATE'
		),
		'title' => array(
			'required'	=> true,
		),
		'text'	=> array(
			'required' => true,
		),
		'subject_id' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
		'activity_id' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
	);

	public function __set($sName, $mValue) {

		if(
			$sName == 'documents' &&
			is_array($mValue)
		) {
			$mValue = implode(',', $mValue);
		}

		parent::__set($sName, $mValue);

	}

	public function  __get($sName) {

		if($sName == 'documents') {
			return explode(',', $this->_aData['documents']);
		}

		return parent::__get($sName);

	}

	public function delete() {

		$bDelete = parent::delete();

		if(
			$bDelete &&
			$this->bPurgeDelete
		) {
			foreach($this->getUploadPaths(true) as $sFile) {
				if(is_file($sFile)) {
					unlink($sFile);
				}
			}
		}

		return $bDelete;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= " ,
			`kact`.`title` `activity`,
		    `ksu`.`title` `subject`,
			`cdb4`.`ext_33` `accommodation_provider_name`
		";

		$aSqlParts['from'] .= "  LEFT JOIN
					`kolumbus_activity` `kact` ON
						 `kav`.`activity_id` = `kact`.`id` LEFT JOIN
					`kolumbus_subject` `ksu` ON
						 `kav`.`subject_id` = `ksu`.`id` JOIN
					 `customer_db_4` `cdb4` ON
						 `cdb4`.`id` = `kav`.`acc_id`
		";
	}

	/**
	 * @param bool $bFullPath
	 * @return string
	 */
	public static function getUploadDir($bFullPath = false) {

		$sDir = '/storage/accommodations/visits/';

		if($bFullPath) {
			$sDir = Util::getDocumentRoot(false).$sDir;
		}

		return $sDir;

	}

	/**
	 * @param bool $bFullPath
	 * @return string[]
	 */
	public function getUploadPaths($bFullPath = false) {

		$sPath = self::getUploadDir($bFullPath);
		$aFiles = array_map(function($sFile) use($sPath) {
			return $sPath.$sFile;
		}, $this->documents);

		return $aFiles;

	}

}
