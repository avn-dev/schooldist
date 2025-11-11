<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class Documents extends AbstractPage {

	public function render(array $aData = array()) {
		$sTemplate = $this->generatePageHeading($this->oApp->t('Documents'));
		return $sTemplate;
	}

	public function getStorageData() {
		
		$aItems = [];
		$oInquiry = $this->oApp->getInquiry(); /** @var \Ext_TS_Inquiry $oInquiry */
		$oSchool = $oInquiry->getSchool();
		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date(false, $this->_oSchool->id);

		$aDocuments = $oInquiry->getDocuments('all', true, true);

		foreach($aDocuments as $oDocument) {
			// Freigegebene Dokumente (aber niemals Nettorechnungen)
			if(
				$oDocument->isReleasedForApp() &&
				strpos($oDocument->type, 'netto') === false
			) {
				$oVersion = $oDocument->getLastVersion();
				$oTemplate = $oVersion->getTemplate();
				$aItems[] = [
					'id' => $oDocument->id,
					'type' => 'document',
					'html' => '<h3>'.$oTemplate->getName() . '</h3><p>' . $this->oApp->t('Date') . ': ' .$oDateFormat->format($oVersion->created) .'</p>',
					'items' => [] // Damit Anchor angezeigt wird
				];
			}
		}

		// Schülerfoto
		if(
			version_compare($this->oApp->getVersion(), '1.1.4', '>=') &&
			$oInquiry->isUploadReleasedForStudentApp('static', 1)
		) {
			$aItems[] = [
				'id' => 'static_1',
				'type' => 'inquiry_upload',
				'html' => '<h3>'.$this->t('Schülerfoto').'</h3>',
				'items' => [] // Damit Anchor angezeigt wird
			];
		}

		// Reisepass
		if(
			version_compare($this->oApp->getVersion(), '1.1.4', '>=') &&
			$oInquiry->isUploadReleasedForStudentApp('static', 2)
		) {
			$aItems[] = [
				'id' => 'static_2',
				'type' => 'inquiry_upload',
				'html' => '<h3>'.$this->t('Reisepass').'</h3>',
				'items' => [] // Damit Anchor angezeigt wird
			];
		}

		// Flexible Uploadfelder (Buchungsdialog)
		if(version_compare($this->oApp->getVersion(), '1.1.4', '>=')) {
			$aUploadFields = \Ext_Thebing_School_Customerupload::getUploadFieldsBySchoolIds([$oSchool->id]);
			foreach($aUploadFields as $oUploadField) {
				if($oInquiry->isUploadReleasedForStudentApp('flex', $oUploadField->id)) {
					$aItems[] = [
						'id' => 'flex_'.$oUploadField->id,
						'type' => 'inquiry_upload',
						'html' => '<h3>'.$oUploadField->name.'</h3>',
						'items' => [] // Damit Anchor angezeigt wird
					];
				}
			}
		}

		return [
			'items' => $aItems
		];
	}

}