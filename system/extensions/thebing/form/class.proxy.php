<?php

use Core\Proxy\WDBasicAbstract;

class Ext_Thebing_Form_Proxy extends WDBasicAbstract {

	/**
	 * @var string
	 */
	protected $sEntityClass = 'Ext_Thebing_Form';

	/**
	 * Gibt das Formular-Entity zurück.
	 *
	 * Die Basis-Proxy-Klasse stellt sicher das es der richtige Typ ist (Definition in $this->sEntityClass),
	 * diese Methode sorgt nur für korrektes Type-Hinting.
	 *
	 * @return Ext_Thebing_Form
	 */
	protected function getEntity() {

		return $this->oEntity;

	}

	/**
	 * Gibt die ID des Formulars zurück.
	 *
	 * @uses Ext_Thebing_Form::$id
	 * @return integer
	 */
	public function getId() {

		$oEntity = $this->getEntity();
		return $oEntity->id;

	}

	/**
	 * Gibt den Titel des Formulars zurück.
	 *
	 * @uses Ext_Thebing_Form::$title
	 * @return string
	 */
	public function getTitle() {

		$oEntity = $this->getEntity();
		return $oEntity->title;

	}

	/**
	 * Gibt die Liste der ausgewählten Sprachen zurück.
	 *
	 * @uses Ext_Thebing_Form::getSelectedLanguages()
	 * @return string[]
	 */
	public function getSelectedLanguages() {

		$oEntity = $this->getEntity();
		return $oEntity->getSelectedLanguages();

	}

	/**
	 * Gibt die ausgewählte Standardsprache zurück.
	 *
	 * @uses Ext_Thebing_Form::$default_language
	 * @return string
	 */
	public function getDefaultLanguage() {

		$oEntity = $this->getEntity();
		return $oEntity->default_language;

	}

	/**
	 * Gibt die Liste aller Formularseiten zurück.
	 *
	 * @uses Ext_Thebing_Form::getPages()
	 * @return Ext_Thebing_Form_Page_Proxy[]
	 */
	public function getPages() {

		$oEntity = $this->getEntity();
		$aPages = $oEntity->getPages();

		$aPages = array_map(function(Ext_Thebing_Form_Page $oPage) {
			return new Ext_Thebing_Form_Page_Proxy($oPage);
		}, $aPages);

		return $aPages;

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form::getFormDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getFormDataAttributes($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getFormDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für die Erfolgs-Seite zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form::getSuccessPageDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSuccessPageDataAttributes($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getSuccessPageDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * Gibt den Titel für die Erfolgs-Seite zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form::getSuccessPageTitle()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSuccessPageTitle($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getSuccessPageTitle($sLanguage);

	}

	/**
	 * @TODO Entfernen (wurde vorher direkt im Template benutzt, geht aber jetzt über AJAX und kann auch keine Platzhalter)
	 * @deprecated
	 *
	 * Gibt den Text für die Erfolgs-Seite zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form::getSuccessPageText()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSuccessPageText($sLanguage = null) {
		return 'DEPRECATED';
	}

	/**
	 * @return bool
	 */
	public function isIncludingDefaultCSS() {
		$oEntity = $this->getEntity();
		return (bool)$oEntity->use_css;
	}

}
