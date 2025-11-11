<?php

/**
* @property $id 
* @property $changed 	
* @property $created 	
* @property $active 	
* @property $creator_id 	
* @property $user_id 	
* @property $accommodation_id 	
* @property $type 	
* @property $lang 	
* @property $published 	
* @property $filename 	
* @property $description 	
* @property $released_student_login 	
* @property $title 	
* @property $position
*/


class Ext_Thebing_Accommodation_Upload extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_accommodations_uploads';

	protected $_aJoinTables = array(
		'accommodations_uploads_i18n' => array(
			'table' => 'kolumbus_accommodations_uploads_i18n',
			'foreign_key_field' => array('language_iso', 'description'),
			'primary_key_field' => 'upload_id'
		)
	);

	public function getDescription($languageIso = null) {
		return $this->getI18NName('accommodations_uploads_i18n', 'description', $languageIso);
	}

	/**
	 * @param int $iAccommodationId
	 * @param string $sType
	 * @param string $sReleasedColumn
	 * @return Ext_Thebing_Accommodation_Upload[]
	 */
	public static function getList($iAccommodationId, $sType = 'pdf', $sReleasedColumn='published') {
		$aBack = array();

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_accommodations_uploads`
			WHERE
				`accommodation_id` = :accommodation_id AND
				`type` = :type AND
				`active` = 1 AND
				#released_column = 1
		";

		$aSql = array(
			'accommodation_id' => $iAccommodationId,
			'type' => $sType,
			'released_column' => $sReleasedColumn
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach((array)$aResult as $aData) {
			$aBack[] = new Ext_Thebing_Accommodation_Upload($aData['id']);
		}

		return $aBack;
	}

	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if(
			$aErrors === true &&
			$this->active == 1
		) {

			$sFile = $this->filename;

			if(
				$this->type == 'pdf'
			){
				$bCheck			= Ext_Thebing_Util::checkFileExtension($sFile, 'pdf');
				$sErrorType		= 'INVALID_PDF';
			}else{
				$bCheck = Ext_Thebing_Util::checkFileExtension($sFile, 'image');
				$sErrorType		= 'INVALID_IMAGE';
			}

			if(!$bCheck){
				$aErrors = array(
					'filename' => $sErrorType
				);
			}

		}

		return $aErrors;
	}

	public function delete() {

		$bDelete = parent::delete();

		if(
			$bDelete &&
			$this->bPurgeDelete &&
			$this->isFileExisting()
		) {
			unlink($this->getPath());
		}

		return $bDelete;

	}

	/**
	 * @return Ext_Thebing_Accommodation
	 */
	public function getAccommodationProvider() {
		return Ext_Thebing_Accommodation::getInstance($this->accommodation_id);
	}

	/**
	 * Liefert den vollständigen Pfad zur Datei
	 * @return string
	 */
	public function getPath() {
		return self::getBasePath().$this->filename;
	}

	/**
	 * Liefert den Basis-Pfad, wo die Uploads für Unterkünfte liegen
	 * @return string
	 */
	public static function getBasePath() {
		return \Util::getDocumentRoot().'storage/accommodation/';
	}

	/**
	 * Prüfen, ob die Datei existiert
	 * @return bool
	 */
	public function isFileExisting() {
		return is_file($this->getPath());
	}
	
}
