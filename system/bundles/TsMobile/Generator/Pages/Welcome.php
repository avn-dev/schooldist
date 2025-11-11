<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class Welcome extends AbstractPage {
	
	public function render(array $aData = array()) {
		
		$oSchool = $this->oApp->getSchool();
//		$oAppConfig = $oSchool->getAppSettingsConfig();
		$aInquiries = $this->oApp->getUser()->getInquiries(false, true);

		$sSchoolText = $oSchool->getMeta('student_app_welcome_text_'.$this->_sInterfaceLanguage);
//		$sSchoolText = (string)$oAppConfig->getValue('welcome_text_student', $this->_sInterfaceLanguage, true);
//		$sImage = $oSchool->getMobileAppImage(true);
	
		$sTemplate = $this->generatePageHeading($this->oApp->t('Welcome'));

		// Select für die Buchung
		if(
			count($aInquiries) > 1 &&
			version_compare($this->oApp->getVersion(), '1.1.8', '>=')
		) {
			$this->createInquirySelect($aInquiries, $sTemplate);
		}

		if(!empty($sImage)) {
			// div: jQuery Mobile Workaround
			// http://forum.jquery.com/topic/adding-images-to-list-that-are-not-thumbnails-or-icons
			$sTemplate .= '<div><img style="width: 100%" src="storage://image/'.$oSchool->id.'/?additional=school_image"></div>';
		}

		// Text muss ebenso in Container stehen, ansonsten ignoriert jQuery Mobile den Text
		if(!in_array(mb_substr($sSchoolText, 0, 2), array('<p', '<d'))) {
			$sSchoolText = '<p>'.$sSchoolText.'</p>';
		}

		$sTemplate .= $sSchoolText;
		
		return $sTemplate;
	}

	/**
	 * Select für Buchungsauswahl generieren
	 *
	 * @param array $aInquiries
	 * @param string $sTemplate
	 */
	protected function createInquirySelect(array $aInquiries, &$sTemplate) {

		// Entweder dem Zeitraum entsprechend erstbeste Buchung oder aus der App übergebene (Select)
		$oCurrentInquiry = $this->oApp->getInquiry();

		$sSelect = '<select class="booking-select" data-native-menu="false" data-mini="true" data-selected-originally="'.$oCurrentInquiry->id.'">';

		foreach($aInquiries as $oInquiry) {

			$sLabel = $this->t('Booking').': ';

			if(
				$oInquiry->service_from !== '0000-00-00' &&
				$oInquiry->service_until !== '0000-00-00'
			) {
				$sLabel .= $this->formatDate($oInquiry->service_from).' – '.$this->formatDate($oInquiry->service_until);
			} else {
				$sLabel .= $this->formatDate($oInquiry->created);
			}

			$sLabel .= ', '.$oInquiry->getSchool()->getName();

			$sSelected = '';
			if($oInquiry->id == $oCurrentInquiry->id) {
				$sSelected = 'selected="selected"';
			}

			$sSelect .= '<option value="'.$oInquiry->id.'" '.$sSelected.'>'.$sLabel.'</option>';
		}

		$sSelect .= '</select>';
		$sTemplate .= '<p>'.$sSelect.'</p>';

	}

	public function getFileData() {

		$oSchool = $this->oApp->getSchool();
		$sFile = $oSchool->getFirstFile(\TsActivities\Entity\Activity::APP_IMAGE_TAG);

		if(!empty($sFile)) {
			return array(
				array(
					'type' => 'image', // Controller
					'additional' => 'school_image', // Sub-Typ
					'id' => $oSchool->id // Eindeutige ID unter Controller und Sub-Typ
				)
			);
		}

		return array();
	}

}