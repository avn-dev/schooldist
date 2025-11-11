<?php

use Core\Proxy\WDBasicAbstract;

class Ext_Thebing_Form_Page_Proxy extends WDBasicAbstract {

	/**
	 * @var string
	 */
	protected $sEntityClass = 'Ext_Thebing_Form_Page';

	/**
	 * Gibt das Page-Entity zurück.
	 *
	 * Die Basis-Proxy-Klasse stellt sicher das es der richtige Typ ist (Definition in $this->sEntityClass),
	 * diese Methode sorgt nur für korrektes Type-Hinting.
	 *
	 * @return Ext_Thebing_Form_Page
	 */
	protected function getEntity() {

		return $this->oEntity;

	}

	/**
	 * Gibt die ID der Seite zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::$id
	 * @return integer
	 */
	public function getId() {

		$oEntity = $this->getEntity();
		return $oEntity->id;

	}

	/**
	 * Gibt die Liste aller aktiven Blöcke dieser Seite zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::getBlocks()
	 * @return Ext_Thebing_Form_Page_Block_Proxy[]
	 */
	public function getBlocks() {

		$oEntity = $this->getEntity();
		$aBlocks = $oEntity->getBlocks();

		$aBlocks = array_map(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return new Ext_Thebing_Form_Page_Block_Proxy($oBlock);
		}, $aBlocks);

		return $aBlocks;

	}

	/**
	 * Gibt das Formular zurück zu dem diese Seite gehört.
	 *
	 * @uses Ext_Thebing_Form_Page::getForm()
	 * @return Ext_Thebing_Form_Proxy
	 */
	public function getForm() {

		$oEntity = $this->getEntity();

		$oForm = $oEntity->getForm();
		$oForm = new Ext_Thebing_Form_Proxy($oForm);

		return $oForm;

	}

	/**
	 * Gibt den Titel der Seite in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form_Page::getTitle()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getTitle($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getTitle($sLanguage);

	}

	/**
	 * Gibt den Standard-Titel für diese Seite zurück.
	 *
	 * Der Rückgabewert dieser Methode ist als Fallback-Platzhalter in Templates gedacht.
	 *
	 * @return string
	 */
	public function getDefaultTitle() {

		$sTitle = 'Page #'.$this->getId();
		return $sTitle;

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::getPageDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getPageDataAttributes($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getPageDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * Gibt true zurück wenn diese Seite die erste Seite des Formulars ist, ansonsten false.
	 *
	 * @uses Ext_Thebing_Form_Page::isFirstPage()
	 * @return boolean
	 */
	public function isFirstPage() {

		$oEntity = $this->getEntity();
		return $oEntity->isFirstPage();

	}

	/**
	 * Gibt true zurück wenn diese Seite die letzte Seite des Formulars ist, ansonsten false.
	 *
	 * @uses Ext_Thebing_Form_Page::isLastPage()
	 * @return boolean
	 */
	public function isLastPage() {

		$oEntity = $this->getEntity();
		return $oEntity->isLastPage();

	}

	/**
	 * Gibt den Titel/Text für den Zurück-Button zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form_Page::getPreviousPageButtonText()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getPreviousPageButtonText($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getPreviousPageButtonText($sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für den Zurück-Button zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::getPreviousPageButtonDataAttributres()
	 * @return string
	 */
	public function getPreviousPageButtonDataAttributres() {

		$oEntity = $this->getEntity();
		return $oEntity->getPreviousPageButtonDataAttributres();

	}

	/**
	 * Gibt den Titel/Text für den Weiter-Button zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form_Page::getNextPageButtonText()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getNextPageButtonText($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getNextPageButtonText($sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für den Weiter-Button zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::getNextPageButtonDataAttributres()
	 * @return string
	 */
	public function getNextPageButtonDataAttributres() {

		$oEntity = $this->getEntity();
		return $oEntity->getNextPageButtonDataAttributres();

	}

	/**
	 * Gibt den Titel/Text für den Absenden-Button zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form_Page::getSubmitButtonText()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSubmitButtonText($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getSubmitButtonText($sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für den Absenden-Button zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::getSubmitButtonDataAttributres()
	 * @return string
	 */
	public function getSubmitButtonDataAttributres() {

		$oEntity = $this->getEntity();
		return $oEntity->getSubmitButtonDataAttributres();

	}

	/**
	 * Gibt eine sortierte Liste aller vorhergehenden Seiten zurück.
	 *
	 * @return Ext_Thebing_Form_Page_Proxy[]
	 */
	public function getPreviousPages() {

		$oEntity = $this->getEntity();
		$aPages = $oEntity->getForm()->getPages();

		$aReturn = array();
		foreach($aPages as $oPage) {
			if($oPage->id == $oEntity->id) {
				break;
			}
			$aReturn[] = $oPage;
		}

		return $aReturn;

	}

	/**
	 * Gibt eine sortierte Liste aller achfolgenden Seiten zurück.
	 *
	 * @return Ext_Thebing_Form_Page_Proxy[]
	 */
	public function getFollowingPages() {

		$oEntity = $this->getEntity();
		$aPages = $oEntity->getForm()->getPages();

		$bAddPages = false;
		$aReturn = array();
		foreach($aPages as $oPage) {
			if($oPage->id == $oEntity->id) {
				$bAddPages = true;
				continue;
			}
			if($bAddPages) {
				$aReturn[] = $oPage;
			}
		}

		return $aReturn;

	}

}
