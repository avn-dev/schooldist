<?php

class Ext_TS_Vat_Combination extends Ext_TC_Basic {

	const KEY_OTHER = 'OTHER';

	const OTHERS_TRANSFER = -1;
	const OTHERS_EXRAPOSITION = -2;
	const OTHERS_ACTIVITY = -3;
//	const OTHERS_SPECIAL = -4;
//	const OTHERS_CANCELLATION = -5;

	protected $_sTable = 'ts_vat_rates_combinations';

	protected $_aFormat = array(
		'valid_until' => array(
			'format' => 'DATE'
		)
	);
	
	protected $_aJoinTables = array(
		'objects' => array(
			'table' => 'ts_vat_rates_combinations_to_objects',
	 		'foreign_key_field'=> array('class_id', 'class'),
	 		'primary_key_field'=> 'combination_id',
			'autoload' => false
		)
	);
	
	public function __set($sName, $mValue) {

		if(strpos($sName, 'combination-') === 0) {
			
			$sClass = str_replace(['combination-container-', 'combination-'], '', $sName);

			$aObjects = $this->objects;

			if(empty($mValue)) {
				$this->objects = array_filter($aObjects, function($aObject) use($sClass) {
					if($aObject['class'] !== $sClass) {
						return true;
					}
					return false;
				});
				return;
			}
			
			foreach($mValue as $iValue) {
				
				$sObjectClass = $sClass;
				
				// In dem Bereich "Sonstiges" werden zwei Einträge manuell zu den Versicherungen
				// geschrieben. Dafür müssen hier die Werte manipuliert werden, da sonst der Klassenname
				// der Versicherung drinsteht
				if($iValue < 0) {
					$sObjectClass = Ext_TS_Vat_Combination::KEY_OTHER;
//					if($iValue == -1) {
//						$sObjectClass = 'TRANSFER';
//					} else if($iValue == -2) {
//						$sObjectClass = 'NOT_ALLOCATED_EXTRAPOSITIONS';
//					}
				}
				
				$aObjects[] = [
					'class' => $sObjectClass,
					'class_id' => $iValue
				];
			}
			
			$this->objects = $aObjects;
			
		} else {
			parent::__set($sName, $mValue);
		}
		
	}
	
	public function __get($sName) {

		if(strpos($sName, 'combination-') === 0) {
			
			$sClass = str_replace(['combination-container-', 'combination-'], '', $sName);

			$aClasses = [$sClass];
			
			if($sClass === 'Ext_Thebing_Insurances') {
//				$aClasses[] = 'TRANSFER';
//				$aClasses[] = 'NOT_ALLOCATED_EXTRAPOSITIONS';
				$aClasses[] = Ext_TS_Vat_Combination::KEY_OTHER;
			}
			
			$aObjects = array_filter($this->objects, function($aObject) use($aClasses) {
				if(in_array($aObject['class'], $aClasses)) {
					return true;
				}
				return false;
			});


			return array_column($aObjects, 'class_id');
			
		} else {
			return parent::__get($sName);
		}
			
	}

}
