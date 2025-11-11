<?php

class Ext_Thebing_Teacher_Upload extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_teachers_uploads';

	protected $_aFormat = array(
		'filename' => array(
			'required' => true
		)
	);

	public function delete() {

		$bDelete = parent::delete();

		if(
			$bDelete &&
			$this->bPurgeDelete
		) {
			$sPath = $this->getPath(true);
			if(is_file($sPath)) {
				unlink($sPath);
			}
		}

		return $bDelete;

	}

	/**
	 * @return Ext_Thebing_Teacher
	 */
	public function getTeacher() {
		return Ext_Thebing_Teacher::getInstance($this->teacher_id);
	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @param bool $bFullPath
	 * @return string
	 */
	public static function getUploadDir(Ext_Thebing_School $oSchool, $bFullPath = false) {

		$sDir = '';
		
		if($bFullPath) {
			$sDir .= Util::getDocumentRoot(false);
		}
				
		$sDir .= '/storage/ts/teachers/documents/';
		
		return $sDir;
	}

	/**
	 * @param bool $bFullPath
	 * @return string
	 */
	public function getPath($bFullPath = false) {

		$oTeacher = $this->getTeacher();
		$oSchool = $oTeacher->getSchool();

		$sPath = self::getUploadDir($oSchool, $bFullPath);
		$sPath .= $this->filename;

		return $sPath;

	}

	public static function getList($iTeacherId, $sType = 'pdf') {

		$sSql = " SELECT 
						`id`
					FROM
						`ts_teachers_uploads`
					WHERE
						`teacher_id` = :agency_id AND
						`type` = :type AND
						`active` = 1";
		$aSql = array('agency_id' => (int)$iTeacherId, 'type' => $sType);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		foreach((array)$aResult as $aData){
			$aBack[] = new \TsCompany\Entity\Upload($aData['id']);
		}

		return $aBack;
	}

}
