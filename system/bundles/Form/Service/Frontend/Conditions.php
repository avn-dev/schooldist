<?php

namespace Form\Service\Frontend;

class Conditions {

	/**
	 * @var \Form\Service\Frontend
	 */
	private $oFormService;
	
	public function __construct(\Form\Service\Frontend $oFormService) {
		$this->oFormService = $oFormService;
	}

	function getFormConditionJavaScript() {

		$aConditions = [];
		
		$iFormId = $this->oFormService->getForm()->id;

		$aFields = $this->oFormService->getFields();
		$aFieldProxies = $this->oFormService->getAllFieldProxies();

		if(empty($aFields)) {
			return '';
		}

		foreach($aFields as $oField) {

			$aDisplayConditions = $oField->display_conditions;

			if (!empty($aDisplayConditions)) {

				foreach($aDisplayConditions as $aDisplayCondition) {
					if(empty($aDisplayCondition['value'])) {
						$aDisplayCondition['value'] = true;
					}
					$aConditions[$oField->id][$aDisplayCondition['field']][] = $aDisplayCondition;
				}

			}

		}

		$sOutput = '<script type="text/javascript">

			function form_checkValue(iFieldId, mValue) {
				if(aValues[iFieldId]) {
					if(Array.isArray(aValues[iFieldId])) {
						var length = aValues[iFieldId].length;
						for(var i = 0; i < length; i++) {
							if(aValues[iFieldId][i] == mValue) {
								return true;
							}
						}
					} else {
						if(aValues[iFieldId] == mValue) {
							return true;
						}
					}
				}
				return false;
			}
		
			function form_toggleRequired(oElement) {

				if(
					!oElement ||
					!oElement.classList.contains("required")
				) {
					return;
				}

				if( oElement.offsetWidth || oElement.offsetHeight || oElement.getClientRects().length ) {
					oElement.required = true;
				} else {
					oElement.required = false;
				}

			}
			
			var oForm = document.getElementById("form_'.$this->oFormService->getContentId().'");
			
			if(oForm) {
			
				var aRequiredFields = oForm.getElementsByClassName("required");

				if(
					aRequiredFields &&
					aRequiredFields.length > 0
				) {
					for(i=0;i<aRequiredFields.length;i++) {
						form_toggleRequired(aRequiredFields[i]);
					}
				}

				

			}

			var aValues = [];
';

		foreach($aFieldProxies as $iPageId=>$aPageFieldProxies) {
			if($iPageId === $this->oFormService->getPage()->id) {
				break;
			}
			foreach($aPageFieldProxies as $oFieldProxy) {
				$sOutput .= '			aValues['.$oFieldProxy->getProperty('id').'] = '.json_encode($oFieldProxy->getValue()).';'."\n";
			}
		}

		$sOutput .= '
			function form_checkConditions(oForm) {';

		if(!empty($aConditions)) {

			foreach($aConditions as $iField=>$aOptions) {

				$oConditionFieldProxy = $this->oFormService->getFieldProxy($iField);
				
				$sActionActive = " document.getElementById('field_".$iFormId."_".$iField."').style.display=''; ";
				$sActionInactive = " document.getElementById('field_".$iFormId."_".$iField."').style.display='none'; ";

				$aConditionParts = array();

				foreach($aOptions as $iOptionFieldId=>$aOptionValues) {

					$oFieldProxy = $this->oFormService->getFieldProxy($iOptionFieldId);
					
					// Feld nicht mehr vorhanden
					if($oFieldProxy === null) {
						continue;
					}

					$aFieldOptions = $oFieldProxy->getOptions();
					
					$aSubConditionParts = array();
					foreach($aOptionValues as $aOption) {
						
						$sOption = $aOption['value'];
				
						$iPos = null;
						if(!empty($aFieldOptions)) {
							foreach($aFieldOptions as $iOptionPos=>$sValue) {
								if(trim($sValue) == trim($sOption)) {
									$iPos = $iOptionPos;				
									break;
								}
							}
						}

						$aSubConditionParts[] = "(form_checkValue(".$oFieldProxy->getProperty('id').", '".\Util::getEscapedString(trim($sOption), 'javascript')."'))";

						$sFieldName = 'oForm[\''.$oFieldProxy->getName().'\']';
						$sFieldNameMultiple = 'oForm[\''.$oFieldProxy->getName().'\']';

						$sOperator = null;
						switch($aOption['mode']) {
							case 2:
								$sOperator = '==';
								break;
							case 3:
								$sOperator = '!=';
								break;
						}

						if (
							$oFieldProxy->getProperty('type') == 'text' ||
							$oFieldProxy->getProperty('type') == 'radio'
						) {

							$aSubConditionParts[] = '('.$sFieldName.' && '.$sFieldName.".value ".$sOperator." '".\Util::getEscapedString(trim($sOption), 'javascript')."')";

						} elseif (
							$oFieldProxy->getProperty('type') != 'select' &&
							$oFieldProxy->getProperty('type') != 'reference'
						) {

							if ($sOption === true) {
								$aSubConditionParts[] = '('.$sFieldName.' && '.$sFieldName.'.checked)';
							} else {
								$aSubConditionParts[] = '('.$sFieldNameMultiple.' && '.$sFieldNameMultiple.'.checked)';
							}

						} else {

							$aSubConditionParts[] = '('.$sFieldNameMultiple.' && '.$sFieldNameMultiple.'.selectedIndex '.$sOperator.' '.(int)$iPos.')';

						}

					}

					$aConditionParts[] = implode(' || ', $aSubConditionParts);

				}

				if(!empty($aConditionParts)) {
					$sOutput .= "if ((".implode(') && (', $aConditionParts).")) {".$sActionActive." form_toggleRequired(oForm['".$oConditionFieldProxy->getName()."']); } \n";
					$sOutput .= " else {".$sActionInactive." form_toggleRequired(oForm['".$oConditionFieldProxy->getName()."']); } \n\n";
				}

			}

		}

		$sOutput .= '
			}

			form_checkConditions(oForm);

		</script>';

		return $sOutput;
	}

	static public function getFormFieldConditionAction($sFieldType='click', $sAdditionalUpdateJs = '') {

		$sAction = 'form_checkConditions(this.form); '.$sAdditionalUpdateJs;

		if ($sFieldType == 'text') {
			$sAction = "onkeyup=\"".$sAction."\" onchange=\"".$sAction."\"";
		} elseif ($sFieldType != 'select') {
			$sAction = "onclick=\"".$sAction."\" onchange=\"".$sAction."\"";
		} else {
			$sAction = "onchange=\"".$sAction."\"";
		}

		return $sAction;

	}

	function getFormConditionAction($intFieldId, $aConditions, $intFormId, $sFieldType='click') {

		$strAction = "";

		foreach($aConditions as $iField=>$aOptions) {

			$strActionActive = " document.getElementById('field_".$intFormId."_".$iField."').style.display=''; ";
			$strActionInactive = " document.getElementById('field_".$intFormId."_".$iField."').style.display='none'; ";

			$aConditionParts = array();

			if($sFieldType == 'text') {

				foreach($aOptions as $sOption) {
					$aConditionParts[] = "this.value == '".\Util::getEscapedString($sOption, 'javascript')."'";
				}

			} elseif($sFieldType != 'select') {

				$sFieldName = 'this.form.option_'.$intFieldId;

				if(empty($aOptions)) {
					$aConditionParts[] = $sFieldName.".checked";
				} else {
					foreach($aOptions as $iOption) {
						$aConditionParts[] = $sFieldName."[".(int)$iOption."].checked";
					}
				}

			} else {

				foreach($aOptions as $iOption) {
					$aConditionParts[] = "this.selectedIndex == ".(int)$iOption."";
				}

			}

			$strAction .= "if(".implode(' || ', $aConditionParts).") {".$strActionActive."} ";
			$strAction .= " else {".$strActionInactive."} ";

		}

		if($sFieldType == 'text') {
			$strAction = "onkeyup=\"".$strAction."\" onchange=\"".$strAction."\"";
		} elseif($sFieldType != 'select') {
			$strAction = "onclick=\"".$strAction."\" onchange=\"".$strAction."\"";
		} else {
			$strAction = "onchange=\"".$strAction."\"";
		}

		return $strAction;

	}

	function getFormConditionValues(&$mDisplayValues) {

		$mCheck = json_decode($mDisplayValues, true);

		if(
			$mCheck === null &&
			is_string($mDisplayValues)
		) {
			$mDisplayValues = array($mDisplayValues);
		} else {
			$mDisplayValues = (array)$mCheck;
		}

	}
	
}
