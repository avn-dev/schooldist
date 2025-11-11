<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 *
 * 
 */


class GUI_ExtendedForm implements GUI_Element {


	/**
	 * The passend configuration array.
	 *
	 * @var array
	 */
	protected $_aConfig = array();


	/**
	 * The generated html string.
	 *
	 * @var string
	 */
	protected $_sHTML   = null;



	public function __construct(array $aConfig) {
		$aFinalConfig = array(
			'css'               => '',
			'style'             => '',
			'action'            => $_SERVER['PHP_SELF'],
			'method'            => 'post',
			'cellSpacing'       => '',
			'cellPadding'       => '',
			'border'            => '',
			'fields'            => array(),
			'additionalButtons' => array()
		);
		if (array_key_exists('css', $aConfig)) {
			$sValue = $aConfig['css'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "css" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['css'] = (string)$sValue;
			unset($aConfig['css']);
		}
		if (array_key_exists('style', $aConfig)) {
			$sValue = $aConfig['style'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "style" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['style'] = (string)$sValue;
			unset($aConfig['style']);
		}
		if (array_key_exists('action', $aConfig)) {
			$sValue = $aConfig['action'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "action" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['action'] = (string)$sValue;
			unset($aConfig['action']);
		}
		if (array_key_exists('method', $aConfig)) {
			$sValue = $aConfig['method'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "method" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['method'] = (string)$sValue;
			unset($aConfig['method']);
		}
		$sFieldCellSpacing = '';
		if (array_key_exists('cellSpacing', $aConfig)) {
			$sValue = $aConfig['cellSpacing'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "cellSpacing" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['cellSpacing'] = (string)$sValue;
			unset($aConfig['cellSpacing']);
		}
		if (array_key_exists('cellPadding', $aConfig)) {
			$sValue = $aConfig['cellPadding'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "cellPadding" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['cellPadding'] = (string)$sValue;
			unset($aConfig['cellPadding']);
		}
		if (array_key_exists('border', $aConfig)) {
			$sValue = $aConfig['border'];
			if (!is_string($sValue) && !is_numeric($sValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "border" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
			}
			$aFinalConfig['border'] = (string)$sValue;
			unset($aConfig['border']);
		}
		if (array_key_exists('additionalButtons', $aConfig)) {
			$aValue = $aConfig['additionalButtons'];
			if (!is_array($aValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Configuration value "additionalButtons" is of type "'.$sArgumentType.'" and is not an array.');
			}
			foreach ($aValue as $sCurrentButtonIndex => $mixedCurrentButtonValue) {
				if (!($mixedCurrentButtonValue instanceof GUI_FormButton)) {
					$sArgumentType = gettype($mixedCurrentButtonValue);
					if (is_object($mixedCurrentButtonValue)) {
						$sArgumentType = get_class($mixedCurrentButtonValue);
					}
					throw new Exception('Index "'.$sCurrentButtonIndex.'" of configuration value "additionalButtons" is of type "'.$sArgumentType.'" and not of type "GUI_FormButton".');
				}
			}
			$aFinalConfig['additionalButtons'] = $aValue;
			unset($aConfig['additionalButtons']);
		}
		if (!array_key_exists('fields', $aConfig) || !is_array($aConfig['fields']) || count($aConfig['fields']) < 1) {
			throw new Exception('No field definitions found in configuration.');
		}
		foreach ($aConfig['fields'] as $sCurIndex => $aCurValue) {
			if (!array_key_exists('name', $aCurValue)) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('No field name defined for field with index "'.$sCurIndex.'".');
			}
			if (!is_string($aCurValue['name']) && !is_numeric($aCurValue['name'])) {
				$sArgumentType = gettype($sValue);
				if (is_object($sValue)) {
					$sArgumentType = get_class($sValue);
				}
				throw new Exception('Field name is of type "'.$sArgumentType.'" and is not a string and cannot be converted for field with index "'.$sCurIndex.'".');
			}
			$sFieldName = (string)$aCurValue['name'];
			unset($aCurValue['name']);
			if (!array_key_exists('type', $aCurValue)) {
				throw new Exception('No field type defined for field "'.$sFieldName.'".');
			}
			if (
				$aCurValue['type'] != 'hidden' &&
				$aCurValue['type'] != 'text' &&
				$aCurValue['type'] != 'textarea' &&
				$aCurValue['type'] != 'select' &&
				$aCurValue['type'] != 'multiselect' &&
				$aCurValue['type'] != 'static' &&
				$aCurValue['type'] != 'checkbox'
			) {
				throw new Exception('Invalid field type "'.$aCurValue['type'].'" defined for field "'.$sFieldName.'".');
			}
			$sFieldType = (string)$aCurValue['type'];
			unset($aCurValue['type']);
			if (
				(
					$sFieldType == 'select' ||
					$sFieldType == 'multiselect'
				) && (
					!array_key_exists('options', $aCurValue) ||
					!is_array($aCurValue['options']) ||
					count($aCurValue['options']) < 1
				)
			) {
				throw new Exception('No select options defined for field "'.$sFieldName.'".');
			}
			$aFieldSelectOptions = array();
			if ($sFieldType == 'select' || $sFieldType == 'multiselect') {
				foreach ($aCurValue['options'] as $sCurSelectOptionKey => $sCurSelectOptionValue) {
					if (!is_string($sCurSelectOptionKey) && !is_numeric($sCurSelectOptionKey)) {
						$sArgumentType = gettype($sCurSelectOptionKey);
						if (is_object($sCurSelectOptionKey)) {
							$sArgumentType = get_class($sCurSelectOptionKey);
						}
						throw new Exception('Select option key is of type "'.$sArgumentType.'" and is not a string and cannot be converted for field "'.$sFieldName.'".');
					}
					if (!is_string($sCurSelectOptionValue) && !is_numeric($sCurSelectOptionValue)) {
						$sArgumentType = gettype($sCurSelectOptionValue);
						if (is_object($sCurSelectOptionValue)) {
							$sArgumentType = get_class($sCurSelectOptionValue);
						}
						throw new Exception('Select option value is of type "'.$sArgumentType.'" and is not a string and cannot be converted for field "'.$sFieldName.'".');
					}
					$aFieldSelectOptions[] = array('value' => $sCurSelectOptionKey, 'display' => $sCurSelectOptionValue);
				}
				unset($aCurValue['options']);
			}
			$sFieldValue          = '';
			$aFieldSelectedValues = '';
			if (array_key_exists('value', $aCurValue)) {
				if (
					(!is_string($aCurValue['value']) && !is_numeric($aCurValue['value']) && $sFieldType != 'static') ||
					(!is_string($aCurValue['value']) && !is_numeric($aCurValue['value']) && !($aCurValue['value'] instanceof GUI_Element) && $sFieldType == 'static')
				) {
					$sArgumentType = gettype($aCurValue['value']);
					if (is_object($aCurValue['value'])) {
						$sArgumentType = get_class($aCurValue['value']);
					}
					throw new Exception('Field value is of type "'.$sArgumentType.'" and is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				if ($aCurValue['value'] instanceof GUI_Element) {
					$sFieldValue            = (string)$aCurValue['value']->generateHTML();
					$aFieldSelectedValues[] = $sFieldValue;
				} else {
					$sFieldValue            = (string)$aCurValue['value'];
					$aFieldSelectedValues[] = $sFieldValue;
				}
				unset($aCurValue['value']);
			}
			if (($sFieldType == 'multiselect') && (array_key_exists('selected', $aCurValue))) {
				if (is_array($aCurValue['selected'])) {
					foreach ($aCurValue['selected'] as $sCurrentSelectedValue) {
						$sFieldValue            = (string)$sCurrentSelectedValue;
						$aFieldSelectedValues[] = $sFieldValue;
					}
				} else {
					$sFieldValue            = (string)$aCurValue['selected'];
					$aFieldSelectedValues[] = $sFieldValue;
				}
				unset($aCurValue['selected']);
			}
			$sFieldText = '';
			if (array_key_exists('text', $aCurValue)) {
				if (!is_string($aCurValue['text']) && !is_numeric($aCurValue['text']) && !($aCurValue['text'] instanceof GUI_Element)) {
					$sArgumentType = gettype($aCurValue['text']);
					if (is_object($aCurValue['text'])) {
						$sArgumentType = get_class($aCurValue['text']);
					}
					throw new Exception('Field text is of type "'.$sArgumentType.'" and is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				if ($aCurValue['text'] instanceof GUI_Element) {
					$sFieldText = (string)$aCurValue['text']->generateHTML();
				} else {
					$sFieldText = (string)$aCurValue['text'];
				}
				unset($aCurValue['text']);
			}
			$bFieldIsRequired = false;
			if (array_key_exists('required', $aCurValue)) {
				if (!is_bool($aCurValue['required'])) {
					throw new Exception('Field required switch is not a boolean for field "'.$sFieldName.'".');
				}
				$bFieldIsRequired = (bool)$aCurValue['required'];
				unset($aCurValue['required']);
			}
			$sFieldCssTr = '';
			if (array_key_exists('cssTr', $aCurValue)) {
				if (!is_string($aCurValue['cssTr']) && !is_numeric($aCurValue['cssTr'])) {
					throw new Exception('Field configuaration value "cssTr is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				$sFieldCssTr = (string)$aCurValue['cssTr'];
				unset($aCurValue['cssTr']);
			}
			$sFieldCssTdLeft = '';
			if (array_key_exists('cssTdLeft', $aCurValue)) {
				if (!is_string($aCurValue['cssTdLeft']) && !is_numeric($aCurValue['cssTdLeft'])) {
					throw new Exception('Field configuaration value "cssTdLeft is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				$sFieldCssTdLeft = (string)$aCurValue['cssTdLeft'];
				unset($aCurValue['cssTdLeft']);
			}
			$sFieldCssTdRight = '';
			if (array_key_exists('cssTdRight', $aCurValue)) {
				if (!is_string($aCurValue['cssTdRight']) && !is_numeric($aCurValue['cssTdRight'])) {
					throw new Exception('Field configuaration value "cssTdRight is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				$sFieldCssTdRight = (string)$aCurValue['cssTdRight'];
				unset($aCurValue['cssTdRight']);
			}
			$sFieldStyleTr = '';
			if (array_key_exists('styleTr', $aCurValue)) {
				if (!is_string($aCurValue['styleTr']) && !is_numeric($aCurValue['styleTr'])) {
					throw new Exception('Field configuaration value "styleTr is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				$sFieldStyleTr = (string)$aCurValue['styleTr'];
				unset($aCurValue['styleTr']);
			}
			$sFieldStyleTdLeft = '';
			if (array_key_exists('styleTdLeft', $aCurValue)) {
				if (!is_string($aCurValue['styleTdLeft']) && !is_numeric($aCurValue['styleTdLeft'])) {
					throw new Exception('Field configuaration value "styleTdLeft is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				$sFieldStyleTdLeft = (string)$aCurValue['styleTdLeft'];
				unset($aCurValue['styleTdLeft']);
			}
			$sFieldStyleTdRight = '';
			if (array_key_exists('styleTdRight', $aCurValue)) {
				if (!is_string($aCurValue['styleTdRight']) && !is_numeric($aCurValue['styleTdRight'])) {
					throw new Exception('Field configuaration value "styleTdRight is not a string and cannot be converted for field "'.$sFieldName.'".');
				}
				$sFieldStyleTdRight = (string)$aCurValue['styleTdRight'];
				unset($aCurValue['styleTdRight']);
			}
			$aFieldRegExpConstraints = array();
			if (array_key_exists('regExpConstraints', $aCurValue)) {
				if (!is_array($aCurValue['regExpConstraints'])) {
					throw new Exception('Field configuaration value "regExpConstraints" is not an array for field "'.$sFieldName.'".');
				}
				foreach ($aCurValue['regExpConstraints'] as $sCurRegExpContraintIndex => $aCurRegExpContraintValues) {
					if (!is_array($aCurRegExpContraintValues)) {
						throw new Exception('Constraint data value for constraint "'.$sCurRegExpContraintIndex.'" is not an array for field "'.$sFieldName.'".');
					}
					foreach ($aCurRegExpContraintValues as $sCurRegExpContraintValueName => $sCurRegExpContraintValueValue) {
						switch ($sCurRegExpContraintValueName) {
							case 'constraint':
								$aFieldRegExpConstraints[] = array('constraint' => (string)$sCurRegExpContraintValueValue);
								break;
							default:
								throw new Exception('Unprocessed constraint configuration value "'.$sCurRegExpContraintValueName.'" for constraint "'.$sCurRegExpContraintIndex.'" in configuration for field "'.$sFieldName.'".');
						}
					}
				}
				unset($aCurValue['regExpConstraints']);
			}
			if (is_array($aCurValue) && count($aCurValue) > 0) {
				reset($aCurValue);
				throw new Exception('Unprocessed configuration value "'.key($aCurValue).'" for field "'.$sCurIndex.'".');
			}
			$aFinalConfig['fields'][] = array(
				'name'              => $sFieldName,
				'type'              => $sFieldType,
				'options'           => $aFieldSelectOptions,
				'selected'          => $aFieldSelectedValues,
				'value'             => $sFieldValue,
				'text'              => $sFieldText,
				'required'          => $bFieldIsRequired,
				'validInput'        => true,
				'cssTr'             => $sFieldCssTr,
				'cssTdLeft'         => $sFieldCssTdLeft,
				'cssTdRight'        => $sFieldCssTdRight,
				'styleTr'           => $sFieldStyleTr,
				'styleTdLeft'       => $sFieldStyleTdLeft,
				'styleTdRight'      => $sFieldStyleTdRight,
				'regExpConstraints' => $aFieldRegExpConstraints
			);
		}
		unset($aConfig['fields']);
		if (is_array($aConfig) && count($aConfig) > 0) {
			reset($aConfig);
			throw new Exception('Unprocessed configuration value "'.key($aConfig).'".');
		}
		$this->_aConfig = $aFinalConfig;
	}

	public function processInput(array $aInputArray) {
		$bValidInput = true;
		foreach ($this->_aConfig['fields'] as $sCurrentFieldIndex => $aCurrentFieldData) {
			if (!array_key_exists($aCurrentFieldData['name'], $aInputArray) || $aCurrentFieldData['type'] == 'static') {
				continue;
			}
			if ($aCurrentFieldData['required'] == true) {
				if (strlen($aInputArray[$aCurrentFieldData['name']]) < 1) {
					$this->_aConfig['fields'][$sCurrentFieldIndex]['validInput'] = false;
					$bValidInput = false;
				}
			}
			if ($aCurrentFieldData['type'] == 'select') {
				$bOptionFound = false;
				foreach ($aCurrentFieldData['options'] as $aCurrentOption) {
					if ($aCurrentOption['value'] == $aInputArray[$aCurrentFieldData['name']]) {
						$bOptionFound = true;
						break;
					}
				}
				if (!$bOptionFound) {
					$this->_aConfig['fields'][$sCurrentFieldIndex]['validInput'] = false;
					$aInputArray[$aCurrentFieldData['name']] = $aCurrentFieldData['value'];
					$bValidInput = false;
				}
				$this->_aConfig['fields'][$sCurrentFieldIndex]['selected'] = array($aInputArray[$aCurrentFieldData['name']]);
				$aInputArray[$aCurrentFieldData['name']]                   = '';
			} elseif ($aCurrentFieldData['type'] == 'multiselect') {
				$this->_aConfig['fields'][$sCurrentFieldIndex]['selected'] = array();
				if (!is_array($aInputArray[$aCurrentFieldData['name']])) {
					$this->_aConfig['fields'][$sCurrentFieldIndex]['validInput'] = false;
					$this->_aConfig['fields'][$sCurrentFieldIndex]['value']      = '';
					$this->_aConfig['fields'][$sCurrentFieldIndex]['selected']   = array();
					$bValidInput = false;
				} else {
					$aCurrentSelectedOptions                 = $aInputArray[$aCurrentFieldData['name']];
					$aInputArray[$aCurrentFieldData['name']] = '';
					foreach ($aCurrentSelectedOptions as $sCurrentSelectedOption) {
						$bOptionFound = false;
						foreach ($aCurrentFieldData['options'] as $aCurrentOption) {
							if ($aCurrentOption['value'] == $sCurrentSelectedOption) {
								$bOptionFound = true;
								break;
							}
						}
						if (!$bOptionFound) {
							$this->_aConfig['fields'][$sCurrentFieldIndex]['validInput'] = false;
							$bValidInput = false;
						} else {
							$this->_aConfig['fields'][$sCurrentFieldIndex]['selected'][] = $sCurrentSelectedOption;
						}
					}
				}
			}
			foreach ($aCurrentFieldData['regExpConstraints'] as $aCurrentConstraint) {
				$iRegExpResult = preg_match($aCurrentConstraint['constraint'], $aInputArray[$aCurrentFieldData['name']]);
				if ($iRegExpResult < 1) {
					$this->_aConfig['fields'][$sCurrentFieldIndex]['validInput'] = false;
					$bValidInput = false;
				}
			}
			$this->_aConfig['fields'][$sCurrentFieldIndex]['value'] = $aInputArray[$aCurrentFieldData['name']];
		}
		$this->_sHTML = null;
		return $bValidInput;
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$objForm  = new GUI_Form(array('action' => $this->_aConfig['action'], 'method' => $this->_aConfig['method']));
			$aTableConfig = array(
				'showth' => false,
				'cols'   => array(
					'Text',
					'Fields'
				),
				'style' => 'width: 100%; margin-bottom: 15px;'
			);
			if (strlen($this->_aConfig['cellSpacing']) > 0) {
				$aTableConfig['cellSpacing'] = $this->_aConfig['cellSpacing'];
			}
			if (strlen($this->_aConfig['cellPadding']) > 0) {
				$aTableConfig['cellPadding'] = $this->_aConfig['cellPadding'];
			}
			if (strlen($this->_aConfig['border']) > 0) {
				$aTableConfig['border'] = $this->_aConfig['border'];
			}
			if (strlen($this->_aConfig['css']) > 0) {
				$aTableConfig['css'] = $this->_aConfig['css'];
			}
			if (strlen($this->_aConfig['style']) > 0) {
				$aTableConfig['style'] = $this->_aConfig['style'];
			}
			$objTable = new GUI_Table($aTableConfig);
			foreach ($this->_aConfig['fields'] as $sCurrentFieldIndex => $aField) {
				switch ($aField['type']) {
					case 'hidden':
						$objHiddenField = new GUI_FormHidden(array('name' => $aField['name'], 'value' => $aField['value']));
						$objForm->appendElement($objHiddenField);
						break;
					case 'text':
						$objTextField = new GUI_FormText(array('name' => $aField['name'], 'value' => $aField['value']));
						if ($aField['validInput'] == true) {
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $aField['text']
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objTextField
									)
								)
							));
						} else {
							$objSpan = new GUI_Span(array('style' => 'color: #FF0000;'));
							$objSpan->appendElement($aField['text']);
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $objSpan
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objTextField
									)
								)
							));
						}
						break;
					case 'textarea':
						$objTextarea = new GUI_FormTextarea(array('name' => $aField['name'], 'value' => $aField['value']));
						if ($aField['validInput'] == true) {
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $aField['text']
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objTextarea
									)
								)
							));
						} else {
							$objSpan = new GUI_Span(array('style' => 'color: #FF0000;'));
							$objSpan->appendElement($aField['text']);
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $objSpan
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objTextarea
									)
								)
							));
						}
						break;
					case 'select':
						$objSelectField = new GUI_FormSelect(array('name' => $aField['name'], 'options' => $aField['options'], 'selected' => $aField['selected']));
						if ($aField['validInput'] == true) {
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $aField['text']
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objSelectField
									)
								)
							));
						} else {
							$objSpan = new GUI_Span(array('style' => 'color: #FF0000;'));
							$objSpan->appendElement($aField['text']);
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $objSpan
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objSelectField
									)
								)
							));
						}
						break;
					case 'multiselect':
						$objSelectField = new GUI_FormSelect(array('name' => $aField['name'], 'options' => $aField['options'], 'selected' => $aField['selected'], 'multi' => true));
						if ($aField['validInput'] == true) {
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $aField['text']
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objSelectField
									)
								)
							));
						} else {
							$objSpan = new GUI_Span(array('style' => 'color: #FF0000;'));
							$objSpan->appendElement($aField['text']);
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $objSpan
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objSelectField
									)
								)
							));
						}
						break;
					case 'static':
						$objTable->appendRow(array(
							'css'   => $aField['cssTr'],
							'style' => $aField['styleTr'],
							'cols'  => array(
								array(
									'forceTh' => true,
									'css'     => $aField['cssTdLeft'],
									'style'   => $aField['styleTdLeft'],
									'text'    => $aField['text']
								),
								array(
									'css'   => $aField['cssTdRight'],
									'style' => $aField['styleTdRight'],
									'text'  => $aField['value']
								)
							)
						));
						break;
					case 'checkbox':
						$objCheckbox = new GUI_ElementList();
						$objCheckbox->appendElement(new GUI_FormHidden(array('name' => $aField['name'], 'value' => '0')));
						$objCheckbox->appendElement(new GUI_FormCheckbox(array('name' => $aField['name'], 'value' => '1', 'checked' => ($aField['value'] == '1'))));
						if ($aField['validInput'] == true) {
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $aField['text']
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objCheckbox
									)
								)
							));
						} else {
							$objSpan = new GUI_Span(array('style' => 'color: #FF0000;'));
							$objSpan->appendElement($aField['text']);
							$objTable->appendRow(array(
								'css'   => $aField['cssTr'],
								'style' => $aField['styleTr'],
								'cols'  => array(
									array(
										'forceTh' => true,
										'css'     => $aField['cssTdLeft'],
										'style'   => $aField['styleTdLeft'],
										'text'    => $objSpan
									),
									array(
										'css'   => $aField['cssTdRight'],
										'style' => $aField['styleTdRight'],
										'text'  => $objCheckbox
									)
								)
							));
						}
						break;
					default:
						throw new Exception('Invalid field type "'.$aField['type'].'".');
				}
			}
			$objSubmitButton = new GUI_FormSubmit(array('value' => 'Absenden'));
			$objResetButton  = new GUI_FormReset(array('value' => 'Zurücksetzen'));
			$objElementList  = new GUI_ElementList();
			foreach ($this->_aConfig['additionalButtons'] as $objCurrentButton) {
				$objElementList->appendElement($objCurrentButton);
				$objElementList->appendElement(new GUI_EscapedString('&nbsp;'));
			}
			$objElementList->appendElement($objResetButton);
			$objElementList->appendElement(new GUI_EscapedString('&nbsp;'));
			$objElementList->appendElement($objSubmitButton);
			$objButtonDiv = new GUI_Div(array('style' => 'text-align: right;'));
			$objButtonDiv->appendElement($objElementList);
			$objForm->appendElement($objTable);
			$objForm->appendElement($objButtonDiv);
			$this->_sHTML = $objForm->generateHTML();
		}
		return $this->_sHTML;
	}

	public function setFieldProperties($sFieldName, array $aProperties = array()) {
		$sFieldName  = (string)$sFieldName;
		$sFieldIndex = $this->_getFieldIndex($sFieldName);
		foreach ($aProperties as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'validInput':
					$this->_aConfig['fields'][$sFieldIndex]['validInput'] = (bool)$mixedCurrentValue;
					break;
				case 'value':
					$this->_aConfig['fields'][$sFieldIndex]['value'] = (string)$mixedCurrentValue;
					break;
				default:
					throw new Exception('Unprocessed configuation value "'.$sCurrentIndex.'".');
			}
		}
	}

	public function getFieldProperties($sFieldName) {
		$sFieldName  = (string)$sFieldName;
		$sFieldIndex = $this->_getFieldIndex($sFieldName);
		$aFieldData  = $this->_aConfig['fields'][$sFieldIndex];
		return array(
			'validInput' => $aFieldData['validInput'],
			'value'      => $aFieldData['value']
		);
	}

	protected function _getFieldIndex($sFieldName) {
		$sFieldName = (string)$sFieldName;
		foreach ($this->_aConfig['fields'] as $sFieldIndex => $aFieldData) {
			if ($aFieldData['name'] == $sFieldName) {
				return $sFieldIndex;
			}
		}
		throw new Exception('The specified field "'.$sFieldName.'" does not exist.');
	}

}
