<?php

namespace Poll\Helper;

use Poll\Entity\Question\Condition;

class ConditionHelper {

	protected $_aQuestions = array();
	protected $_aJs = array();

	public function handleQuestionCondition($aQuestion) {

		$oQuestionConditionRepo = \Poll\Entity\Question\Condition::getRepository();
		
		$oCondition = $oQuestionConditionRepo->findOneBy(array('question_id'=>(int)$aQuestion['id']));
		
		if(
			$oCondition !== null &&
			$oCondition->settings != ''
		) {

			$aConditions = json_decode($oCondition->settings, true);

			foreach($aConditions['iFilter'] as $sField) {

				$aFieldInfo = explode('_', $sField);

				if($aFieldInfo[0] == 'question') {

					$this->_aQuestions[$aFieldInfo[1]] = $aFieldInfo[1];

				}

			}
			
			$this->_aJs[$aQuestion['id']] = $this->buildJsQuery($oCondition);

		}

	}

	public function buildJsQuery(Condition $oCondition) {

		$aFilter = json_decode($oCondition->settings, true);

		foreach($aFilter['iFilter'] as $iKey=>$sField) {

			$iQuestionId = 0;
			$sCustomerField = '';
			$mValue = null;

			if($aFilter['iOpen'][$iKey] == 1) {
				$sCmd .= ' ( ';
			}

			$sCompare = $aFilter['sFilter'][$iKey];

			if(strpos($sField, 'question_') === 0) {

				$iQuestionId = str_replace('question_', '', $sField);

				if(is_numeric($sCompare)) {
					$sValueOutput = $sCompare;
				} else {
					$sValueOutput = "'".\Util::getEscapedString($sCompare, 'javascript')."'";
				}				

				$sCheckAll = 'false';
				if (isset($aFilter['iCheckAll'][$iKey]) && (int)$aFilter['iCheckAll'][$iKey] === 1) {
					$sCheckAll = 'true';
				}

				if (strpos($iQuestionId, '_') !== false) {
					$sCmd .= " poll_checkCondition('".$iQuestionId."', ".(int)$aFilter['iMode'][$iKey].", ".$sValueOutput.", ".$sCheckAll.") ";
				} else {
					$sCmd .= " poll_checkCondition(".(int)$iQuestionId.", ".(int)$aFilter['iMode'][$iKey].", ".$sValueOutput.", ".$sCheckAll.") ";
				}

				$sCmd .= " poll_checkCondition(".$iQuestionId.", ".(int)$aFilter['iMode'][$iKey].", ".$sValueOutput.", ".$sCheckAll.") ";

			} else {

				$sCustomerField = str_replace('customer_', '', $sField);

				$mValue = $this->getCustomerValue($sCustomerField);
				
				$bValue = false;
				switch($aFilter['iMode'][$iKey]) {
					case 1: //	=> "beinhaltet",
						if(strpos($mValue, $sCompare) !== false) {
							$bValue = true;
						}
						break;
					case 2: //	=> "gleich",
						if($mValue == $sCompare) {
							$bValue = true;
						}
						break;
					case 3: //	=> "ungleich",
						if($mValue != $sCompare) {
							$bValue = true;
						}
						break;
					case 6: //	=> "größer",
						if($mValue > $sCompare) {
							$bValue = true;
						}
						break;
					case 7: //	=> "kleiner",
						if($mValue < $sCompare) {
							$bValue = true;
						}
						break;
					case 8: //	=> "nicht leer",
						if(!empty($mValue)) {
							$bValue = true;
						}
						break;
				}

				if($bValue === true) {
					$sCmd .= ' true ';
				} else {
					$sCmd .= ' false ';
				}

			}

			if($aFilter['iClose'][$iKey] == 1) {
				$sCmd .= ' ) ';
			}

			if(isset($aFilter['iOperator'][$iKey])) {
				if($aFilter['iOperator'][$iKey] == 'AND') {
					$sCmd .= ' && ';
				} else {
					$sCmd .= ' || ';
				}
			}

		}

		return $sCmd;
		
	}
	
	public function getPageQuestions() {
		return array_values($this->_aQuestions);
	}
	
	public function getQuestionJs($aReport) {
		
		$sJs = '<script type="text/javascript">';
		$sJs .= '
				function initPoll() {
				if($) {';
		$sJs .= 'var aConditions = new Array();';
		$sJs .= 'var aQuestionValues = new Array();';

		$sJs .= "function poll_checkCondition(iQuestionId, iCompare, sCompare, bCheckAll) {

			var aValues = [];
			if($('.result_'+iQuestionId+'[type=radio]').size()) {
				var mTempValue = $('.result_'+iQuestionId+'[type=radio]:checked').val();
				if(mTempValue) {
					aValues.push(mTempValue);
				}
			} else if($('.result_'+iQuestionId+'[type=checkbox]').size()) {
				var aChecked = $('.result_'+iQuestionId+'[type=checkbox]:checked');
				$.each(aChecked, function(iChecked, oChecked) {
					var mTempValue = $(oChecked).val();
					if(mTempValue) {
						aValues.push(mTempValue);
					}
				});
			} else if($('.result_'+iQuestionId+'').size()) {
				var mTempValue = $('.result_'+iQuestionId+'').val();
				if(mTempValue) {
					aValues.push(mTempValue);
				}
			} else if(aQuestionValues[iQuestionId]) {
				aValues.push(aQuestionValues[iQuestionId]);
			}

			if(aValues.length == 0 && iCompare === 3) {
				aValues.push('');
			}

			var aValueMatches = [];
			$.each(aValues, function(iValue, mValue) {
				
				var bValue = false;
				switch(iCompare) {
					case 1:
						if(mValue.indexOf(sCompare) >= 0) {
							bValue = true;
						}
						break;
					case 2:
						if(mValue == sCompare) {
							bValue = true;
						}
						break;
					case 3:
						if(mValue != sCompare) {
							bValue = true;
						}
						break;
					case 6:
						if(mValue > sCompare) {
							bValue = true;
						}
						break;
					case 7:
						if(mValue < sCompare) {
							bValue = true;
						}
						break;
					case 8:
						if(!empty(mValue)) {
							bValue = true;
						}
						break;
				}

				aValueMatches.push(bValue);
			});

			if (aValueMatches.length > 0) {
				if (bCheckAll && aValueMatches.indexOf(false) === -1) {
					// All selected options must match to conditions
					return true;
				} else if (!bCheckAll && aValueMatches.indexOf(true) !== -1) {
					// Only one selected option must match to conditions
					return true;
				}
			}

			return false;
		}
		";
		
		$aConditionQuestions = $this->getPageQuestions();

		foreach($aConditionQuestions as $iConditionQuestionId) {

			$mValue = $aReport['f_'.$iConditionQuestionId];
			
			$sJs .= "aQuestionValues[".(int)$iConditionQuestionId."] = '".\Util::getEscapedString($mValue, 'javascript')."';\n";

		}

		$sJs .= '
			function poll_checkQuestions() { 
		';
		
		foreach($this->_aJs as $iConditionQuestionId=>$sQuery) {

			$sJs .= '
				if( $(\'#question_container_'.$iConditionQuestionId.'\')) { 
					if('.$sQuery.') {
						$(\'#question_container_'.$iConditionQuestionId.'\').show();
						$(\'#question_container_'.$iConditionQuestionId.'\').removeClass(\'question-hidden\');
					} else {
						$(\'#question_container_'.$iConditionQuestionId.'\').hide();
						$(\'#question_container_'.$iConditionQuestionId.'\').addClass(\'question-hidden\');
					}
				}
			';
			$sJs .= "\n";

		}

		$sJs .= "
			}
		$(function() {
			poll_checkQuestions();
			
			$('input,textarea,select').change(function() {poll_checkQuestions() });

		});
		\n";
		
		$sJs .= '}
			}
		</script>';

		return $sJs;

	}
	
	public function checkConditions($aConditions, $aRequestResult, $aReport) {

		$sCmd = 'return ';
		foreach((array)$aConditions['iFilter'] as $iKey=>$sField) {

			$iQuestionId = 0;
			$sCustomerField = '';
			$mValue = null;

			if(strpos($sField, 'question_') === 0) {

				$iQuestionId = str_replace('question_', '', $sField);

				// Anfrage
				if(isset($aRequestResult[$iQuestionId])) {
					$mValue = $aRequestResult[$iQuestionId];
				// Alte Werte
				} elseif(isset($aReport['f_'.$iQuestionId])) {
					$mValue = $aReport['f_'.$iQuestionId];
				}

			} else {

				$sCustomerField = str_replace('customer_', '', $sField);

				$mValue = $this->getCustomerValue($sCustomerField);
				
			}

			if($aConditions['iOpen'][$iKey] == 1) {
				$sCmd .= ' ( ';
			}

			$sCompare = $aConditions['sFilter'][$iKey];

			if(!is_array($mValue)) {
				$aValues = [$mValue];
			} else {
				$aValues = $mValue;
			}

			$bValue = false;
			foreach($aValues as $mValue) {
				
				switch($aConditions['iMode'][$iKey]) {
					case 1: //	=> "beinhaltet",
						if(
							strpos($mValue, $sCompare) !== false
						) {
							$bValue = true;
						}
						break;
					case 2: //	=> "gleich",
						if($mValue == $sCompare) {
							$bValue = true;
						}
						break;
					case 3: //	=> "ungleich",
						if($mValue != $sCompare) {
							$bValue = true;
						}
						break;
					case 6: //	=> "größer",
						if($mValue > $sCompare) {
							$bValue = true;
						}
						break;
					case 7: //	=> "kleiner",
						if($mValue < $sCompare) {
							$bValue = true;
						}
						break;
					case 8: //	=> "nicht leer",
						if(!empty($mValue)) {
							$bValue = true;
						}
						break;
				}
				
				if($bValue === true) {
					break;
				}

			}

			if($bValue === true) {
				$sCmd .= ' true ';
			} else {
				$sCmd .= ' false ';
			}

			if($aConditions['iClose'][$iKey] == 1) {
				$sCmd .= ' ) ';
			}

			if(isset($aConditions['iOperator'][$iKey])) {
				$sCmd .= ' '.$aConditions['iOperator'][$iKey].' ';
			}

		}

		$sCmd .= ';';

		$bRouting = eval($sCmd);
		
		return $bRouting;
	}

	public function getCustomerValue($sCustomerField) {
		
		if(is_numeric($sCustomerField)) {
			$sCustomerField = 'ext_'.$sCustomerField;
		}

		global $oAccessFrontend;

		if(
			$oAccessFrontend instanceof \Access_Frontend &&
			$oAccessFrontend->checkValidAccess() === true
		) {
			$oAccess = $oAccessFrontend;
		} else {
			$oAccess = \Access::getInstance();
		}

		if(
			$oAccess instanceof \Access_Frontend &&
			$oAccess->checkValidAccess() === true
		) {
			$aUserData = $oAccess->getUserData();
			$mValue = $aUserData['data'][$sCustomerField];
		}

		return $mValue;
	}
	
}