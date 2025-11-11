<?php

/**
 * @deprecated
 */
abstract class Ext_Thebing_Management_Statistic_Static_Abstract {
	public $dFrom;
	public $dUntil;

	protected $_aSchools = array();
	protected $_aFilter = array();
	protected $_aColumns = array();
	protected $_sLanguage;

	abstract public function render();

	/**
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 */
	public function __construct(\DateTime $dFrom, \DateTime $dUntil) {
		$this->dFrom = $dFrom;
		$this->dUntil = $dUntil;

		if(Ext_Thebing_System::isAllSchools()) {
			$oFirstSchool = Ext_Thebing_Client::getFirstSchool();
			$this->_sLanguage = $oFirstSchool->getLanguage();
			$this->_aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);
		} else {
			$oCurrentSchool = Ext_Thebing_School::getSchoolFromSession();
			$this->_sLanguage = $oCurrentSchool->getLanguage();
			$this->_aSchools = array($oCurrentSchool);
		}
	}

	/**
	 * Definiert den Namen der Statistik – sollte daher immer überschrieben werden!
	 * @return string
	 */
	public static function getTitle() {
		return '';
	}

	/**
	 * Gibt an, ob diese Statistik exportierbar ist (zeigt XSL-Icon an)
	 * @return bool
	 */
	public static function isExportable() {
		return false;
	}

	/**
	 * Gibt an, ob diese Statistik absolut ist (aktiviert Datumsfilter)
	 * @return bool
	 */
	public static function isAbsolute() {
		return true;
	}

	/**
	 * Gibt an, ob das Währungs-Select im Filter angezeigt werden soll
	 * @return bool
	 */
	public static function canConvertCurrency() {
		return false;
	}

	/**
	 * Zeigt die Statistiken-Seite an
	 */
	public static function display() {
		//Admin_Html::loadAdminHeader(Ext_Thebing_Management_Statistic_Gui2::getAdminHeaderOptions());

		$oSmarty = new SmartyWrapper();

		$oGuiHtml = new Ext_Gui2_Html(new Ext_Thebing_Gui2()); // Ext_Thebing_Gui2 benötigt wegen JS-Includes für statistic.js
		$oSmarty->assign('aOptions', $oGuiHtml->generateHtmlHeader());
		$oSmarty->assign('sDateFormat', (new \Ext_Thebing_Gui2_Format_Date())->format_js);
		$oSmarty->assign('aTranslations', Ext_Thebing_Management_Statistic_Gui2::getInterfaceTranslations());
		$oSmarty->assign('sPageTitle', Ext_TS_System_Navigation::t());
		$oSmarty->assign('sStaticReportClass', get_called_class());
		$oSmarty->assign('iPageID', 0);
		$oSmarty->assign('sJs', $oGuiHtml->getJsFooter());

		$oSmarty->display(Ext_Thebing_Management_PageBlock::getTemplatePath() . 'pages.tpl');

		//Admin_Html::loadAdminFooter();
	}

	/**
	 * Liefert ein Fake-Statistiken-Objekt, womit die Pageblock-Klasse arbeiten kann
	 * @return stdClass
	 */
	public function getFakeStatisticObject() {
		$oStatistic = new stdClass();

		$oStatistic->id = get_called_class();
		$oStatistic->from = $this->dFrom;
		$oStatistic->until = $this->dUntil;

		// Schule setzen, damit diese im Filter vorausgewählt ist
		$oStatistic->schools = $this->getSchools(true);

		if(static::isAbsolute()) {
			// Statistik als absolute Statistik definieren
			// Dies ist z.B. wichtig, damit der Zeitraumfilter aktiviert wird
			$oStatistic->type = 2;
		}

		return $oStatistic;
	}

	/**
	 * @param array $aFilter
	 */
	public function setFilter($aFilter) {
		$this->_aFilter = $aFilter;
	}

	/**
	 * Fügt den Where-Part der Filter zum Query hinzu
	 *
	 * @param $sSql
	 * @return array
	 */
	protected function _addWherePart(&$sSql, array &$aSql) {

		$aFilter = $this->_aFilter;

		// Wenn keine Filter vorhanden, Default-Values setzen (sofern diese die Fake-Statistik hergibt)
		if(empty($aFilter)) {
			$oStatistic = $this->getFakeStatisticObject();
			$aFilter = Ext_Thebing_Management_PageBlock::createFilterData($oStatistic);
		}

		// Schulen manuell ergänzen
		// Leer ist diese Variable beim ersten Aufruf der Statistik
		if(empty($aFilter['schools'])) {
			$aFilter['schools'] = $this->getSchools(true);
		}

		$aValues = Ext_Thebing_Management_PageBlock::createWhere($this, $aFilter, $sSql, $aSql);
		$sSql = str_replace('{WHERE}', '', $sSql);

		return $aValues;
	}

	/**
	 * @param bool $bIds
	 * @return array
	 */
	public function getSchools($bIds=false) {
		if(!$bIds) {
			return $this->_aSchools;
		} else {
			$aSchools = array();
			foreach($this->_aSchools as $oSchool) {
				$aSchools[] = $oSchool->id;
			}
			return $aSchools;
		}
	}

	/**
	 * Methode wird aufgerufen beim Klick auf das Export-Icon
	 */
	public function getExport() {
		throw new BadMethodCallException('Must be overridden');
	}

	/**
	 * @param $sTranslation
	 * @return string
	 */
	public static function t($sTranslation) {
		return L10N::t($sTranslation, Ext_Thebing_Management_Statistic::$_sDescription);
	}

	/**
	 * Betrag eines Items ermitteln
	 * Achtet auf Leistungszeitraum und splittet Betrag anhand des Leistungszeitraumes
	 *
	 * Optionen:
	 * 	split: Betrag auf Leistungszeitraum aufteilen
	 * 	tax: Steuer abziehen (0, Bruttobetrag), addieren (1, Nettobetrag) oder nur Steuer berechnen (2)
	 * 	commission_only: Nur Provision berechnen (bei CN-Beträgen plus Steuer wichtig)
	 *
	 * @TODO Brutto/netto? Aktuell nur netto!
	 * 	-> Bei Bruttorechnungen ist das korrekt, solange CNs nicht beachtet werden
	 *
	 * @TODO Hier fehlt item_index_special_amount_gross vat!
	 *
	 * @deprecated
	 * @see \TsStatistic\Service\DocumentItemAmount
	 *
	 * @param array $aItem
	 * @param array $aOptions
	 * @return float|int
	 * @throws UnexpectedValueException
	 */
	protected function getItemAmount(array $aItem, array $aOptions=array()) {
		$fAmount = 0;

		$aNeededKeys = array(
			'item_type',
			'item_from',
			'item_until',
			'item_amount',
			'item_amount_net',
			'item_amount_discount',
			'item_tax_type',
			'item_index_special_amount_net',
			'document_type'
		);

		$bSplit = isset($aOptions['split']) && $aOptions['split'] === true;
		$bCommissionOnly = isset($aOptions['commission_only']) && $aOptions['commission_only'] === true;

		// Wenn gesplittet werden soll, muss bei einem Item bekannt sein, welcher Gebührtyp (einmalig usw.) das ist
		if($bSplit) {
			$aNeededKeys[] = 'item_costs_charge';
		}

		// Wenn nur Provision berechnet werden soll, muss die entsprechende Spalte da sein
		if($bCommissionOnly) {
			$aNeededKeys[] = 'item_amount_commission';
		}

		// Steuer-Option
		if(isset($aOptions['tax'])) {
			$iTaxOption = (int)$aOptions['tax'];
			if($aOptions['tax'] !== 0) {
				// Bei Steuerkalkulation muss der Wert auch vorhanden sein
				$aNeededKeys[] = 'item_index_special_amount_net_vat';
			}
		} else {
			// Standardmäßig Steuer abziehen, wie es überall im System passiert
			$iTaxOption = 0;
		}

		// Validieren, ob Array alle benötigten Werte hat
		if(count(array_intersect_key(array_flip($aNeededKeys), $aItem)) !== count($aNeededKeys)) {
			throw new UnexpectedValueException('Not all needed item keys are set for '.__METHOD__);
		}

		// Specials immer ignorieren, da diese über die entsprechenden Index-Spalten abgezogen werden
		// Außer bei Provisionswert, da es die Index-Spalten dafür nicht gibt!
		if(
			!$bCommissionOnly &&
			$aItem['item_type'] === 'special'
		) {
			return 0;
		}

		$oItemFrom = new DateTime($aItem['item_from']);
		$oItemUntil = new DateTime($aItem['item_until']);

		// Einmalige Gebühren werden auf den ersten Tag der Leistung verbucht
		// Außerdem werden diese Gebühren nicht gesplittet
		$bOneTimeFee = $aItem['item_costs_charge'] == 0 && (
			$aItem['item_type'] === 'additional_general' ||
			$aItem['item_type'] === 'additional_course' ||
			$aItem['item_type'] === 'additional_accommodation'
		);

		if($bOneTimeFee) {
			$oItemUntil = $oItemFrom;
		}

		// Position muss in Filterzeitraum fallen
		if(
			!$bSplit || (
				$oItemFrom <= $this->dUntil &&
				$oItemUntil >= $this->dFrom
			)
		) {

			if(!$bCommissionOnly) {
				// Bruttobetrag oder Nettobetrag
				if(strpos($aItem['document_type'], 'netto') !== false) {
					$fAmount = $aItem['item_amount_net'];
				} else {
					$fAmount = $aItem['item_amount'];
				}
			} else {
				// Nur Provisionsbetrag
				$fAmount = $aItem['item_amount_commission'];
			}

			// Discount steht im Item als Prozentwert
			if(
				//!$bCommissionOnly && // Auch bei Provision, da der Wert ebenso ohne einberechneten Discount gespeichert wird
				$aItem['item_amount_discount'] > 0
			) {
				$fAmount -= $fAmount / 100 * $aItem['item_amount_discount'];
			}

			$fAmountWithDiscount = $fAmount;

			// Option 0: Steuer abziehen
			if($iTaxOption === 0) {
				// Bei »Steuern inklusive« den Steuerbetrag abziehen
				// Grund: Bei dieser Option steht im Amount der Steuer-Bruttobetrag
				if($aItem['item_tax_type'] == 1) {
					$fAmount -= $fAmount - ($fAmount / ($aItem['item_tax'] / 100 + 1));
				}
			// Optionen 1 und 2: Steuer draufrechnen
			} else {
				// Nur bei »Steuer exklusive«, da bei »Steuern inklusive« Amount bereits der Steuer-Bruttobetrag ist
				if($aItem['item_tax_type'] == 2) {
					$fAmount += $fAmount * ($aItem['item_tax'] / 100);
				}
			}

			// Bei nur Steuer berechnen: Originalwert abziehen
			if($iTaxOption === 2) {

				// Bei »Steuer inklusive« muss der Steuerbetrag addiert werden, da danach subtrahiert wird
				if($aItem['item_tax_type'] == 1) {
					$fAmount += $fAmount - ($fAmount / ($aItem['item_tax'] / 100 + 1));
				}

				$fAmount -= $fAmountWithDiscount;
			}

			// Special abziehen
			if(Ext_TC_Util::compareFloat($aItem['item_index_special_amount_net'], 0) != 0) {

				// Wenn nur Steuer berechnet werden soll, darf der Special-Betrag gar nicht erst addiert werden (In der Spalte steht Nettobetrag)
				if($iTaxOption !== 2) {
					$fAmount += $aItem['item_index_special_amount_net'];
				}

				if($iTaxOption !== 0) {
					// Steuer des Specials (Steuer-Nettobetrag hier!) ebenso abziehen (Wert ist minus), wenn Steuern addiert werden sollen
					$fAmount += $aItem['item_index_special_amount_net_vat'];
				}
			}

			// Betrag auf den Leistungszeitraum aufsplitten
			if(
				$bSplit &&
				!$bOneTimeFee
			) {
				$fAmount = Ext_TC_Util::getSplittedAmountByDates($fAmount, $this->dFrom, $this->dUntil, $oItemFrom, $oItemUntil);
			}
		}

		return $fAmount;
	}

	/**
	 * Baut eine Unterkunftsbezeichnung zusammen
	 *
	 * @param array $aData
	 * @return string
	 */
	protected function _buildAccommodationLabel($aData) {
		$aAccommodations = array();
		$aAccCategories = explode('{|}', $aData['accommodation_categories']);
		$aAccRoomtypes = explode('{|}', $aData['accommodation_roomtypes']);
		$aAccMealtypes = explode('{|}', $aData['accommodation_mealtypes']);

		foreach($aAccCategories as $iKey => $sAccCategory) {
			if(!empty($sAccCategory)) {
				$sAccommodation = $sAccCategory.' / '.$aAccRoomtypes[$iKey].' / '.$aAccMealtypes[$iKey];

				if(!in_array($sAccommodation, $aAccommodations)) {
					$aAccommodations[] = $sAccommodation;
				}
			}
		}

		return join(', ', $aAccommodations);
	}
}