<?php


class Ext_TC_Upload extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_upload';

	protected $_sTableAlias = 'tc_u';

	/**
	 * @var array
	 */
	protected $_aFormat = [
		'description' => [
			'required' => true
		],
		'category' => [
			'required' => true
		],
		'filename' => [
			'required' => true
		]
	];

	/**
	 * @var array
	 */
	protected $_aJoinTables = [
		'languages' => [
			'table'=>'tc_upload_languages',
			'foreign_key_field' =>'language_iso',
			'primary_key_field' =>'upload_id'
		],
		'objects' => [
			'table' =>'tc_upload_objects',
			'foreign_key_field' =>'object_id',
			'primary_key_field' =>'upload_id',
		]
	];

	/**
	 * Ermittelt wo der Upload überall verwendet wird
	 * @todo Caching einbauen!
	 */
	public function getUsage() {

		$aUsage = array();

		// pdf optionen
		$bPdfOptions = false;
		$bPdfAttachment = false;

		switch($this->category) {
				case 'pdf_background':
				case 'signatures':
					$bPdfOptions = true;
					break;
				case 'pdf_attachments':
					$bPdfAttachment = true;
					break;
		}

		// Verwendungszwecke prüfen
		if($bPdfOptions){

			// Prüfen ob Datei bei den PDFs verwendet wird
			$sSql = "
				SELECT
					`kpt`.`name`
				FROM
					`tc_pdf_templates` `kpt` INNER JOIN
					`tc_pdf_templates_options` `kpto` ON
						`kpto`.`template_id` = `kpt`.`id`
				WHERE
					`kpto`.`option` IN (
									'first_page_pdf_template',
									'signatur_img'
								) AND
					`kpto`.`value` = :upload_id AND
					`kpt`.`active` = 1
				GROUP BY
					`kpt`.`id`
			";

			$aSql = array();
			$aSql['upload_id'] = (int)$this->id;

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aResult as $aData){
				$aInfo = array();
				$aInfo['reason'] = 'PDF';
				$aInfo['name'] = $aData['name'];
				$aUsage[] = $aInfo;
			}

		}

		if($bPdfAttachment) {
			$sSql = "
				SELECT
					`kpt`.`name`
				FROM
					`tc_pdf_templates_options_attachments` `kptoa` INNER JOIN
					`tc_pdf_templates_options` `kpto` ON
						`kptoa`.`option_id` = `kpto`.`id` INNER JOIN
					`tc_pdf_templates` `kpt` ON
						`kpt`.`id` = `kpto`.`template_id`
				WHERE
					`kpto`.`option` IN (
									'attachments'
								) AND
					`kptoa`.`file_id` = :upload_id AND
					`kpt`.`active` = 1
				GROUP BY
					`kpt`.`id`
			";
			
			$aSql = array();
			$aSql['upload_id'] = (int)$this->id;

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aResult as $aData) {
				$aInfo = array();
				$aInfo['reason'] = 'PDF';
				$aInfo['name'] = $aData['name'];
				$aUsage[] = $aInfo;
			}

		}

		return $aUsage;
	}

	/**
	 * Pfad der Datei
	 *
	 * @param bool $bDocumentRoot
	 * @return string
	 */
	public function getPath(bool $bDocumentRoot = true) {

		$sFilePath = Ext_TC_Upload_Gui2_Data::getUploadPath($bDocumentRoot);
		$sFilePath = $sFilePath . $this->filename;

		return $sFilePath;
	}

	public static function getUploadDir(bool $documentRoot = true, bool $withStorageDir = true) {

		$path = Ext_TC_Upload_Gui2_Data::getUploadPath($documentRoot);

		if($withStorageDir) {
			$path = '/storage'.$path;
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

		$sqlData = ['category' => 'image'];

		return \DB::getPreparedQueryData($sql, $sqlData);
	}

	/**
	 * Öffentlicher Pfad der Datei
	 *
	 * @return string
	 */
	public function getPublicPath() {

		$sFilePath = Ext_TC_Upload_Gui2_Data::getUploadPath(false);
		$sFilePath = $sFilePath . $this->filename;

		$sFilePath = str_replace('/storage/', '/media/', $sFilePath);

		return $sFilePath;
	}

	/**
	 * @param $sType
	 * @return array
	 */
	public static function getFileExtensions($sType) {

		switch($sType) {
			case 'file':
				$aAllowed = array('jpg', 'jpeg', 'png', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx');
				break;
			case 'pdf':
				$aAllowed = array('pdf');
				break;
			case 'image':
			default:
				$aAllowed = array('jpg', 'jpeg', 'png', 'gif');
				break;
		}

		return $aAllowed;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		// Bei gelöschten Einträge nicht prüfen
		if(
			$aErrors === true &&
			$this->isActive()
		) {

			// Prüfen, ob "Gültig ab" nach dem aktuellsten "Gültig ab" liegt
			$aErrors = array();
			$sMsg = '';
			$aAllowed = array();

			$path_parts = pathinfo($this->_aData['filename']);

			switch($this->_aData['category']) {
				case 'pdf_background':
					$aAllowed = Ext_TC_Upload::getFileExtensions('pdf');
					$sMsg = 'NO_PDF_DATA';
					break;
				case 'signatures':
				case 'images':
					$aAllowed = Ext_TC_Upload::getFileExtensions('image');
					$sMsg = 'NO_IMG_DATA';
					break;
				case 'pdf_attachments':
				case 'communication':
					$aAllowed = Ext_TC_Upload::getFileExtensions('file');
					$sMsg = 'NO_FILE_DATA';
					break;
			}

			if(!empty($aAllowed) && !in_array(mb_strtolower($path_parts['extension']), $aAllowed)) {
				$aErrors['filename'][] = array('message' => $sMsg);
			}

			if(empty($aErrors)) {
				$aErrors = true;
			}

		}

		return $aErrors;
	}

	/**
	 * Speichert den Dialog ab verschiebt die Bilder
	 * in einen Public Path
	 *
	 * @param bool $bLog
	 * @return Ext_TC_Basic
	 */
	public function save($bLog = true) {
		
		$mReturn = parent::save($bLog);
		
		if(
			$this->isActive() &&
			mb_strpos($this->filename,'/') === false &&
			$this->category == 'images'
		) {
			
			$sFile = $this->getPath();

			$sPublicPathFile = str_replace('/storage/', '/storage/public/', $sFile);
			$sPublicPath = substr($sPublicPathFile, 0, strrpos($sPublicPathFile, '/'));

			Ext_TC_Util::checkDir($sPublicPath);

			if(is_file($sFile)) {
				copy($sFile, $sPublicPathFile);
			}
		}

		return $mReturn;
		
	}
	
	/**
	 * Sucht entsprechende Uploads und gibt diese als Array zurück
	 * @param string
	 * @param Objekt ID Array oder Int
	 * @return array
	 */
	public static function search($sCategory, $sLanguage=null, $mObjectIds=null) {

		$aSql = array(
			'category'=> (array)$sCategory
		);
		$sSql = "
			SELECT
				*
			FROM
				`tc_upload` `tc_u` LEFT JOIN
				`tc_upload_languages` `tc_ul` ON
					`tc_u`.`id` = `tc_ul`.`upload_id` LEFT JOIN
				`tc_upload_objects` `tc_uo` ON
					`tc_u`.`id` = `tc_uo`.`upload_id`
			WHERE
				`tc_u`.`active` = 1 AND
				`tc_u`.`category` IN (:category)
		";
		
		if($sLanguage !== null) {
			$sSql .= "  AND
				`tc_ul`.`language_iso` = :language_iso ";
			$aSql['language_iso'] = $sLanguage;
		}
		
		if($mObjectIds !== null) {

			if(!is_array($mObjectIds)) {
				$aObjectIds = array($mObjectIds);
			} else {
				$aObjectIds = $mObjectIds;
			}

			$sSql .= " AND 
				`tc_uo`.`object_id` IN (:object_ids) ";
			$aSql['object_ids'] = $aObjectIds;
			
		}

		$sSql .= " 
			GROUP BY
				`tc_u`.`id`
			ORDER BY
				`tc_u`.`description`";

		$aResults = DB::getQueryRows($sSql, $aSql);

		return (array)$aResults;
	}

	/**
	 * @param string $sCategory
	 * @param null|string $sLanguage
	 * @param null $mObjectIds
	 * @return array
	 */
	public static function getSelectOptionsBySearch($sCategory, $sLanguage = null, $mObjectIds = null) {

		$aReturn = array();
		
		$aResult = self::search($sCategory, $sLanguage, $mObjectIds);
		foreach((array)$aResult as $aUpload) {
			$aReturn[$aUpload['id']] = $aUpload['description'];
		}
		
		return $aReturn;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->description;
	}

	public function  manipulateSqlParts(&$aSqlParts, $sView=null) {
		$aSqlParts['select'] .= ",
 			GROUP_CONCAT(DISTINCT `objects`.`object_id`) AS `objects`
        ";
	}

}
