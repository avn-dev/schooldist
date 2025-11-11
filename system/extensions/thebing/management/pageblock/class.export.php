<?php

use PhpOffice\PhpSpreadsheet;

class Ext_Thebing_Management_PageBlock_Export {

	// The cache array
	protected static $_aCache = array(
		'format'	=> array(),
		'codes'		=> array()
	);

	// The result object
	protected $_oResult;

	// The statistik object
	protected $_oStatistic;

	public static $_aDebug = array();

	protected $_aColors;
	
	/* ==================================================================================================== */

	/**
	 * The constructor
	 * 
	 * @param Ext_Thebing_Management_Statistic $oStatistic
	 * @param Ext_Thebing_Management_PageBlock_Result $oResult
	 */
	public function __construct(Ext_Thebing_Management_Statistic $oStatistic, Ext_Thebing_Management_PageBlock_Result $oResult)
	{
		$this->_oStatistic = $oStatistic;

		$this->_oResult = $oResult;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_prepare();
	}

	/* ==================================================================================================== */

	public function setColors($aColors) {
		$this->_aColors = $aColors;
	}
	
	/**
	 * Export the statistic as excel 2007
	 */
	protected function _export()
	{
		$oExcel = new PhpSpreadsheet\Spreadsheet();

		$oExcel->setActiveSheetIndex(0);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Definitions

		$iCol = 0;
		$iRow = 1;

		// Define style for titles and sum lines like font and background
		$aStyle = array(
			'font' => array(
				'bold' => true
			),
			'fill' => array(
				'fillType' => PhpSpreadsheet\Style\Fill::FILL_SOLID,
				'color' => array(
					'rgb' => ''
				)
			)
		);

		if($this->_aColors === null) {
			$aColors = Ext_Thebing_Management_Statistic_Gui2::getColumnColorsByID();
		} else {
			$aColors = $this->_aColors;
		}

		$aLabels = (array)$this->_oResult->getLabels();
		$aData = (array)$this->_oResult->getData();

		// Summenzeile für ksort aus Array nehmen (das hat mit PHP < 7 noch mit uksort korrekt funktioniert)
		$aSumRow = null;
		if(isset($aLabels['-'])) {
			$aSumRow = $aLabels['-'];
			unset($aLabels['-']);
		}

		// 1 muss immer oben stehen (wird ggf. erst später ins Array gesetzt) #5069
		ksort($aLabels);

		if($aSumRow !== null) {
			$aLabels['-'] = $aSumRow;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write excel

		if($this->_oStatistic->list_type == 1) // Summe
		{
			if(!empty($this->_oStatistic->columns['groups']))
			{
				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // First line by groups

				$oExcel->getActiveSheet()->mergeCells('A1:A4');
				$aStyle['fill']['color']['rgb'] = 'EEEEEE';
				$oExcel->getActiveSheet()->getStyle('A1:A4')->applyFromArray($aStyle);
				$oExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

				foreach($aLabels as $iPeriod => $aPeriods)
				{
					if($iPeriod == 1)
					{
						if(is_array($aPeriods['data']))
						{
							foreach($aPeriods['data'] as $iGroupKey => $aGroups)
							{
								$sC = self::_getColumnCode(++$iCol);

								$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aGroups['title']);

								$aStyle['fill']['color']['rgb'] = $aColors[$iGroupKey]['color_dark'];

								$sCells = $sC . $iRow . ':' . self::_getColumnCode($iCol + $aGroups['count'] - 1) . $iRow;

								$oExcel->getActiveSheet()->getStyle($sCells)->applyFromArray($aStyle);

								$oExcel->getActiveSheet()->mergeCells($sCells);
							}
						}
					}

					break;
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Second line by groups

				$iRow = 2;
				$iCol = 1;

				foreach($aLabels as $iPeriod => $aPeriods)
				{
					if($iPeriod == 1)
					{
						if(is_array($aPeriods['data']))
						{
							foreach($aPeriods['data'] as $iGroupKey => $aGroups)
							{
								if(is_array($aGroups['data']))
								{
									foreach($aGroups['data'] as $iGroupID => $aGroup)
									{
										$sC = self::_getColumnCode($iCol);

										$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aGroup['title']);

										$aStyle['fill']['color']['rgb'] = $aColors[$iGroupKey]['color_light'];

										$sCells = $sC . $iRow . ':' . self::_getColumnCode($iCol + $aGroup['count'] - 1) . $iRow;

										$oExcel->getActiveSheet()->getStyle($sCells)->applyFromArray($aStyle);

										$oExcel->getActiveSheet()->mergeCells($sCells);

										$iCol += $aGroup['count'];
									}
								}
							}
						}
					}

					break;
				}

				$iRow++;
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Column name line (3. line by groups)

			$iCol = 1;

			foreach($aLabels as $iPeriod => $aPeriods)
			{
				if($iPeriod == 1)
				{
					if(empty($this->_oStatistic->columns['groups']))
					{
						
						$oExcel->getActiveSheet()->mergeCells('A1:A2');
						$aStyle['fill']['color']['rgb'] = 'EEEEEE';
						$oExcel->getActiveSheet()->getStyle('A1:A2')->applyFromArray($aStyle);
//						$oExcel->getActiveSheet()->getColumnDimensionByColumn('A1:A2')->setAutoSize(true);

						if(is_array($aPeriods['data']))
						{
							foreach($aPeriods['data'] as $iColumnID => $aColumn)
							{
								if(!empty($aColumn['data']))
								{
									$sC = self::_getColumnCode($iCol);

									$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_dark'];

									$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

									$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aColumn['title']);

									$oExcel->getActiveSheet()->mergeCells($sC . $iRow . ':' . self::_getColumnCode($iCol - 1 + $aColumn['count']) . $iRow);

									$iCol += $aColumn['count'];
								}
								else
								{
									$sC = self::_getColumnCode($iCol++);

									$oExcel->getActiveSheet()->getColumnDimension($sC)->setWidth(30);

									$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_light'];

									$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aColumn['title']);

									$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

									$oExcel->getActiveSheet()->mergeCells($sC . $iRow . ':' . $sC . ($iRow + 1));
								}
							}
						}
					}
					else
					{
						if(is_array($aPeriods['data']))
						{
							foreach($aPeriods['data'] as $iGroupKey => $aGroups)
							{
								if(is_array($aGroups['data']))
								{
									foreach($aGroups['data'] as $iGroupID => $aGroup)
									{
										if(is_array($aGroup['data']))
										{
											foreach($aGroup['data'] as $iColumnID => $aColumn)
											{
												if(!empty($aColumn['data']))
												{
													$sC = self::_getColumnCode($iCol);

													$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aColumn['title']);

													$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_dark'];

													$sCells = $sC . $iRow . ':' . self::_getColumnCode($iCol - 1 + $aColumn['count']) . $iRow;

													$oExcel->getActiveSheet()->getStyle($sCells)->applyFromArray($aStyle);

													$oExcel->getActiveSheet()->mergeCells($sCells);

													$iCol += $aColumn['count'];
												}
												else
												{
													$sC = self::_getColumnCode($iCol++);

													$oExcel->getActiveSheet()->getColumnDimension($sC)->setWidth(30);

													$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aColumn['title']);

													$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_light'];

													$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

													$oExcel->getActiveSheet()->mergeCells($sC . $iRow . ':' . $sC . ($iRow + 1));
												}
											}
										}
									}
								}
							}
						}
					}
				}

				break;
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Column subnames line (4. line by groups)

			$iRow++;
			$iCol = 1;

			foreach($aLabels as $iPeriod => $aPeriods)
			{
				if($iPeriod == 1)
				{
					if(empty($this->_oStatistic->columns['groups']))
					{
						if(is_array($aPeriods['data']))
						{
							foreach($aPeriods['data'] as $iColumnID => $aColumn)
							{
								if(
									!empty($aColumn['data']) &&
									is_array($aColumn['data'])
								)
								{
									foreach($aColumn['data'] as $iColumnKey => $sColumn)
									{
										$sC = self::_getColumnCode($iCol++);

										$oExcel->getActiveSheet()->getColumnDimension($sC)->setAutoSize(true);

										$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_light'];

										$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

										$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $sColumn);
									}
								}
								else
								{
									$iCol++;
								}
							}
						}
					}
					else
					{
						if(is_array($aPeriods['data']))
						{
							foreach($aPeriods['data'] as $iGroupKey => $aGroups)
							{
								if(is_array($aGroups['data']))
								{
									foreach($aGroups['data'] as $iGroupID => $aGroup)
									{
										if(is_array($aGroup['data']))
										{
											foreach($aGroup['data'] as $iColumnID => $aColumn)
											{
												if(
													!empty($aColumn['data']) &&
													is_array($aColumn['data'])
												)
												{
													foreach($aColumn['data'] as $iColumnKey => $mColumn)
													{
														$sC = self::_getColumnCode($iCol++);

														$oExcel->getActiveSheet()->getColumnDimension($sC)->setAutoSize(true);

														$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_light'];

														$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

														$sColumn = $mColumn;
														if(is_array($mColumn)) {
															$sColumn = $mColumn['title'];
														}

														$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $sColumn);
													}
												}
												else
												{
													$iCol++;
												}
											}
										}
									}
								}
							}
						}
					}
				}

				break;
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write data

			$aStyle['fill']['color']['rgb'] = 'EEEEEE';

			PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheet\Cell\AdvancedValueBinder());

			foreach($aLabels as $iPeriod => $aPeriods)
			{
				$iRow++;
				$iCol = 0;

				$sC = self::_getColumnCode($iCol++);

				$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

				$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aPeriods['title']);

				if(empty($this->_oStatistic->columns['groups']))
				{
					if(is_array($aLabels[1]['data']))
					{
						foreach($aLabels[1]['data'] as $iColumnID => $aColumn)
						{
							if(
								!empty($aColumn['data']) &&
								is_array($aColumn['data'])
							)
							{
								foreach($aColumn['data'] as $iColumnKey => $sColumn)
								{
									$sC = self::_getColumnCode($iCol++);

									$sValue = $aData[$iPeriod][0][''][null][$iColumnID][$iColumnKey];

									if($iPeriod == '-')
									{
										$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);
									}

									$sType = $this->_getColumnType($iColumnID, $sValue);

									if(
										$sType !== false &&
										$sValue !== ''
									)
									{
										$oExcel->getActiveSheet()->getCell($sC . $iRow)->setValueExplicit($sValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

										$oExcel->getActiveSheet()->getStyle($sC . $iRow)->getNumberFormat()->setFormatCode($sType);
									}
									else
									{
										$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $sValue);
									}
								}
							}
							else
							{
								$sC = self::_getColumnCode($iCol++);

								$sValue = $aData[$iPeriod][0][''][null][$iColumnID];

								if($iPeriod == '-')
								{
									$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);
								}

								if(!is_array($sValue))
								{
									$sType = $this->_getColumnType($iColumnID, $sValue);

									if(
										$sType !== false &&
										$sValue !== ''
									)
									{
										$oExcel->getActiveSheet()->getCell($sC . $iRow)->setValueExplicit($sValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

										$oExcel->getActiveSheet()->getStyle($sC . $iRow)->getNumberFormat()->setFormatCode($sType);
									}
									else
									{
										$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $sValue);
									}
								}
							}
						}
					}
				}
				else
				{
					if(is_array($aLabels[1]['data']))
					{
						foreach($aLabels[1]['data'] as $iGroupKey => $aGroups)
						{
							if(is_array($aGroups['data']))
							{
								foreach($aGroups['data'] as $iGroupID => $aGroup)
								{
									if(is_array($aGroup['data']))
									{
										foreach($aGroup['data'] as $iColumnID => $aColumn)
										{
											if(
												!empty($aColumn['data']) &&
												is_array($aColumn['data'])
											)
											{
												foreach($aColumn['data'] as $iColumnKey => $sColumn)
												{
													$sC = self::_getColumnCode($iCol++);

													if(is_array($aData[$iPeriod][$iGroupKey][$iGroupID][null])) {
														$sValue = $aData[$iPeriod][$iGroupKey][$iGroupID][null][$iColumnID][$iColumnKey];
													} else {
														$sValue = $aData[$iPeriod][$iGroupKey][$iGroupID][$iColumnID][$iColumnKey];
													}

													if($iPeriod == '-')
													{
														$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);
													}

													$sType = $this->_getColumnType($iColumnID, $sValue);

													if(
														$sType !== false &&
														$sValue !== ''
													)
													{
														$oExcel->getActiveSheet()->getCell($sC . $iRow)->setValueExplicit($sValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

														$oExcel->getActiveSheet()->getStyle($sC . $iRow)->getNumberFormat()->setFormatCode($sType);
													}
													else
													{
														$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $sValue);
													}
												}
											}
											else
											{
												$sC = self::_getColumnCode($iCol++);

												$sValue = $aData[$iPeriod][$iGroupKey][$iGroupID][null][$iColumnID];

												if($iPeriod == '-')
												{
													$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);
												}

												$sType = $this->_getColumnType($iColumnID, $sValue);

												if(
													$sType !== false &&
													$sValue !== ''
												)
												{
													$oExcel->getActiveSheet()->getCell($sC . $iRow)->setValueExplicit($sValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

													$oExcel->getActiveSheet()->getStyle($sC . $iRow)->getNumberFormat()->setFormatCode($sType);
												}
												else
												{
													$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $sValue);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		/* ==================================================================================================== */

		else if($this->_oStatistic->list_type == 2) // Detail
		{
			foreach($aLabels as $iPeriod => $aPeriods)
			{
				if(is_array($aPeriods['data']))
				{
					foreach($aPeriods['data'] as $iColumnID => $aColumn)
					{
						$sC = self::_getColumnCode($iCol++);

						$aStyle['fill']['color']['rgb'] = $aColors[$iColumnID]['color_light'];

						$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);

						$oExcel->getActiveSheet()->getColumnDimension($sC)->setAutoSize(true);

						$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $aColumn['title']);
					}
				}
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write data

			$aStyle['fill']['color']['rgb'] = 'EEEEEE';

			foreach($aData as $iLineID => $aColumns)
			{
				$iRow++;
				$iCol = 0;

				if(is_array($aColumns))
				{
					foreach($aColumns as $iColumnID => $mValue)
					{
						$sC = self::_getColumnCode($iCol++);

						$sType = $this->_getColumnType($iColumnID, $mValue);

						if($iLineID == '-')
						{
							$oExcel->getActiveSheet()->getStyle($sC . $iRow)->applyFromArray($aStyle);
						}

						if(
							$sType !== false &&
							$mValue !== ''
						)
						{
							$oExcel->getActiveSheet()->getCell($sC . $iRow)->setValueExplicit($mValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

							$oExcel->getActiveSheet()->getStyle($sC . $iRow)->getNumberFormat()->setFormatCode($sType);
						}
						else
						{
							$oExcel->getActiveSheet()->setCellValue($sC . $iRow, $mValue);
						}
					}
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sFilename = \Util::getCleanFileName($this->_oStatistic->title);

		// Bei alpha.b funktioniert der Export ohne das hier nicht…
		ob_end_clean();
		ob_start('ob_gzhandler');

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $sFilename . '.xlsx"');
		header('Cache-Control: max-age=0');

		$oWriter = new PhpSpreadsheet\Writer\Xlsx($oExcel);
		$oWriter->save('php://output');
	}


	/**
	 * Prepare data for export 
	 */
	protected function _prepare()
	{
		$this->_oResult->format(true);
	}


	/**
	 * Wrapper for Ext_Thebing_Util::getColumnCodeForExcel() with caching
	 *
	 * @param int $iColumnNumber
	 * @return string
	 */
	protected static function _getColumnCode($iColumnNumber)
	{
		if(!isset(self::$_aCache['codes'][$iColumnNumber]))
		{
			self::$_aCache['codes'][$iColumnNumber] = Ext_Thebing_Util::getColumnCodeForExcel($iColumnNumber);
		}

		return self::$_aCache['codes'][$iColumnNumber];
	}


	/**
	 * Get the type of column data
	 * 
	 * @param $iColumnID
	 * @param &$sValue
	 * @return mixed
	 */
	protected function _getColumnType($iColumnID, &$sValue)
	{
		if($this->_oStatistic->list_type == 1) // Summe
		{
			switch((int)$iColumnID)
			{
				case 6:		// Schüler gesamt
				case 7:		// Erwachsene schüler
				case 8:		// Minderjährige schüler
				case 9:		// Weibliche Schüler
				case 10:	// Männliche Schüler
				case 11:	// ø Alter gesamt
				case 12:	// ø Alter männliche Schüler
				case 13:	// ø Alter weibliche Schüler
				case 15:	// Land
				case 16:	// Muttersprache
				case 17:	// Nationalität
				case 18:	// Status des Schülers
				case 19:	// Wie sind Sie auf uns aufmerksam geworden
				case 21:	// Agenturen
				case 23:	// Agenturkategorien
				case 25:	// Agenturgruppen
				case 27:	// Stornierungen gesamt
				case 28:	// Stornierungen Minderjähriger
				case 29:	// Stornierungen Erwachsener
				case 30:	// Stornierungen männlich
				case 31:	// Stornierungen weiblich
				case 34:	// Anfragen (Anzahl)
				case 35:	// Umwandlung (Anzahl)
				case 36:	// Umsätze (inkl. Storno) gesamt
				case 37:	// Umsätze je Kurskategorie
				case 38:	// Umsätze je Kurs
				case 39:	// Umsätze je Unterkunftskategorie
				case 41:	// Umsätze je generelle Kosten
				case 42:	// Umsätze je kursbezogene Kosten
				case 43:	// Umsätze Agenturkunden (netto, inkl. Storno)
				case 44:	// Umsätze Direktkunden (inkl. Storno)
				case 45:	// ø Reisepreis (alles, inkl. Storno)
				case 46:	// ø Kurspreis je Kurs
				case 47:	// ø Kurspreis je Kunde
				case 48:	// ø Kurspreis je Kurskategorie (Auflistung)
				case 49:	// ø Kurspreis je Kurs (Auflistung)
				case 50:	// ø Unterkunftspreis
				case 51:	// ø Unterkunftspreis je Unterkunftskategorie
				case 52:	// ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
				case 53:	// ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
				case 54:	// Zahlungseingänge (Summe)
				case 56:	// Umsätze je unterkunftsbezogene Kosten
				case 57:	// Zahlungsausgänge (Summe)
				case 63:	// Provision gesamt
				case 64:	// ø Provision absolut pro Kunde bei Agenturbuchungen
				case 65:	// ø Provisionssatz je Kunde bei Agenturbuchungen
				case 66:	// Stornierungsumsätze
				case 67:	// Summe je angelegtem Steuersatz
				case 68:	// Kurswochen je Kurs
				case 69:	// Kurswochen je Kurskategorie
				case 70:	// Kurswochen gesamt
				case 71:	// Unterkunftswochen je Unterkunftskategorie
				case 72:	// Unterkunftswochen je Unterkunft
				case 73:	// Anzahl Transfers (Anreise, Abreise, An- und Abreise)
				case 74:	// Anreise je Flughafen
				case 75:	// Abreise je Flughafen
				case 78:	// ø Kursdauer je Kurs in Wochen
				case 79:	// ø Kursdauer je Kurskategorie in Wochen
				case 80:	// ø Unterkunftsdauer je Unterkunft in Wochen
				case 81:	// ø Unterkunftsdauer je Unterkunftskategorie in Wochen
				case 82:	// ø Anzahl Schüler pro Lektion
				case 83:	// Auslastung in % bei Klassen in Bezug auf Maximalgröße (gesamt)
				case 84:	// ø Alter Kunde je Kurs
				case 86:	// ø Alter Kunde je Kurskategorie
				case 87:	// ø Alter Kunde je Unterkunftskategorie
				case 89:	// Versicherungen (Anzahl)
				case 90:	// Versicherungsumsatz
				case 91:	// Versicherungssumme je Versicherung
				case 93:	// Geleistete Stunden gesamt
				case 94:	// Geleistete Stunden je Niveau
				case 95:	// Margen Kurse (gesamt)
				case 96:	// Margen je Klasse (entsprechend Klassenplanung)
				case 97:	// Margen je Kurs
				case 98:	// Margen je Kurskategorie
				case 100:	// Margen Unterkunftsbezogen (gesamt)
				case 101:	// Margen je Unterkunftsanbieter
				case 102:	// Margen je Unterkunftskategorie
				case 103:	// Margen transferbezogen (gesamt)
				case 104:	// Margen je Transferanbieter (bei An- und Abreise: Preis/2)
				case 105:	// Margen je Transferabreise (bei An- und Abreise: Preis/2)
				case 106:	// Margen je Transferanreise (bei An- und Abreise: Preis/2)
				case 107:	// Margen Transfer gesamt je Flughafen
				case 108:	// Margen Transfer - Abreise je Flughafen
				case 109:	// Margen Transfer - Anreise je Flughafen
				case 113:	// Kosten Lehrer
				case 114:	// Kosten je Kurs
				case 115:	// Kosten je Kurskategorie
				case 116:	// Unterkunftskosten
				case 118:	// Kosten je Unterkunftskategorie
				case 119:	// Kosten Transfer gesamt
				case 120:	// Kosten Transfer - Abreise
				case 121:	// Kosten Transfer - Anreise
				case 122:	// Kosten Transfer gesamt je Flughafen
				case 123:	// Kosten Transfer - Abreise je Flughafen
				case 124:	// Kosten Transfer - Anreise je Flughafen
				case 137:	// Schulen
				case 138:	// Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
				case 139:	// Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
				case 144:	// Verdienst gesamt
				case 145:	// Verdienst je Kurskategorie
				case 146:	// Verdienst je Kurs
				case 147:	// Anzahl der Bewertungen
				case 151:	// ø Bewertung gesamt
				case 154:	// Umsatz je Unterkunftsanbieter
				case 156:	// Anzahl der Schüler (nur kursbezogen, mit Rechnung)
				case 157:	// Anzahl der Schüler (nur unterkunftsbezogen)
				case 159:	// Anzahl der Schüler (Erwachsene) nur kursbezogen exkl. Stornierungen
				case 160: 	// Anzahl der Schüler (Minderjährige) nur kursbezogen exkl. Stornierungen
				case 168:	// Anzahl der Schüler (nur unterkunftsbezogen)
				case 172:	// Umsätze gesamt (brutto, inkl. Storno)
				case 173:	// Umsätze gesamt (netto, inkl. Storno)
				case 176:	// Umsatz - gesamt (netto, inkl. Storno und Steuern)
				case 193:	// Gruppen
				case 196:	// Anzahl der Angebote
				case 197:	// Anzahl der Anfragen ohne Angebot
				case 198:	// Anzahl fälliger nachzuhakender Anfragen
				case 199:	// Anzahl umgewandelter Anfragen in %
				case 200:	// Durchschnittliche Dauer bis zur Umwandlung (Tage)
				case 201:	// Anzahl der Online-Anmeldungen (Buchungen)
				case 202:	// Anzahl der Online-Anmeldungen (Anfragen)
				case 203:	// Vertriebsmitarbeiter
				case 205: // Kurswochen je Kurskategorie (Erwachsene)
				case 206: // Kurswochen je Kurskategorie (Minderjährige)
				case 208: // Inbox
				{
					$bFormat = true;

					break;
				}
				default:
					$bFormat = false;
			}
		}
		else // Details
		{
			switch((int)$iColumnID)
			{
				case 5:		// Alter
				case 36:	// Umsätze (inkl. Storno) gesamt
				case 54:	// Zahlungseingänge (Summe)
				case 57:	// Zahlungsausgänge (Summe)
				case 63:	// Provision gesamt
				case 70:	// Kurswochen gesamt
				case 90:	// Versicherungsumsatz
				case 93:	// Geleistete Stunden gesamt
				case 126:	// Aufgenommene Schüler gesamt
				case 128:	// Anzahl der Bewertungen
				case 129:	// Niedrigste Bewertung (Note)
				case 130:	// Höchste Bewertung (Note)
				case 131:	// Häufigste Bewertung (Note, bei mehreren CVS)
				case 132:	// ø Bewertung gesamt
				case 140:	// Kursumsatz
				case 141:	// Unterkunftumsatz
				case 142:	// Stornierungsumsatz
				case 144:	// Verdienst gesamt
				case 147:	// Anzahl der Bewertungen
				case 148:	// Niedrigste Bewertungen
				case 149:	// Höchste Bewertungen
				case 150:	// Häufigste Bewertungen
				case 151:	// ø Bewertung gesamt
				case 170:	// Kursumsatz (brutto)
				case 171:	// Kursumsatz (netto)
				case 172:	// Umsätze gesamt (brutto, inkl. Storno)
				case 173:	// Umsätze gesamt (netto, inkl. Storno)
				case 176:	// Umsatz - gesamt (netto, inkl. Storno und Steuern)
				{
					$bFormat = true;

					break;
				}
				default:
					$bFormat = false;
			}
		}

		if($bFormat)
		{
			if($this->_oResult->checkColumnInt($iColumnID))
			{
				// Format integers
				$sFormat = PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER;
			}
			else
			{
				// Format comma numbers
				$sFormat = PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

				if(!isset(self::$_aCache['format']['t']) || !isset(self::$_aCache['format']['e']))
				{
					$t = ".";
					$e = ",";

					$oSchool = Ext_Thebing_School::getFirstSchool();

					// Referenzen
					Ext_Thebing_Format::createNumberFormatPoints($oSchool, $e, $t);

					// Eigentlich sollte das auf die Zahlen keinen Einfluss haben
					// Zur Sicherheit aber trotzdem setzen
					PhpSpreadsheet\Shared\StringHelper::setDecimalSeparator($e);
					PhpSpreadsheet\Shared\StringHelper::setThousandsSeparator($t);

					self::$_aCache['format'] = array(
						't' => $t,
						'e' => $e
					);
				}

				// Wert ist hier bereits formatiert und wird an dieser Stelle wieder zu einem PHP-Float-Format umgewandelt
				$sValue = str_replace(array(self::$_aCache['format']['t'], self::$_aCache['format']['e']), array('', '.'), $sValue);
			}

			if($sValue <= 0.005 && $sValue >= -0.005)
			{
				$sValue = '';
			}

			return $sFormat;
		}

		return false;
	}
	
	public function export() {
		$this->_export();
	}
	
}