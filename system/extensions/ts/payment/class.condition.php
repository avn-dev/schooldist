<?php

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $user_id
 * @property string $name
 * @property string $comment
 * @property int $position
 * @property float $surcharge_amount
 * @property string $surcharge_type (ENUM)
 * @property string $surcharge_calculation (ENUM)
 * @property string $surcharge_on (ENUM)
 */
class Ext_TS_Payment_Condition extends Ext_Thebing_Basic {

    protected $_sTable = 'ts_payment_conditions';

    protected $_sTableAlias = 'ts_pc';

    protected $_aJoinTables = array(
        'agencies_payments_groups_to_payment_conditions' => array(
            'table'	=>	'ts_agencies_payments_groups_to_payment_conditions',
            'foreign_key_field'	=>	array('group_id', 'school_id'),
            'primary_key_field'	=>	'payment_condition_id',
			'autoload' => false
        ),
    );

	protected $_aJoinedObjects = [
		'settings' => [
			'class' => 'Ext_TS_Payment_Condition_Setting',
			'key' => 'payment_condition_id',
			'check_active' => true,
			'type' => 'child',
			'orderby' => 'position'
		],
	];

	const CACHE_KEY_FOR_PARTIAL_INVOICE_CONDITIONS = 'CACHE_KEY_FOR_PARTIAL_INVOICE_CONDITIONS';

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {

			$aSettingTypes = array_column($this->getSettings(), 'type');
			$aTypeCounts = array_count_values($aSettingTypes);
			if(
				in_array('installment', $aSettingTypes) &&
				in_array('final', $aSettingTypes)
			) {
				// Ratenzahlung darf nicht mit Restzahlung kombiniert werden
				$mValidate = ['PAYMENT_CONDITION_INSTALLMENT_WITH_FINAL'];
			} elseif(
				!in_array('installment', $aSettingTypes) &&
				!in_array('final', $aSettingTypes)
			) {
				// Restzahlung muss ansonsten immer vorhanden sein
				$mValidate = ['PAYMENT_CONDITION_NO_FINAL'];
			} elseif($aTypeCounts['installment'] > 1) {
				// Nur eine Ratenzahlung
				$mValidate = ['PAYMENT_CONDITION_INSTALLMENT_COUNT'];
			} elseif($aTypeCounts['final'] > 1) {
				// Nur eine Restzahlung
				$mValidate = ['PAYMENT_CONDITION_FINAL_COUNT'];
			}

		}

		return $mValidate;

	}

	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {

		$this->setSettingsPosition();

		parent::save($bLog);
		
		WDCache::delete(self::CACHE_KEY_FOR_PARTIAL_INVOICE_CONDITIONS);
		
		return $this;
	}

	/**
	 * @return Ext_TS_Payment_Condition_Setting[]
	 */
	public function getSettings() {
		return $this->getJoinedObjectChilds('settings', true);
	}

	/**
	 * Reihenfolge der Settings (Childs) nach Typ setzen
	 */
	private function setSettingsPosition() {

		// Beim Agentur-Import kann es sein, dass es noch keine Settings gibt
		if(empty($this->_aJoinedObjectChilds['settings'])) {
			return;
		}
		
		$aOrder = ['deposit', 'installment', 'final'];
		uasort($this->_aJoinedObjectChilds['settings'], function($oSetting) use($aOrder) {
			return array_search($oSetting->type, $aOrder);
		});

		$iPosition = 1;
		foreach($this->_aJoinedObjectChilds['settings'] as $oSetting) {
			$oSetting->position = $iPosition++;
		}

	}

	/**
	 * Prüfen, ob diese Zahlungsbedingung für Teilrechnungen verwendet werden kann
	 *
	 * @return bool
	 */
	public function isEligibleForPartialInvoice() {

		$aSettings = $this->getSettings();

		if(
			(
				count($aSettings) === 2 &&
				reset($aSettings)->type === 'deposit' &&
				(
					end($aSettings)->type === 'final' ||
					end($aSettings)->type === 'installment'
				)
			) ||
			(
				count($aSettings) === 1 &&
				reset($aSettings)->type === 'installment'
			)
		) {
			return true;
		}

		return false;

	}

	/**
	 * @return array
	 */
	public static function getSelectOptions() {

		$aOptions = [];
		foreach(self::getRepository()->findAll() as $oCondition) {
			$aOptions[$oCondition->id] = $oCondition->name;
		}

		return $aOptions;
	}

	public static function getSelectOptionsForPartialInvoice($bAddEmpty=false) {
		
		$aOptions = WDCache::get(self::CACHE_KEY_FOR_PARTIAL_INVOICE_CONDITIONS);
		
		if($aOptions === null) {
			
			$aOptions = [];
			foreach(self::getRepository()->findAll() as $oCondition) {
				if($oCondition->isEligibleForPartialInvoice()) {
					$aOptions[$oCondition->id] = $oCondition->name;
				}
			}
			
			WDCache::set(self::CACHE_KEY_FOR_PARTIAL_INVOICE_CONDITIONS, 86400, $aOptions);

		}

		if($bAddEmpty === true) {
			$aOptions = \Ext_TC_Util::addEmptyItem($aOptions);
		}
		
		return $aOptions;
	}

}
