<?php

namespace TsMobile\Generator;

use TsMobile\Service\AbstractApp;

abstract class AbstractPage {

	/**
	 * @var \TsMobile\Service\AbstractApp|\TsMobile\Service\App\Student
	 */
	protected $oApp = null;

	/**
	 * @var \Ext_Thebing_School
	 */
	protected $_oSchool = null;
	
	protected $_sInterfaceLanguage = 'en';

	/**
	 * Konstruktor
	 * 
	 * @param \TsMobile\Service\AbstractApp $oApp
	 */
	public function __construct(AbstractApp $oApp) {
		$this->oApp = $oApp;
		$this->_sInterfaceLanguage = $oApp->getInterfaceLanguage();
		if($this->oApp->getUser()) {
			$this->_oSchool = $oApp->getSchool();
		}
	}

	public static function getTemplatePath() {
		return \Util::getDocumentRoot().'system/bundles/TsMobile/Resources/views/pages/';
	}
	
	/**
	 * liefert fertigen HTML-Code für die Seite zurück
	 * 
	 * @TODO SMARTY?
	 * 
	 * @param array $aData
	 * @return string
	 */
	public function render(array $aData = array()) {
		return '';
	}
	
	/**
	 * Liefert Daten, die in den LocalStorage der App geschrieben werden sollen
	 * 
	 * @return array
	 */
	public function getStorageData() {
		return array();
	}

	/**
	 * Liefert Dateien, die zur Seite gehören
	 *
	 * @return array
	 */
	public function getFileData() {
		return array();
	}

	/**
	 * Generiert die Überschrift für eine Seite
	 *
	 * @param string $sHeading
	 * @return string
	 */
	protected function generatePageHeading($sHeading) {
		return '<h2>'.$sHeading.'</h2>';
	}
	
	/**
	 * Generiert einen Block mit Überschrift und Inhalt
	 *
	 * @deprecated
	 * @param string $sHeading
	 * @param string $sContent
	 * @return string
	 */
	protected function generatePageBlock($sHeading, $sContent='') {
		return '<h3>'.$sHeading.'</h3>' . $sContent;
	}	
	
	/**
	 * Baut einen Block zusammen
	 *
	 * @deprecated
	 * @param string $sTitle
	 * @param string $sContent
	 * @return string
	 */
	protected function generateBlock($sTitle, $sContent) {
		
		$sTemplate = '<div class="ui-body ui-body-a ui-corner-all ui-shadow">';

		if(!empty($sTitle)) {
			$sTemplate .= '<h4>'.$sTitle.'</h4>';
		}

		$sTemplate .= '<p>'.$sContent.'</p>';		
		$sTemplate .= '</div>';
		
		return $sTemplate;
	}
	
	/**
	 * Holt eine Übersetzung aus den Frontend-Übersetzungen der Installation
	 * 
	 * @param string $sTranslation
	 * @return string
	 */
	public function t($sTranslation) {
		return $this->oApp->t($sTranslation);
	}
	
	/**
	 * Formatiert ein Datum anhand den Schuleinstellungen
	 * 
	 * @param mixed $mDate
	 * @return string
	 */
	public function formatDate($mDate) {
		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $this->_oSchool->id);
		return $oDateFormat->formatByValue($mDate);
	}

	/**
	 * Titel für eine Woche
	 *
	 * @TODO Müsste eigentlich Kurs-Starttag beachten
	 *
	 * @param \DateTime $dMonday
	 * @return string
	 */
	public function formatWeekTitle(\DateTime $dMonday) {

		$dFriday = clone $dMonday;
		$dFriday->modify('next friday');

		// ucfirst() weil Frontend-Translations dumm sind
		return sprintf('%s %d, %s – %s',  ucfirst($this->t('Week')), $dMonday->format('W'), $this->formatDate($dMonday), $this->formatDate($dFriday));

	}
	
}

