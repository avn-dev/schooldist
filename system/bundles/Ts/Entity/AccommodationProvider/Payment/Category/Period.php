<?php

namespace Ts\Entity\AccommodationProvider\Payment\Category;

use Ts\Handler\AccommodationProvider\PaymentHandler;

/**
 * @param $inboxes
 * @param $conditions
 * @param $period_type
 * @param $weeks
 * @param $display
 * @param $duration_dependency
 */
class Period extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_accommodation_providers_payment_categories_periods';
	protected $_sTableAlias = 'ts_appcp';
	protected $_sEditorIdColumn = 'editor_id';
	
	protected $_aJoinTables = array(
		'inboxes'=>array(
	 		'table'=>'ts_accommodation_providers_payment_categories_periods_inboxes',
	 		'foreign_key_field'=>'inbox_id',
	 		'primary_key_field'=>'period_id',
	 		'autoload'=>true,
	 		'cloneable' => true,
	 		'on_delete' => 'delete',
		),
		'conditions'=>array(
	 		'table'=>'ts_accommodation_providers_payment_categories_periods_conditions',
	 		'primary_key_field'=>'period_id',
	 		'cloneable' => true,
	 		'on_delete' => 'delete',
		)
	);

	/**
	 * 
	 * @param boolean $bThrowExceptions
	 * @return boolean|array
	 */
	public function validate($bThrowExceptions = false) {
		
		// Wenn keine Abhängigkeit besteht, Einstellungen entfernen
		if(!$this->duration_dependency) {
			$this->conditions = array();
		}
		
		$mErrors = parent::validate($bThrowExceptions);
		
		if(!empty($this->_aJoinData['conditions'])) {
			foreach($this->_aJoinData['conditions'] as $iCondition=>$aCondition) {
				
				$oValidator = new \WDValidate();
				$oValidator->value = $aCondition['weeks'];
				$oValidator->check = 'INT_POSITIVE';
				$bCheck = $oValidator->execute();
				
				if(!$bCheck) {
					if(!is_array($mErrors)) {
						$mErrors = array();
					}
					$mErrors['conditions.weeks-'.$iCondition.''][] = 'INVALID_INT_POSITIVE';
				}
				
			}
		}

		if(
			$this->before_direction === 'pre' ||
			strpos($this->before_direction, 'minus') !== false
		) {
			$iQuantity = PaymentHandler::IGNORE_ALLOCATIONS_OLDER_THAN_MONTHS;
			if($this->before_unit == 8) {
				// Tage
				$iQuantity = $iQuantity * 4 * 30;
			} elseif($this->before_unit != 10) {
				// Alles andere außer Monate sind Wochen
				$iQuantity *= 4;
			}

			if($this->before_quantity > $iQuantity) {
				if(!is_array($mErrors)) {
					$mErrors = array();
				}
				$mErrors['before_quantity'][] = 'TO_HIGH';
			}
		}

		return $mErrors;
	}
	
}