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
 * TODO: Rewrite processing of column definitions
 * 
 */


class GUI_Table implements GUI_Element {

	protected $_aConfig = array();
	protected $_aRows   = array();
	protected $_sHTML   = null;

	public function __construct(array $aConfig) {
		$this->_aConfig = $this->_parseRowArray($aConfig, true);
	}

	public function appendRow($aRow) {
		if (!is_array($aRow)) {
			throw new Exception('Argument 1 ($aRow) is not an array.');
		}
		$aFinalRow = $this->_parseRowArray($aRow, false);
		foreach ($aFinalRow['cols'] as $sColIndex => $aColData) {
			if (!array_key_exists($sColIndex, $this->_aConfig['cols'])) {
				throw new Exception('Invalid column "'.$sColIndex.'" specified.');
			}
		}
		$this->_aRows[] = $aFinalRow;
		$this->_sHTML   = null;
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('aConfig', $this->_aConfig);
			$objSmarty->assign('aRows', $this->_aRows);
			$this->_sHTML = $objSmarty->parseTemplate('gui.table.tpl');
		}
		return $this->_sHTML;
	}

	protected function _parseRowArray(array $aRow, $bIsTableConfig) {
		if (!is_bool($bIsTableConfig)) {
			throw new Exception('Argument 2 ($bIsTableConfig) is not a boolean.');
		}
		// initiate table configuration
		if ($bIsTableConfig == true) {
			$aFinalRow = array(
				'cellSpacing' => '0',
				'cellPadding' => '4',
				'border'      => '0',
				'css'         => 'table',
				'style'       => 'width: 100%;',
				'thcss'       => '',
				'thstyle'     => '',
				'thname'      => '',
				'thid'        => '',
				'showth'      => true,
				'cols'        => array(),
				'highlight'   => false,
				'width'		  => ''
			);
		} else {
			$aFinalRow = array(
				'css'               => '',
				'style'             => '',
				'name'              => '',
				'id'                => '',
				'cols'              => array(),
				'highlight'         => $this->_aConfig['highlight'],
				'doubleClickAction' => ''
			);
		}
		// process table specific configuration
		if ($bIsTableConfig == true) {
			foreach ($aRow as $sCurrentIndex => $mixedCurrentValue) {
				switch ($sCurrentIndex) {
					case 'cellSpacing':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['cellSpacing'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'cellPadding':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['cellPadding'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'border':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['border'] = (string)$mixedCurrentValue;
						unset($aRow[$sCurrentIndex]);
						break;
					case 'thcss':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['thcss'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'thstyle':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['thstyle'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'thname':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['thname'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'thid':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['thid'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'showth':
						if (!is_bool($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a boolean.');
						}
						$aFinalRow['showth'] = (bool)$mixedCurrentValue;
						unset($aRow[$sCurrentIndex]);
						break;
				}
			}
		}
		// process row specific configuration
		else {
			foreach ($aRow as $sCurrentIndex => $mixedCurrentValue) {
				switch ($sCurrentIndex) {
					case 'name':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['name'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'id':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['id'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
					case 'doubleClickAction':
						if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
							$sArgumentType = gettype($mixedCurrentValue);
							if (is_object($mixedCurrentValue)) {
								$sArgumentType = get_class($mixedCurrentValue);
							}
							throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
						}
						$aFinalRow['doubleClickAction'] = (string)trim($mixedCurrentValue);
						unset($aRow[$sCurrentIndex]);
						break;
				}
			}
		}
		// process general configuration
		$aColumns = array();
		foreach ($aRow as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'css':
					if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
						$sArgumentType = gettype($mixedCurrentValue);
						if (is_object($mixedCurrentValue)) {
							$sArgumentType = get_class($mixedCurrentValue);
						}
						throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
					}
					$aFinalRow['css'] = (string)trim($mixedCurrentValue);
					unset($aRow[$sCurrentIndex]);
					break;
				case 'style':
					if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
						$sArgumentType = gettype($mixedCurrentValue);
						if (is_object($mixedCurrentValue)) {
							$sArgumentType = get_class($mixedCurrentValue);
						}
						throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
					}
					$aFinalRow['style'] = (string)trim($mixedCurrentValue);
					unset($aRow[$sCurrentIndex]);
					break;
				case 'appendCss':
					if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
						$sArgumentType = gettype($mixedCurrentValue);
						if (is_object($mixedCurrentValue)) {
							$sArgumentType = get_class($mixedCurrentValue);
						}
						throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
					}
					$aFinalRow['css'] = (string)trim($aFinalRow['css'].' '.trim($mixedCurrentValue));
					unset($aRow[$sCurrentIndex]);
					break;
				case 'appendStyle':
					if (!is_string($mixedCurrentValue) && !is_numeric($mixedCurrentValue)) {
						$sArgumentType = gettype($mixedCurrentValue);
						if (is_object($mixedCurrentValue)) {
							$sArgumentType = get_class($mixedCurrentValue);
						}
						throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a string and cannot be converted.');
					}
					$aFinalRow['style'] = (string)trim($aFinalRow['style'].' '.trim($mixedCurrentValue));
					unset($aRow[$sCurrentIndex]);
					break;
				case 'cols':
					if (is_array($mixedCurrentValue)) {
						$aColumns = $mixedCurrentValue;
					}
					unset($aRow[$sCurrentIndex]);
					break;
				case 'highlight':
					if (!is_bool($mixedCurrentValue)) {
						$sArgumentType = gettype($mixedCurrentValue);
						if (is_object($mixedCurrentValue)) {
							$sArgumentType = get_class($mixedCurrentValue);
						}
						throw new Exception('Configuration value "'.$sCurrentIndex.'" is of type "'.$sArgumentType.'" and is not a boolean.');
					}
					$aFinalRow['highlight'] = (bool)$mixedCurrentValue;
					unset($aRow[$sCurrentIndex]);
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}
		if (count($aColumns) < 1) {
			throw new Exception('No column definitions found in configuration.');
		}
		unset($aRow);
		// process all defined columns, at this point the following variables exist:
		// - $aFinalRow      = the processed table/row configuration
		// - $bIsTableConfig = defines whether the current data is a table or a row configuration
		// - $aColumns       = list of all defined colums (not yet processed)
		$iCurrentColSpan = 0;
		foreach ($aColumns as $sCurIndex => $aCurValue) {
			$sColIndex      = (string)$sCurIndex;
			$sColText       = '';
			$bEscapeColText = null;
			$sColCss        = '';
			$sColStyle      = '';
			$sColThCss      = '';
			$sColThStyle    = '';
			$sColName       = '';
			$sColId         = '';
			$iColSpan       = 1;
			$bColForceTh    = false;
			if ($bIsTableConfig != true && array_key_exists($sColIndex, $this->_aConfig['cols'])) {
				$sColStyle = (string)$this->_aConfig['cols'][$sColIndex]['style'];
			}
			if (!is_array($aCurValue)) {
				$sColText = $aCurValue;
			} else {
				if (!array_key_exists('text', $aCurValue)) {
					throw new Exception('No column text defined for column "'.$sColIndex.'".');
				}
				$sColText = $aCurValue['text'];
				unset($aCurValue['text']);
				if (array_key_exists('css', $aCurValue)) {
					$sColCss = $aCurValue['css'];
					if (!is_string($sColCss) && !is_numeric($sColCss)) {
						throw new Exception('Configuration value "css" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColCss = (string)$sColCss;
					unset($aCurValue['css']);
				}
				if (array_key_exists('style', $aCurValue)) {
					$sColStyle = $aCurValue['style'];
					if (!is_string($sColStyle) && !is_numeric($sColStyle)) {
						throw new Exception('Configuration value "style" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					if ($bIsTableConfig != true && array_key_exists($sColIndex, $this->_aConfig['cols'])) {
						$sColStyle = (string)$this->_aConfig['cols'][$sColIndex]['style'].' '.(string)$sColStyle;
					} else {
						$sColStyle = (string)$sColStyle;
					}
					unset($aCurValue['style']);
				}
				if ($bIsTableConfig == true && array_key_exists('thcss', $aCurValue)) {
					$sColThCss = $aCurValue['thcss'];
					if (!is_string($sColThCss) && !is_numeric($sColThCss)) {
						throw new Exception('Configuration value "thcss" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColThCss = (string)$sColThCss;
					unset($aCurValue['thcss']);
				}
				if ($bIsTableConfig == true && array_key_exists('thstyle', $aCurValue)) {
					$sColThStyle = $aCurValue['thstyle'];
					if (!is_string($sColThStyle) && !is_numeric($sColThStyle)) {
						throw new Exception('Configuration value "thstyle" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColThStyle = (string)$sColThStyle;
					unset($aCurValue['thstyle']);
				}
				if ($bIsTableConfig == true && array_key_exists('thname', $aCurValue)) {
					$sColName = $aCurValue['thname'];
					if (!is_string($sColName) && !is_numeric($sColName)) {
						throw new Exception('Configuration value "thname" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColName = (string)$sColName;
					unset($aCurValue['thname']);
				}
				if ($bIsTableConfig == true && array_key_exists('thid', $aCurValue)) {
					$sColId = $aCurValue['thid'];
					if (!is_string($sColId) && !is_numeric($sColId)) {
						throw new Exception('Configuration value "thid" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColId = (string)$sColId;
					unset($aCurValue['thid']);
				}
				if ($bIsTableConfig != true && array_key_exists('name', $aCurValue)) {
					$sColName = $aCurValue['name'];
					if (!is_string($sColName) && !is_numeric($sColName)) {
						throw new Exception('Configuration value "name" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColName = (string)$sColName;
					unset($aCurValue['name']);
				}
				if ($bIsTableConfig != true && array_key_exists('id', $aCurValue)) {
					$sColId = $aCurValue['id'];
					if (!is_string($sColId) && !is_numeric($sColId)) {
						throw new Exception('Configuration value "id" is not a string and cannot be converted for column "'.$sColIndex.'".');
					}
					$sColId = (string)$sColId;
					unset($aCurValue['id']);
				}
				if ($bIsTableConfig != true && array_key_exists('colspan', $aCurValue)) {
					$iColSpan = $aCurValue['colspan'];
					if (!is_int($iColSpan)) {
						throw new Exception('Configuration value "colspan" is not an integer for column "'.$sColIndex.'".');
					}
					if ($iColSpan < 0) {
						throw new Exception('Configuration value "colspan" must be greater or equal than 0 for column "'.$sColIndex.'".');
					}
					$iColSpan = (int)$iColSpan;
					unset($aCurValue['colspan']);
				}
				if ($bIsTableConfig != true && array_key_exists('forceTh', $aCurValue)) {
					$bColForceTh = $aCurValue['forceTh'];
					if (!is_bool($bColForceTh)) {
						throw new Exception('Configuration value "forceTh" is not a boolean for column "'.$sColIndex.'".');
					}
					$bColForceTh = (bool)$bColForceTh;
					unset($aCurValue['forceTh']);
				}
			}
			if ($sColText instanceof GUI_Element) {
				$sColText       = (string)$sColText->generateHTML();
				$bEscapeColText = false;
			} elseif (!is_string($sColText) && !is_numeric($sColText)) {
				throw new Exception('Column text is not a string and cannot be converted for column "'.$sColIndex.'".');
			} else {
				$sColText       = (string)$sColText;
				$bEscapeColText = true;
			}
			if (is_array($aCurValue) && count($aCurValue) > 0) {
				reset($aCurValue);
				throw new Exception('Unprocessed configuration value "'.key($aCurValue).'" for column "'.$sColIndex.'".');
			}
			if (array_key_exists($sColIndex, $aFinalRow['cols'])) {
				throw new Exception('Column "'.$sColIndex.'" already exists.');
			}
			$aFinalRow['cols'][$sColIndex] = $this->_createColArray(
				$bIsTableConfig,
				$sColIndex,
				$sColText,
				$bEscapeColText,
				$sColCss,
				$sColStyle,
				$sColThCss,
				$sColThStyle,
				$sColName,
				$sColId,
				$iColSpan,
				$bColForceTh
			);
			$iCurrentColSpan += $iColSpan;
		}
		if ($bIsTableConfig != true && $iCurrentColSpan != count($this->_aConfig['cols'])) {
			throw new Exception('Invalid colspan count ('.$iCurrentColSpan.' != '.count($this->_aConfig['cols']).').');
		}
		return $aFinalRow;
	}

	protected function _createColArray(
		$bIsTableConfig,
		$sColIndex,
		$sColText,
		$bEscapeColText,
		$sColCss,
		$sColStyle,
		$sColThCss,
		$sColThStyle,
		$sColName,
		$sColId,
		$iColSpan,
		$bColForceTh
	) {
		if (!is_bool($bIsTableConfig)) {
			throw new Exception('Argument 1 ($bIsTableConfig) is not a boolean.');
		}
		if (!is_string($sColIndex)) {
			throw new Exception('Argument 2 ($sColIndex) is not a string.');
		}
		if (!is_string($sColText)) {
			throw new Exception('Argument 3 ($sColText) is not a string.');
		}
		if (!is_bool($bEscapeColText)) {
			throw new Exception('Argument 4 ($bEscapeColText) is not a boolean.');
		}
		if (!is_string($sColCss)) {
			throw new Exception('Argument 5 ($sColCss) is not a string.');
		}
		if (!is_string($sColStyle)) {
			throw new Exception('Argument 6 ($sColStyle) is not a string.');
		}
		if (!is_string($sColThCss)) {
			throw new Exception('Argument 7 ($sColThCss) is not a string.');
		}
		if (!is_string($sColThStyle)) {
			throw new Exception('Argument 8 ($sColThStyle) is not a string.');
		}
		if (!is_string($sColName)) {
			throw new Exception('Argument 9 ($sColName) is not a string.');
		}
		if (!is_string($sColId)) {
			throw new Exception('Argument 10 ($sColId) is not a string.');
		}
		if (!is_int($iColSpan)) {
			throw new Exception('Argument 11 ($iColSpan) is not an integer.');
		}
		if (!is_bool($bColForceTh)) {
			throw new Exception('Argument 12 ($bColForceTh) is not a boolean.');
		}
		if ($bIsTableConfig == true) {
			return array(
				'text'       => (string)$sColText,
				'escapeText' => (bool)$bEscapeColText,
				'css'        => (string)$sColCss,
				'style'      => (string)$sColStyle,
				'thcss'      => (string)$sColThCss,
				'thstyle'    => (string)$sColThStyle,
				'thname'     => (string)$sColName,
				'thid'       => (string)$sColId
			);
		} else {
			return array(
				'text'       => (string)$sColText,
				'escapeText' => (bool)$bEscapeColText,
				'css'        => (string)$sColCss,
				'style'      => (string)$sColStyle,
				'name'       => (string)$sColName,
				'id'         => (string)$sColId,
				'colspan'    => (int)$iColSpan,
				'forceTh'    => (bool)$bColForceTh
			);
		}
	}

}
