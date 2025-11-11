<?php

use Core\Proxy\WDBasicAbstract;

class Ext_Thebing_Form_Page_Block_Proxy extends WDBasicAbstract {

	/**
	 * @var string
	 */
	protected $sEntityClass = 'Ext_Thebing_Form_Page_Block';

	/**
	 * Gibt das Block-Entity zurück.
	 *
	 * Die Basis-Proxy-Klasse stellt sicher das es der richtige Typ ist (Definition in $this->sEntityClass),
	 * diese Methode sorgt nur für korrektes Type-Hinting.
	 *
	 * @return Ext_Thebing_Form_Page_Block
	 */
	protected function getEntity() {

		return $this->oEntity;

	}

	/**
	 * Gibt die ID des Blocks zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::$id
	 * @return integer
	 */
	public function getId() {

		$oEntity = $this->getEntity();
		return $oEntity->id;

	}

	/**
	 * Gibt die Seite zurück zu der dieser Block gehört.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getPage()
	 * @return Ext_Thebing_Form_Page_Proxy
	 */
	public function getPage() {

		$oEntity = $this->getEntity();

		$oPage = $oEntity->getPage();
		$oPage = new Ext_Thebing_Form_Page_Proxy($oPage);

		return $oPage;

	}

	/**
	 * Gibt die Unterart des Blocks zurück.
	 *
	 * Wenn der Block keine Unterart hat wird ein leerer String zurück gegeben.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getSubtype()
	 * @return string
	 */
	public function getSubtype() {

		$oEntity = $this->getEntity();
		return $oEntity->getSubtype();

	}

	/**
	 * Gibt true zurück wenn der Block einen Eltern-Block hat, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getParentBlock()
	 * @uses Ext_Thebing_Form_Page_Block::isChildBlock()
	 * @return boolean
	 */
	public function isChildBlock() {

		$oEntity = $this->getEntity();
		return $oEntity->isChildBlock();

	}

	/**
	 * Gibt den Eltern-Block dieses Blocks zurück oder null wenn es keinen
	 * Eltern-Block gibt (dann gehört der Block direkt zur Seite).
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::isChildBlock()
	 * @uses Ext_Thebing_Form_Page_Block::getParentBlock()
	 * @return null|Ext_Thebing_Form_Page_Block_Proxy
	 */
	public function getParentBlock() {

		$oEntity = $this->getEntity();

		$oBlock = $oEntity->getParentBlock();

		if($oBlock !== null) {
			$oBlock = new Ext_Thebing_Form_Page_Block_Proxy($oBlock);
		}

		return $oBlock;

	}

	/**
	 * Gibt true zurück wenn der Block Kind-Blöcke hat, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getChildBlocks()
	 * @uses Ext_Thebing_Form_Page_Block::hasChildBlocks()
	 * @return boolean
	 */
	public function hasChildBlocks() {

		$oEntity = $this->getEntity();
		return $oEntity->hasChildBlocks(true);

	}

	/**
	 * Gibt die Liste mit allen aktiven Blöcken zurück die direkte Kinder dieses Blocks sind.
	 *
	 * Wenn dieser Block ein fester Block ist werden virtuelle Kind-Blöcke zurück gegeben um die nötigen
	 * Eingabefelder generieren zu können.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::hasChildBlocks()
	 * @uses Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @return Ext_Thebing_Form_Page_Block_Proxy[]
	 */
	public function getChildBlocks() {

		$oEntity = $this->getEntity();
		$aChildBlocks = $oEntity->getChildBlocks(true);

		$aChildBlocks = array_map(function(Ext_Thebing_Form_Page_Block $oChildBlock) {
			return new Ext_Thebing_Form_Page_Block_Proxy($oChildBlock);
		}, $aChildBlocks);

		return $aChildBlocks;

	}

	/**
	 * Gibt true zurück wenn der Block mehrere Bereiche haben kann, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getAreaWidths()
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getChildBlocksForArea()
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getAreaType()
	 * @uses Ext_Thebing_Form_Page_Block::hasAreas()
	 * @return boolean
	 */
	public function hasAreas() {

		$oEntity = $this->getEntity();
		return $oEntity->hasAreas();

	}

	/**
	 * Gibt ein Array mit den Breiten der einzelnen Bereiche zurück.
	 *
	 * Die Werte sind Prozentangaben, Anordnung von links nach rechts.
	 *
	 * Die Array-Keys werden von 0 aufsteigend vergeben und können direkt als Parameter für
	 * Ext_Thebing_Form_Page_Block_Proxy::getChildBlocksForArea() verwendet werden.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getChildBlocksForArea()
	 * @uses Ext_Thebing_Form_Page_Block::getAreaWidths()
	 * @return integer[]
	 */
	public function getAreaWidths() {

		$oEntity = $this->getEntity();
		return $oEntity->getAreaWidths();

	}

	/**
	 * Gibt die Art des Mehrbereichts-Blocks zurück (Standard, Kurse, Unterkünfte, usw.).
	 *
	 * Wenn der Block nicht mehrere Bereiche hat wird ein leerer String zurück gegeben.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::hasAreas()
	 * @uses Ext_Thebing_Form_Page_Block::getAreaType()
	 * @return string
	 */
	public function getAreaType() {

		$oEntity = $this->getEntity();
		return $oEntity->getAreaType();

	}

	/**
	 * Gibt die Liste mit allen aktiven Blöcken zurück die direkte Kinder dieses Blocks sind und in den
	 * angegebenen Bereich gehören.
	 *
	 * Bereiche werden von 0 aufsteigend nummeriert, von links nach rechts.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::hasChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block_Proxy::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getAreaWidths()
	 * @uses Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @param integer $iAreaNumber
	 * @return Ext_Thebing_Form_Page_Block_Proxy[]
	 */
	public function getChildBlocksForArea($iAreaNumber) {

		$oEntity = $this->getEntity();
		$aChildBlocks = $oEntity->getChildBlocksForArea($iAreaNumber, true);

		$aChildBlocks = array_map(function(Ext_Thebing_Form_Page_Block $oChildBlock) {
			return new Ext_Thebing_Form_Page_Block_Proxy($oChildBlock);
		}, $aChildBlocks);

		return $aChildBlocks;

	}

	/**
	 * Gibt den Titel des Blocks in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block keinen Titel haben kann wird ein leerer String zurück gegeben.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getDefaultTitle()
	 * @uses Ext_Thebing_Form_Page_Block::getTitle()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getTitle($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getTitle($sLanguage);

	}

	/**
	 * Gibt den Standard-Titel für diesen Block zurück.
	 *
	 * Der Rückgabewert dieser Methode ist als Fallback-Platzhalter in Templates gedacht.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getTitle()
	 * @return string
	 */
	public function getDefaultTitle() {

		if(System::d('debugmode') == 0) {
			return '';
		}

		$oEntity = $this->getEntity();
		$sTitle = '';

		if($this->isFixedBlock()) {
			$sTitle .= 'Fixed-';
		} elseif($this->isInputBlock()) {
			$sTitle .= 'Input-';
		} elseif($this->isHeadlineBlock()) {
			$sTitle .= 'Headline-';
		} elseif($this->isTextBlock()) {
			$sTitle .= 'Text-';
		}

		if($oEntity->isVirtualBlock()) {
			$sTitle = 'Virtual-'.$sTitle.'Block';
		} else {
			$sTitle .= 'Block #'.$this->getId();
		}

		$sSubtype = $this->getSubtype();
		if(strlen($sSubtype) > 0) {
			$sTitle .= ' ('.$sSubtype.')';
		}

		return $sTitle;

	}

	/**
	 * Gibt die Fehlermeldung die angezeigt werden soll wenn das Eingabefeld in diesem Block falsch ausgefüllt wurde
	 * in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block kein Eingabefeld hat wird ein leerer String zurück gegeben.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getErrorMessage()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getErrorMessage($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getErrorMessage($sLanguage);

	}

	/**
	 * Gibt true zurück wenn dieser Block eine Infomeldung hat, ansonsten false.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @uses Ext_Thebing_Form_Page_Block_Proxy::getInfoMessage()
	 * @param string $sLanguage
	 * @return string
	 */
	public function hasInfoMessage($sLanguage = null) {

		$oEntity = $this->getEntity();
		$sMessage = $this->getInfoMessage($sLanguage);

		if(
			(
				strlen($sMessage) > 0
			) && (
				$oEntity->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT ||
				$oEntity->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE ||
				$oEntity->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT ||
				$oEntity->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA ||
				$oEntity->block_id == Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX
			)
		) {
			return true;
		}

		return false;

	}

	/**
	 * Gibt die Infomeldung die zusätzlich zum Eingabefeld dieses Blocks angezeigt werden soll
	 * in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block kein Eingabefeld hat wird ein leerer String zurück gegeben.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getInfoMessage()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getInfoMessage($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getInfoMessage($sLanguage);

	}

	/**
	 * Gibt true zurück wenn es sich um einen Überschrift-Block handelt, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getHeadlineLevel()
	 * @uses Ext_Thebing_Form_Page_Block::isHeadlineBlock()
	 * @return boolean
	 */
	public function isHeadlineBlock() {

		$oEntity = $this->getEntity();
		return $oEntity->isHeadlineBlock();

	}

	/**
	 * Gibt das Überschrift-Level zurück oder 0 wenn der Block kein Überschrift-Block ist.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::isHeadlineBlock()
	 * @uses Ext_Thebing_Form_Page_Block::getHeadlineLevel()
	 * @return integer
	 */
	public function getHeadlineLevel() {

		$oEntity = $this->getEntity();
		return $oEntity->getHeadlineLevel();

	}

	/**
	 * Gibt true zurück wenn es sich um einen Text-Block handelt, ansonsten false.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::isTextBlock()
	 * @return boolean
	 */
	public function isTextBlock() {

		$oEntity = $this->getEntity();
		return $oEntity->isTextBlock();

	}

	/**
	 * Gibt den Text des Blocks in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block keinen Text hat wird ein leerer String zurück gegeben.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getBlockText()
	 * @param string $sLanguage
	 * @return string
	 */
	public function getBlockText($sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getBlockText($sLanguage);

	}

	/**
	 * Gibt true zurück wenn das Eingabefeld dieses Blocks ein Pflichtfeld ist, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isRequired() {

		$oEntity = $this->getEntity();
		return (bool)$oEntity->isRequired();

	}

	/**
	 * CSS-Klasse die diesem Block zugeordnet werden soll (kann leer sein).
	 *
	 * @uses Ext_Thebing_Form_Page_Block::$css_class
	 * @return string
	 */
	public function getCssClass() {

		$oEntity = $this->getEntity();

		if($oEntity->isVirtualBlock()) {
			$sClass = constant(get_class($oEntity).'::SUBTYPE');
			return 'block-'.str_replace('_', '-', $sClass);
		}

		return (string)$oEntity->css_class;

	}

	/**
	 * Gibt true zurück wenn es sich um einen Eingabe-Block handelt, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getInputBlockType()
	 * @uses Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @return boolean
	 */
	public function isInputBlock() {

		$oEntity = $this->getEntity();
		return $oEntity->isInputBlock();

	}

	/**
	 * Gibt die Art des Eingabefelds zurück.
	 *
	 * Mögliche (gültige) Rückgabewerte:
	 * - text
	 * - textarea
	 * - select
	 * - checkbox
	 * - date
	 * - upload
	 * - download
	 *
	 * Standard-Rückgabewert ist "unknown (<Block-Art>)".
	 *
	 * Diese Methode ist zur Abfrage innerhalb von Templates o.ä. gedacht, damit man nicht auf
	 * die Konstanten (Software-Interne Details) oder die numerischen Werte (nicht aussagekräftig
	 * und ebenfalls Software-Interne Details) abfragen muss, sondern einen lesbaren und einfach
	 * verständlichen String hat.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::isInputBlock()
	 * @uses Ext_Thebing_Form_Page_Block::$block_id
	 * @return string
	 */
	public function getInputBlockType() {

		$oEntity = $this->getEntity();
		$iBlockId = $oEntity->block_id;
		
		switch($iBlockId) {
			case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
				return 'text';
			case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
				return 'textarea';
			case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
				return 'select';
			case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
				return 'checkbox';
			case Ext_Thebing_Form_Page_Block::TYPE_DATE:
				return 'date';
			case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
				return 'upload';
			case Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD:
				return 'download';
			case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
				return 'multiselect';
			case Ext_Thebing_Form_Page_Block::TYPE_YESNO:
				return 'yesno';
		}

		return 'unknown ('.$iBlockId.')';

	}

	/**
	 * Gibt den Namen, der für das Eingabefeld im HTML verwendet werden soll, zurück.
	 *
	 * Wenn der Block kein Eingabefeld darstellt wird ein leerer String zurück gegeben.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getInputBlockName()
	 * @return string
	 */
	public function getInputBlockName() {

		$oEntity = $this->getEntity();
		return $oEntity->getInputBlockName();

	}

	/**
	 * Gibt true zurück wenn es sich um einen festen Block handelt, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::getFixedBlockType()
	 * @uses Ext_Thebing_Form_Page_Block::isFixedBlock()
	 * @return boolean
	 */
	public function isFixedBlock() {

		$oEntity = $this->getEntity();
		return $oEntity->isFixedBlock();

	}

	/**
	 * Gibt die Art des festen Blocks zurück.
	 *
	 * Mögliche (gültige) Rückgabewerte:
	 * - courses
	 * - accommodations
	 * - transfers
	 * - insurances
	 * - prices
	 *
	 * Standard-Rückgabewert ist "unknown (<Block-Art>)".
	 *
	 * Diese Methode ist zur Abfrage innerhalb von Templates o.ä. gedacht, damit man nicht auf
	 * die Konstanten (Software-Interne Details) oder die numerischen Werte (nicht aussagekräftig
	 * und ebenfalls Software-Interne Details) abfragen muss, sondern einen lesbaren und einfach
	 * verständlichen String hat.
	 *
	 * @see Ext_Thebing_Form_Page_Block_Proxy::isFixedBlock()
	 * @uses Ext_Thebing_Form_Page_Block::$block_id
	 * @return string
	 */
	public function getFixedBlockType() {

		$oEntity = $this->getEntity();
		$iBlockId = $oEntity->block_id;

		switch($iBlockId) {
			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
				return 'courses';
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
				return 'accommodations';
			case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:
				return 'transfers';
			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
				return 'insurances';
			case Ext_Thebing_Form_Page_Block::TYPE_PRICES:
				return 'prices';
		}

		return 'unknown ('.$iBlockId.')';

	}

	/**
	 * Gibt true zurück wenn es sich um einen Spezial-Block handelt, ansonsten false.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::isSpecialBlock()
	 * @return boolean
	 */
	public function isSpecialBlock() {

		$oEntity = $this->getEntity();
		return $oEntity->isSpecialBlock();

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getBlockDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getBlockDataAttributes($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getBlockDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getTitleDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getTitleDataAttributes($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getTitleDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * @see \Ext_Thebing_Form_Page_Block::isShowingLabelAsPlaceholder()
	 * @return bool
	 */
	public function isShowingLabelAsPlaceholder() {
		return $this->getEntity()->isShowingLabelAsPlaceholder();
	}
	
	/**
	 * Gibt die Liste der gültigen Select-Optionen in der angegebenen Sprache zurück.
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string[] 
	 */
	public function getSelectOptions($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getSelectOptions($mSchool, $sLanguage);
		

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getInputDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getInputDataAttributes($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getInputDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * Gibt true zurück wenn der Block für die angegebene Schul-/Sprach-Kombination verfügbar ist, ansonsten false.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::isAvailable()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return boolean
	 */
	public function isAvailable($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->isAvailable($mSchool, $sLanguage);

	}

	/**
	 * Gibt den Text für den Hinzufügen-Button eines duplizierbaren Bereichs zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block nicht zu einem duplizierbaren Bereich gehört wird ein leerer String zurück gegeben.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getDuplicateAddButtonText($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getDuplicateAddButtonText($mSchool, $sLanguage);

	}

	/**
	 * Gibt den Text für den Entfernen-Button eines duplizierbaren Bereichs zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block nicht zu einem duplizierbaren Bereich gehört wird ein leerer String zurück gegeben.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getDuplicateRemoveButtonText($mSchool, $sLanguage = null) {

		$oEntity = $this->getEntity();
		return $oEntity->getDuplicateRemoveButtonText($mSchool, $sLanguage);

	}

	/**
	 * type-Attribut für normales Text-Input
	 *
	 * @return string
	 */
	public function getInputTextType() {

		$oEntity = $this->getEntity();
		if($oEntity->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_EMAIL) {
			return 'email';
		} elseif(
			$oEntity->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE ||
			$oEntity->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE_OFFICE ||
			$oEntity->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE_MOBILE ||
			$oEntity->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_FAX
		) {
			return 'tel';
		} else {
			return 'text';
		}

	}

}
