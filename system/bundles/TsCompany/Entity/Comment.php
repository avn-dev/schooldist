<?php

namespace TsCompany\Entity;

/**
 * @property int $id
 * @property string $changed
 * @property string $created
 * @property int $active
 * @property int $creator_id
 * @property string $title
 * @property int $subject_id
 * @property int $activity_id
 * @property string $text
 * @property int $company_id
 * @property int $company_contact_id
 * @property string $documents
 * @property int $user_id
 * @property string $follow_up Ist in der Datenbank eine Date-Spalte.
 */
class Comment extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_companies_comments';

	protected $_sTableAlias = 'kaco';

	protected $_aJoinedObjects = [
		'subject' => [
			'class' => \Ext_Thebing_Marketing_Subject::class,
			'key' => 'subject_id',
			'check_active' => true,
			'type' => 'parent',
		],
		'activity' => [
			'class' => \Ext_Thebing_Marketing_Activity::class,
			'key' => 'activity_id',
			'check_active' => true,
			'type' => 'parent',
		],
		'company_contact' => [
			'class' => \Ext_Thebing_Agency_Contact::class,
			'key' => 'company_contact_id',
			'check_active' => true,
			'type' => 'parent',
		],
	];

	/**
	 * @param \Ext_Thebing_Gui2|null $oGui
	 *
	 * @return array
	 */
	public function getListQueryData($oGui = NULL) {

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
					".$sTableAlias.".*,`ts_ac`.`firstname`, `ts_ac`.`lastname`,`kact`.`title` `activity`,`ksu`.`title` `subject` ".$sFormat."
				FROM
					`{TABLE}` ".$sTableAlias." LEFT JOIN
					`ts_companies_contacts` `ts_ac` ON ".$sTableAlias.".`company_contact_id` = `ts_ac`.`id` LEFT JOIN
					`kolumbus_activity` `kact` ON ".$sTableAlias.".`activity_id` = `kact`.`id` LEFT JOIN
					`kolumbus_subject` `ksu` ON ".$sTableAlias.".`subject_id` = `ksu`.`id`
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

	/**
	 * @param string $sName
	 * @param mixed  $mValue
	 */
	public function  __set($sName, $mValue) {
		if($sName == 'documents') {
			if(is_array($mValue)) {
				$mValue = implode(',', $mValue);
			}
			$this->_aData['documents'] = $mValue;
		} else {
			parent::__set($sName, $mValue);
		}
	}

	/**
	 * @param string $sName
	 * @return array|mixed
	 */
	public function  __get($sName) {
		if($sName == 'documents' ) {
			$aDocuments = explode(',', $this->_aData['documents']);
			return $aDocuments;
		} else {
			return parent::__get($sName);
		}
	}

}
