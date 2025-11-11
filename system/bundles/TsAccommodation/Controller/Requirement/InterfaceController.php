<?php

namespace TsAccommodation\Controller\Requirement;

use TsAccommodation\Entity\Requirement\Document;
use TsAccommodation\Service\CheckRequirement;
use \TsAccommodation\Entity\Requirement;
use \TsAccommodation\Entity\Member;

class InterfaceController extends \MVC_Abstract_Controller {

	protected $_sViewClass = '\MVC_View_Smarty';

	/**
	 * Erzeugt die Ansicht der Voraussetzungen
	 * @throws \Exception
	 * @return void
	 */
	public function ViewAction() {

		$bSave = false;
		$sClass = $this->_oRequest->get('entity');
		$iRequirement = (int)$this->_oRequest->get('id');
		$iAccommodationProvider = (int)$this->_oRequest->input('accommodation');

		$oAccommodation = \Ext_Thebing_Accommodation::getInstance($iAccommodationProvider);

		$oRequirement = Requirement::getInstance($iRequirement);
		$sRequiredFrom = $oRequirement->requirement;

		$aMembersByAge = Member::getRepository()->findByAge($oAccommodation, $oRequirement->age);

		$aMembers = [];

		if(!empty($aMembersByAge)) {

			$oNameFormat = new \Ext_Thebing_Gui2_Format_Name();

			foreach($aMembersByAge as $oMember) {

				$oColumn = new \stdClass();
				$sValue = $oNameFormat->format('', $oColumn, $oMember->aData);

				$aMembers[$oMember->id] = $sValue;

			}
		}

		// Wenn ein neues Dokument hinzugefügt wird
		if($this->_oRequest->get('addbutton')) {

			$oDocument = $oAccommodation->getJoinedObjectChild('requirement_documents');
			$oDocument->requirement_id = $iRequirement;
			$oDocument->accommodation_provider_id = $iAccommodationProvider;
			$oDocument->save();

		}

		// Wenn Dokumente gespeichert werden
		if($this->_oRequest->get('savebutton')) {
			$bSave = true;
			$this->saveRequirementDocument($oAccommodation, $oRequirement);
		}

		$aDocuments = $oAccommodation->getJoinedObjectChilds('requirement_documents');

		if(!empty($aDocuments)) {
			$aDocuments = array_filter($aDocuments, function($oDocument) use($iRequirement) {
				return $oDocument->requirement_id == $iRequirement;
			});
		}

		$oCheckRequirement = new CheckRequirement($oAccommodation, $oRequirement);
		$oCheckRequirement->check();

		if(empty($aDocuments)) {
			$aDocuments[] = $oAccommodation->getJoinedObjectChild('requirement_documents');
		}

		$sDatepickerFormat = \Ext_Thebing_Format::getDateFormat(null, 'backend_datepicker_format');

		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date();

		$this->set('sDatepickerFormat', $sDatepickerFormat);

		$this->set('sRequiredFrom', $sRequiredFrom);
		$this->set('aMembers', $aMembers);

		$this->set('bDocumentMissing', $oCheckRequirement->hasMissingDocument());
		$this->set('bDocumentExpired', $oCheckRequirement->hasExpiredDocument());

		$this->set('aMembersWithMissingDocuments', $oCheckRequirement->getMembersWithMissingDocuments());
		$this->set('aMembersWithExpiredDocuments', $oCheckRequirement->getMembersWithExpiredDocuments());

		$this->set('oDateFormat', $oDateFormat);
		$this->set('sClass', $sClass);
		$this->set('iId', $iRequirement);

		$this->set('oRequirement', $oRequirement);

		$this->set('oController', $this);
		$this->set('aDocuments', $aDocuments);
		$this->set('bSave', $bSave);

	}

	/**
	 * @todo Exception abfangen
	 * Übernimmt das Speichern der Nachweise zu einer Voraussetzung
	 *
	 * @param \Ext_Thebing_Accommodation $oAccommodation
	 * @param Requirement $oRequirement
	 */
	public function saveRequirementDocument(\Ext_Thebing_Accommodation $oAccommodation, Requirement $oRequirement) {

		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date();
		//$oRequirement = \TsAccommodation\Entity\Requirement::getInstance($iRequirement);

		if($this->_oRequest->input('save')) {

			$aFiles = $this->_oRequest->getFilesData();

			foreach($this->_oRequest->input('save') as $iKey=>$aSaveFields)  {

				$aSaveFields['valid'] = $oDateFormat->convert($aSaveFields['valid']);

				/** @var Document $oDocument */
				$oDocument = $oAccommodation->getJoinedObjectChild('requirement_documents', $iKey);

				if(is_file($aFiles['save']['tmp_name'][$iKey]['file'])) {
					$sFile = $oDocument->saveFile($aFiles['save']['name'][$iKey]['file'], $aFiles['save']['tmp_name'][$iKey]['file']);

					if(!empty($sFile)) {
						$oDocument->file = $sFile;
					}
				}

				$oDocument->name = $aSaveFields['name'];
				$oDocument->valid = $aSaveFields['valid'];
				$oDocument->always_valid = $aSaveFields['always_valid'];
				$oDocument->requirement_id = $oRequirement->id;
				$oDocument->members = $aSaveFields['members'];
				$oDocument->accommodation_provider_id = $oAccommodation->id;
				$oDocument->save();

			}
		}

		if($this->_oRequest->input('delete')) {

			foreach($this->_oRequest->input('delete') as $iDocumentId)  {
				$oDocument = $oAccommodation->getJoinedObjectChild('requirement_documents', $iDocumentId);
				$oDocument->delete();
			}
		}

		$oAccommodation->updateRequirementStatus();

	}

}