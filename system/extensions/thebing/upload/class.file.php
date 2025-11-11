<?php

/**
 * @property int id
 * @property string changed
 * @property string created
 * @property int active
 * @property int creator_id
 * @property int editor_id
 * @property string category
 * @property string description
 * @property string filename
 */
class Ext_Thebing_Upload_File extends Ext_TC_Upload {

	const CORE_CATEGORY_MAPPING = [
		'communication' => [3, 4]
	];

	/**
	 * Ermittelt wo der Upload überall verwendet wird
	 */
	public function getUsage() {

		$aUsage = [];

		// pdf optionen
		$bPdfOptions = false;
		$bPdfAttachment = false;

		switch($this->category) {
			case 1:
			case 2:
				$bPdfOptions = true;
				break;
			case 5:
				$bPdfAttachment = true;
				break;
		}

		// Verwendungszwecke prüfen
		if($bPdfOptions) {

			// Prüfen ob Datei bei den PDFs verwendet wird
			$sSql = "
				SELECT
					`kpt`.`name`
				FROM
					`kolumbus_pdf_templates` `kpt` INNER JOIN
					`kolumbus_pdf_templates_languages` `kptl` ON
						`kptl`.`template_id` = `kpt`.`id` INNER JOIN
					`kolumbus_pdf_templates_options` `kpto` ON
						`kpto`.`template_id` = `kpt`.`id` AND
						`kpto`.`language_iso` = `kptl`.`iso_language`
				WHERE
					`kpto`.`option` IN (
						'first_page_pdf_template',
						'additional_page_pdf_template',
						'signatur_img'
					) AND
					`kpto`.`value` = :upload_id AND
					`kpt`.`active` = 1
				GROUP BY
					`kpt`.`id`
			";

			$aSql = array();
			$aSql['upload_id'] = (int)$this->id;

			$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

			foreach($aResult as $aData){
				$aInfo = array();
				$aInfo['reason'] = 'PDF';
				$aInfo['name'] = $aData['name'];
				$aUsage[] = $aInfo;
			}

		}

		if($bPdfAttachment) {

			// #5144
//			$sSql = "
//				SELECT
//					`kpt`.`name`
//				FROM
//					`kolumbus_pdf_templates_options_attachment` `kptoa` INNER JOIN
//					`kolumbus_pdf_templates_options` `kpto` ON
//						`kptoa`.`option_id` = `kpto`.`id` INNER JOIN
//					`kolumbus_pdf_templates` `kpt` ON
//						`kpt`.`id` = `kpto`.`template_id`
//				WHERE
//					`kpto`.`option` IN (
//						'attachments'
//					) AND
//					`kptoa`.`file_id` = :upload_id AND
//					`kpt`.`active` = 1 AND
//					`kpt`.`client_id` = :client_id
//				GROUP BY
//					`kpt`.`id`
//			";
//
//			$aSql = array();
//			$aSql['upload_id'] = (int)$this->id;
//			$aSql['client_id'] = (int)$this->client_id;
//
//			$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
//
//			foreach($aResult as $aData){
//				$aInfo = array();
//				$aInfo['reason'] = 'PDF';
//				$aInfo['name'] = $aData['name'];
//				$aUsage[] = $aInfo;
//			}

			$sSql = "
				SELECT
					'Frontend' `reason`,
					`kf`.`title` `name`
				FROM
					`kolumbus_forms_pages_blocks_settings` `kfpbs` INNER JOIN
					`kolumbus_forms_pages_blocks` `kfpb` ON
						`kfpb`.`id` = `kfpbs`.`block_id` AND
						`kfpb`.`block_id` = :type AND
						`kfpb`.`active` = 1 INNER JOIN
					`kolumbus_forms_pages` `kfp` ON
						`kfp`.`id` = `kfpb`.`page_id` AND
						`kfpb`.`active` = 1 INNER JOIN
					`kolumbus_forms` `kf` ON
						`kf`.`id` = `kfp`.`form_id` AND
						`kf`.`active` = 1
				WHERE
					`setting` LIKE 'file_%' AND
				    `value` = :id
			";

			$aResult = (array)DB::getQueryRows($sSql, [
				'id' => $this->id,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD
			]);

			$aUsage = array_merge($aUsage, $aResult);

		}

		return $aUsage;
	}

	/**
	 * Pfad der Datei
	 *
	 * @param bool $documentRoot
	 * @return string
	 */
	public function getPath(bool $documentRoot = true) {

		return self::getUploadDir($documentRoot).$this->filename;

		/*$oSchool = $this->getSchool();

		$sPath = '';
		if($oSchool) {
			$sPath = $oSchool->getSchoolFileDir($documentRoot);
			$sPath .= '/uploads/' . $this->filename;
		}

		return $sPath;*/
	}

	public static function getUploadDir(bool $documentRoot = true, bool $withStorageDir = true) {

		$path = '/ts/uploads/';

		if($withStorageDir) {
			$path = '/storage'.$path;
		}

		if($documentRoot) {
			$path = \Util::getDocumentRoot(false).$path;
		}

		return $path;
	}

	public static function getImages() {
		$sql = '
			SELECT
				`id`, 
				`filename`, 
				`description`
			FROM
				`tc_upload`
			WHERE
				`category` = :category AND
				`active` = 1';

		$sqlData = ['category' => 6];

		return \DB::getPreparedQueryData($sql, $sqlData);
	}

	public static function buildPath($path, bool $documentRoot = true) {
		$path = ltrim($path,'/');
		return self::getUploadDir($documentRoot).$path;
	}

	/**
	 * @deprecated
	 * @return Ext_Thebing_School|null
	 */
	public function getSchool() {

		throw new LogicException("Don't use getSchool() on this object!");

		if($this->school_id > 0) {
			return Ext_Thebing_School::getInstance($this->school_id);
		} else {
			return null;
		}

	}

	/**
	 * @param string $sType
	 * @return array
	 */
	public static function getFileExtensions($sType) {

		$aAllowed = Ext_Thebing_Util::getFileExtensions($sType);

		return $aAllowed;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	/**
	 * Validierung: arbeitet mit Kategorienamen (String)
	 */
	public function validate($bThrowExceptions = false)
	{
		$aErrors = parent::validate($bThrowExceptions);

		// Bei gelöschten Einträge nicht prüfen
		if(
			$aErrors === true &&
			$this->isActive()
		) {
			// Prüfen, ob "Gültig ab" nach dem aktuellsten "Gültig ab" liegt
			$aErrors = array();
			$sMsg = '';
			$bCheck = false;
			$sFile = $this->_aData['filename'];

			switch($this->_aData['category']) {
				case 1:
					$bCheck = Ext_Thebing_Util::checkFileExtension($sFile, 'pdf');
					$sMsg = 'NO_PDF_DATA';
					break;
				case 2:
				case 6:
					$bCheck = Ext_Thebing_Util::checkFileExtension($sFile, 'image');
					$sMsg = 'NO_IMG_DATA';
					break;
				case 3:
				case 4:
				case 5:
					$bCheck = Ext_Thebing_Util::checkFileExtension($sFile, 'file');
					$sMsg = 'NO_FILE_DATA';
					break;
			}
			if(!$bCheck) {
				$aErrors['filename'][] = array('message' => $sMsg);
			} else {
				return true;
			}
		}

		return $aErrors;
	}


	/**
	 * Speichert den Dialog ab verschiebt die Bilder
	 * in einen Public Path
	 *
	 * @param bool $bLog
	 * @return $this|Ext_TC_Basic
	 */
	public function save($bLog = true)
	{
		$mReturn = parent::save($bLog);

		if (
			$this->isActive() &&
			strpos((string)$this->filename, '/') === false &&
			(int)$this->category === 6
		) {
			$sFile          = $this->getPath();
			$sPublicPathFile = str_replace('/storage/', '/storage/public/', $sFile);
			$sPublicPath     = substr($sPublicPathFile, 0, strrpos($sPublicPathFile, '/'));

			Ext_Thebing_Util::checkDir($sPublicPath);

			if (is_file($sFile)) {
				copy($sFile, $sPublicPathFile);
				Util::changeFileMode($sPublicPathFile);
			}
		}

		return $mReturn;
	}

	public static function getSelectOptionsBySearch($sCategory, $sLanguage = null, $mObjectIds = null) {
		$sCategory = self::CORE_CATEGORY_MAPPING[$sCategory] ?? $sCategory;
		return parent::getSelectOptionsBySearch($sCategory, $sLanguage, $mObjectIds);
	}
}
