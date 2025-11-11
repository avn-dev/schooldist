<?php

namespace TsAccommodation\Entity\Requirement;

use Core\Helper\DateTime;

class Document extends \WDBasic {

	protected $_sTable = 'ts_accommodation_providers_requirements_documents';
	protected $_sTableAlias = 'ts_aprd';

	protected $sFilePath = 'accommodation_providers/requirements/';

	protected $_aJoinedObjects = [
		'requirements_accommodation' => [
			'class' => 'Ext_Thebing_Accommodation',
			'key' => 'accommodation_provider_id',
			'check_active' => true,
			'type' => 'parent',
		]
	];

	protected $_aJoinTables = [
		'members' => [
			'table' => 'ts_accommodation_providers_requirements_documents_to_members',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'document_id',
			'class' => '\TsAccommodation\Entity\Member',
			'autoload' => true,
			'on_delete' => 'no_action'
		]
	];

	public function delete() {

		$bDelete = parent::delete();

		if(
			$bDelete &&
			$this->bPurgeDelete
		) {
			$sPath = $this->getPath(true).$this->file;
			if(is_file($sPath)) {
				unlink($sPath);
			}
		}

		return $bDelete;

	}

	/**
	 * @param bool $bDocumentRoot
	 * @return string
	 */
	public function getPath($bDocumentRoot=true) {

		$sClientPath = \Ext_Thebing_Util::getSecureDirectory($bDocumentRoot);

		$sPath = $sClientPath.$this->sFilePath;

		return $sPath;
	}

	/**
	 * @param $sName
	 * @param $sSource
	 * @return mixed|string
	 */
	public function saveFile($sName, $sSource) {

		$sName = \Util::getCleanFilename($sName);

		$sPath = $this->getPath();

		\Util::checkDir($sPath);

		$sDestination = $sPath.$sName;

		$bSuccess = move_uploaded_file($sSource, $sDestination);

		if($bSuccess === true) {
			return $sName;
		}

	}

	/**
	 * @return string
	 */
	public function getFileUrl() {

		$sUrl = $this->getPath(false).$this->file;

		return $sUrl;
	}

	/**
	 * Überprüfen, ob ein Dokument gültig ist, indem das Enddatum mit dem jetzigen Datum bzw. dem übergebenen Datum verglichen wird
	 *
	 * @param $dEnd
	 * @return bool
	 */
	public function isValid(\DateTime $dEnd = null) {

		if($this->always_valid) {

			return true;

		} else {

			$dValid = new DateTime($this->valid);

			$dComparedWith = new DateTime();

			//Wenn kein Enddatum vom Matching übergeben wird, wird mit dem jetzigen Datum verglichen
			if($dEnd !== null) {
				$dComparedWith = $dEnd;
			}

			if($dValid >= $dComparedWith) {
				return true;
			}

		}

		return false;

	}

	public function save() {

		parent::save();

		/** @var \Ext_Thebing_Accommodation $oAccommodation */
		$oAccommodation = $this->getJoinedObject('requirements_accommodation');
		$oAccommodation->updateRequirementStatus();

		return $this;
	}

}