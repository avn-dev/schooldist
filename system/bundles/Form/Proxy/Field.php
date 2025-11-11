<?php

namespace Form\Proxy;

class Field extends \Core\Proxy\WDBasicAbstract {
	
	protected $sEntityClass = '\Form\Entity\Option';
	protected $mValue;
	protected $sConditionCheck;
	protected $sConditionAction;

	protected $oFormService;
	
	protected $sError;

	public function getOptions() {
		
		if(
			$this->oEntity->type === 'select' ||
			$this->oEntity->type === 'radio' ||
			$this->oEntity->type === 'checkbox'
		) {
			
			if(strpos($this->oEntity->value, '|') !== false) {
				$aOptions = explode("|", $this->oEntity->value);
			} else {
				$aOptions = explode(",", $this->oEntity->value);
			}

			$aOptions = array_map('trim', $aOptions);
			
			return $aOptions;

		} elseif($this->oEntity->type === 'reference') {

			$aOptions = [''];

			$sSql = "SELECT * FROM `".$this->oEntity->getAdditional('db_table')."` ".$this->oEntity->getAdditional('db_query')."";
			$aValues = \DB::getQueryRows($sSql);
			foreach($aValues as $aValue) {
				$aReferenceFields = explode(',', $this->oEntity->getAdditional('db_field'));
				$aReferenceValue = array();
				foreach ($aReferenceFields as $sReferenceField) {
					$aReferenceValue[] = $aValue[trim($sReferenceField)];
				}
				$aOptions[] = implode(' ', $aReferenceValue);
			}
			
			return $aOptions;
		} elseif($this->oEntity->type === 'method_call') {
			
			try {
				
				$aMethodData = json_decode($this->oEntity->getAdditional('method_call'), 1);

				$sClass = $aMethodData[0];
				$sMethod = $aMethodData[1];
				unset($aMethodData[0]);
				unset($aMethodData[1]);
				$aParams = (array)$aMethodData;

				$aOptions = \Factory::executeStatic($sClass, $sMethod, $aParams);
				$aOptions = \Util::addEmptyItem($aOptions);

			} catch (\Exception $exc) {
				$aOptions = ['An error occured! ('.$exc->getMessage().')'];
			}
			
			return $aOptions;
		}

	}
	
	public function getName() {
		
		$sInputName = 'option_'.$this->oEntity->id;
		
		if(in_array($this->oEntity->type, ['select', 'reference', 'checkbox']) === true) {
			$sInputName .= '[]';
		}
		
		return $sInputName;
	}
	
	public function setValue($mValue) {
		$this->mValue = $mValue;
	}
	
	public function getValue($bReturnString=false) {
		
		$mReturn = $this->mValue;
		
		// Defaultwert
		if(
			empty($this->mValue) &&
			$this->getProperty('type') !== 'select' &&
			$this->getProperty('type') !== 'checkbox' &&
			$this->getProperty('type') !== 'radio'
		) {
			$mReturn = $this->getProperty('value');
		}

		if($this->oEntity->type === 'checkbox') {
			if(!is_array($mReturn)) {
				$mReturn = (array)$mReturn;
			}
		}

		if(
			$bReturnString === true &&
			is_array($mReturn)
		) {
			$mReturn = implode(', ', $mReturn);
		}

		return $mReturn;
	}
	
	public function getConditionCheck() {

		$aDisplayConditions = $this->oEntity->display_conditions;
		
		if (!empty($aDisplayConditions)) {

			$bShow = true;

			if (!empty($aDisplayConditions)) {
				foreach($aDisplayConditions as $aDisplayCondition) {
					if (
						(string)$this->mValue != (string)$aDisplayCondition['value']
					) {
						$bShow = false;
					}
				}
			}

			if ($bShow === false) {
				$sConditionCheck = "id=\"field_".$this->oEntity->form_id."_".$this->oEntity->id."\" style=\"display:none;\"";
			} else {
				$sConditionCheck = "id=\"field_".$this->oEntity->form_id."_".$this->oEntity->id."\"";
			}

		} else {
			$sConditionCheck = "id=\"field_".$this->oEntity->form_id."_".$this->oEntity->id."\"";
		}
		
		return $sConditionCheck;
	}
	
	public function getConditionAction() {
		
		$sAction = \Form\Service\Frontend\Conditions::getFormFieldConditionAction($this->oEntity->type, $this->oEntity->updateaction);
		
		return $sAction;
	}
	
	public function setError($sError) {
		$this->sError = $sError;
	}

	public function unsetError() {
		unset($this->sError);
	}
	
	public function hasError() {
		return !empty($this->sError);
	}
	
	public function getError() {
		return $this->sError;
	}
	
}