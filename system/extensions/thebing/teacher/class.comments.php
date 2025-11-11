<?php

/**
 * @TODO Ist fast dasselbe wie Ext_Thebing_Accommodation_Visit
 */
class Ext_Thebing_Teacher_Comments extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_teachers_comments';

	protected $_sTableAlias = 'ts_tc';

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
				
		$sDir .= '/storage/ts/teachers/comments/';
		
		return $sDir;
	}

	/**
	 * @param bool $bFullPath
	 * @return string[]
	 */
	public function getUploadPaths($bFullPath = false) {

		$oTeacher = $this->getTeacher();
		$oSchool = $oTeacher->getSchool();

		$sPath = self::getUploadDir($oSchool, $bFullPath);
		$aFiles = array_map(function($sFile) use($sPath) {
			return $sPath.$sFile;
		}, $this->documents);

		return $aFiles;

	}

	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sTableAlias.".*,`kact`.`title` `activity`,`ksu`.`title` `subject` ".$sFormat."
				FROM
					`{TABLE}` ".$sTableAlias." LEFT JOIN
					`kolumbus_activity` `kact` ON ".$sTableAlias.".`activity_id` = `kact`.`id` LEFT JOIN
					`kolumbus_subject` `ksu` ON ".$sTableAlias.".`subject_id` = `ksu`.`id` LEFT JOIN
					`ts_companies_contacts` `ts_ac` ON ".$sTableAlias.".`teacher_id` = `ts_ac`.`id`
			";

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	public function  __set($sName, $mValue) {

		if($sName == 'documents') {
			$mValue = implode(',', (array)$mValue);
			$this->_aData['documents'] = $mValue;
		} else {
			parent::__set($sName, $mValue);
		}

	}

	public function  __get($sName) {

		if($sName == 'documents') {

			$aDocuments = explode(',', $this->_aData['documents']);

			return $aDocuments;
		} else {
			return parent::__get($sName);
		}
	}

}
