<?php

namespace TsAccounting\Helper\Company;

use L10N;

class ReceiptTextBasedOn
{
	/**
	 * Basierend auf Daten Cachen
	 *
	 * @var array
	 */
	protected static $_aBasedonData = null;

	/**
	 * Attribute Liste Cachen
	 *
	 * @var array
	 */
	protected static $_aAttributes = array();

	public static function getBasedOnData()
	{
		if (self::$_aBasedonData === null) {
			$aBasedOnData = array();

			// Alle Dokumenttypen laden
			$aDocTypes = self::getDocTypes();

			$aPositionTypes = self::getDocPositions();

			$aOtherTypes = array(
				'vat' => 'vat',
				'claim_debt' => 'claim_debt',
				'deposit' => 'deposit',
				'deposit_credit' => 'deposit_credit'
			);

			// Rechnungstyp-basierend
			$aBasedOnData[1] = array_combine($aDocTypes, $aDocTypes);

			// Position-basierend
			$aBasedOnData[2] = array_combine($aPositionTypes, $aPositionTypes);

			foreach ($aDocTypes as $sDocType) {
				$aTemp = $aPositionTypes;

				if ($sDocType == 'manual_creditnote') {
					// Bei Typ 3: Es gibt nur den Typ Provision (neben Steuer und Forderung)
					$aTemp = array('commission');
				} elseif ($sDocType == 'storno') {
					// Belegtext für allgemeine Stornogebühren (Gebühr auf alles)
					$aTemp[] = 'storno';
				} elseif (strpos($sDocType, 'brutto') !== false) {
					// Bruttorechnungen haben keine Provision
					unset($aTemp[array_search('commission', $aTemp)]);
				}

				foreach ($aTemp as $sPositionType) {
					$sKey = $sDocType . '_' . $sPositionType;

					// Rechnungs-typ basierend & Positionbasierend
					$aBasedOnData[3][$sDocType][$sPositionType] = $sKey;
				}
			}

			// Rechnungstyp-basierend
			foreach ($aBasedOnData[1] as $sKey => &$mData) {

				$mData = array('position' => $sKey . '_position');
				foreach ($aOtherTypes as $sOtherType) {
					$sDataKey = $sKey . '_' . $sOtherType;
					$mData[$sOtherType] = $sDataKey;
				}

			}

			// Position-basierend
			$aBasedOnData[2] = array_merge($aBasedOnData[2], $aOtherTypes);

			// Belegtext für allgemeine Stornogebühren (Gebühr auf alles)
			// Muss hier manuell hinzugefügt werden, da das oben nur für Typ 3 passiert
			$aBasedOnData[2]['storno'] = 'storno';

			// Pro Leistungs- und Dokumententyp
			foreach ($aBasedOnData[3] as $sKey => &$mData) {
				foreach ($aOtherTypes as $sOtherType) {
					$sDataKey = $sKey . '_' . $sOtherType;
					$mData[$sOtherType] = $sDataKey;
				}
			}

			self::$_aBasedonData = $aBasedOnData;
		}

		return self::$_aBasedonData;
	}

	/**
	 * Nach "Basierend auf" Typ entehende Attribute in der WDBasic
	 *
	 * @param int $iType
	 */
	public static function getAttributes($iType = false)
	{

		if (!$iType) {

			// Wenn kein Typ definiert, alle zurück geben
			$aAttributesAll = array();

			$aTypes = self::getTypes();

			// Alle möglichen Typen durchgehen & Mergen
			foreach ($aTypes as $iType) {
				$aAttributesByType = self::getAttributes($iType);

				$aAttributesAll = array_merge($aAttributesAll, $aAttributesByType);
			}

			return $aAttributesAll;
		}

		// Im Cache niks gefunden, neu generieren
		if (!isset(self::$_aAttributes[$iType])) {
			$aBasedOnDataAll = self::getBasedOnData();

			if (isset($aBasedOnDataAll[$iType])) {
				$aAttributes = array();

				$aBasedOnData = self::$_aBasedonData[$iType];

				foreach ($aBasedOnData as $mData) {
					// Bei Typ 3 (Dok&Positionsgebunden) hat das Array 1 Ebene mehr
					if (is_array($mData)) {
						foreach ($mData as $mValue) {
							$aAttributes[$mValue] = $mValue;
						}
					} else {
						$aAttributes[$mData] = $mData;
					}
				}

				self::$_aAttributes[$iType] = $aAttributes;
			} else {
				throw new \Exception('type "' . $iType . '" not found!');
			}
		}

		self::$_aAttributes[$iType][] = 'payment';

		return self::$_aAttributes[$iType];
	}

	/**
	 * Dokumenttypen die für Belegtexte relevant sind
	 *
	 * @return array
	 */
	public static function getDocTypes()
	{
		$aDocTypes = (array)\Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnotes_and_without_proforma');

		$aDocTypes = array_combine($aDocTypes, $aDocTypes);

		// Diese Typen gibt es nicht mehr
		unset(
			$aDocTypes['group_proforma'],
			$aDocTypes['group_proforma_netto'],
			$aDocTypes['credit_brutto'],
			$aDocTypes['credit_netto']
		);

		// Stornierte Creditnote
		$aDocTypes[] = 'creditnote_cancellation';

		$aDocTypes = array_values($aDocTypes);

		return $aDocTypes;
	}

	/**
	 * Alle Rechnungspositions-typen
	 *
	 * @return array
	 */
	public static function getDocPositions()
	{
		$aDocPositions = (array)\Ext_Thebing_School_Positions::getAllPositions();

		$aDocPositions['extra'] = 1;
		$aDocPositions['commission'] = 1;
		$aDocPositions['invoice'] = 1;

		$aDocPositions = array_keys($aDocPositions);

		return $aDocPositions;
	}

	/**
	 * Verfügbare Basierend auf Optionen mit Labels
	 *
	 * @param type $sTranslationPath
	 * @return type
	 */
	public static function getTypesForSelect($sTranslationPath)
	{
		return array(
			'1' => L10N::t('Pro Dokumententyp', $sTranslationPath),
			'2' => L10N::t('Pro Leistungstyp', $sTranslationPath),
			'3' => L10N::t('Pro Leistungs- und Dokumententyp', $sTranslationPath),
		);
	}

	/**
	 * Verfügbare Basierend auf Optionen
	 *
	 * @return array
	 */
	public static function getTypes()
	{
		return array(
			'1',
			'2',
			'3',
		);
	}
}