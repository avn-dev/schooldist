<?php

namespace Ts\Gui2\Data\Special;

class Code extends \Ext_Thebing_Gui2_Data {
	
	public static function getOrderby() {
		return ['code'=>'ASC'];
	}
	
	public static function getDialog(\Ext_Gui2 $gui) {

		$dialog = $gui->createDialog($gui->t('Rabattcode "{code}" editieren'), $gui->t('Neuen Rabattcode anlegen'));
		$dialog->width = 750;
		$dialog->height = 600;
		
		$dialog->setElement($dialog->createRow($gui->t('Code'), 'input', array(
			'db_column' => 'code',
			'db_alias' => 'ts_spc',
			'required' => 1
		)));

		$dialog->setElement(
			$dialog->createMultiRow(
				$gui->t('Gültigkeit'),
				[
					'items' => [
						[
							'input'=>'calendar',
							'db_alias' => '',
							'db_column'=>'valid_from',
							'format' => new \Ext_Thebing_Gui2_Format_Date()
						],
						[
							'input'=>'calendar',
							'db_alias' => '',
							'db_column'=>'valid_until',
							'format' => new \Ext_Thebing_Gui2_Format_Date(),
							'text_before' => '<span class="row_until">&nbsp;'.$gui->t('bis').'</span>'
						]

					]
				]
			)
		);

		self::createUsageLimitField($gui, $dialog);
		
		return $dialog;
	}

	public static function getEditDialog(\Ext_Gui2 $gui) {

		$dialog = $gui->createDialog($gui->t('Rabattcode "{code}" editieren'), $gui->t('Neuen Rabattcode anlegen'));
		$dialog->width = 750;
		$dialog->height = 600;

		$dialog->setElement(
			$dialog->createMultiRow(
				$gui->t('Gültigkeit'),
				[
					'items' => [
						[
							'input'=>'calendar',
							'db_alias' => '',
							'db_column'=>'valid_from',
							'format' => new \Ext_Thebing_Gui2_Format_Date('convert_null')
						],
						[
							'input'=>'calendar',
							'db_alias' => '',
							'db_column'=>'valid_until',
							'format' => new \Ext_Thebing_Gui2_Format_Date('convert_null'),
							'text_before' => '<span class="row_until">&nbsp;'.$gui->t('bis').'</span>'
						]
					]
				]
			)
		);

		return $dialog;
	}
	
	public static function getGenerateDialog(\Ext_Gui2 $gui) {

		$dialog = $gui->createDialog($gui->t('Rabattcodes generieren'), $gui->t('Rabattcodes generieren'));
		$dialog->width = 800;
		$dialog->height = 600;
		
		$dialog->setElement($dialog->createRow($gui->t('Format'), 'select', [
			'db_column' => 'format',
			'required' => 1,
			'select_options' => [
				'number' => $gui->t('Numerisch'),
				'alphanumeric' => $gui->t('Alphanumerisch')
			]
		]));
				
		$dialog->setElement($dialog->createRow($gui->t('Anzahl Stellen'), 'input', array(
			'db_column' => 'digits',
			'required' => 1,
			'value' => 6
		)));
		
		$dialog->setElement($dialog->createRow($gui->t('Anzahl Codes'), 'input', array(
			'db_column' => 'number',
			'required' => 1,
			'value' => 1
		)));
		
		$dialog->setElement(
			$dialog->createMultiRow(
				$gui->t('Gültigkeit'), 
				[
					'items' => [
						[
							'input'=>'calendar',
							'db_alias' => '', 
							'db_column'=>'from', 
							'format' => new \Ext_Thebing_Gui2_Format_Date()
						],
						[
							'input'=>'calendar',
							'db_alias' => '', 
							'db_column'=>'until', 
							'format' => new \Ext_Thebing_Gui2_Format_Date(),
							'text_before' => '<span class="row_until">&nbsp;'.$gui->t('bis').'</span>'
						]

					]
				]
			)
		);

		self::createUsageLimitField($gui, $dialog);

		return $dialog;
	}
	protected static function createUsageLimitField(\Ext_Gui2 $gui, \Ext_Gui2_Dialog $dialog)
	{
		$usageLimitOptions = [
			'1' => $gui->t('Einfach')
		];

		$i = 2;
		do {

			$usageLimitOptions[$i] = $i.$gui->t('-fach');

			if($i<20) {
				$i++;
			} elseif($i<100) {
				$i += 10;
			} else {
				$i += 100;
			}

		} while($i<=1000);

		$usageLimitOptions[''] = $gui->t('Unendlich');

		$dialog->setElement($dialog->createRow($gui->t('Verwendbar'), 'select', [
			'db_column' => 'usage_limit',
			'required' => 1,
			'select_options' => $usageLimitOptions,
			'format' => new \Ext_Gui2_View_Format_Null()
		]));

	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		
		if($sAction === 'generate') {

			\DB::begin(__METHOD__);

			$specialId = (int)reset($this->getParentGuiIds());

			$numberOfCodes = (int)$aData['number'];

			if($aData['usage_limit'] === '') {
				$usageLimit = null;
			} else {
				$usageLimit = (int)$aData['usage_limit'];
			}

			$validFrom = $validUntil = null;
			if(!empty($aData['from'])) {
				$validFrom = \Ext_Thebing_Format::ConvertDate($aData['from'], null, 1);
			}
			if(!empty($aData['until'])) {
				$validUntil = \Ext_Thebing_Format::ConvertDate($aData['until'], null, 1);
			}

			$enteredCodes = 0;
			$codes = [];
			do {

				if($aData['format'] === 'alphanumeric') {
					$code = \Util::generateRandomString($aData['digits']);
				} else {
					$code = random_int($this->findSmallestNumberWithNDigits($aData['digits']), ($this->findSmallestNumberWithNDigits($aData['digits']+1)-1));
				}

				$codeData = [
					'special_id' => $specialId,
					'code' => $code,
					'valid_from' => $validFrom,
					'valid_until' => $validUntil,
					'usage_limit' => $usageLimit,
				];

				$insertId = \DB::insertData('ts_specials_codes', $codeData);

				if($insertId) {
					$enteredCodes++;
				}

			} while($enteredCodes < $numberOfCodes);

			\DB::commit(__METHOD__);

			$transfer = [
				'action' => 'closeDialogAndReloadTable',
				'data' => ['id' => 'ID_'.implode('_', (!empty($aSelectedIds)) ? $aSelectedIds : [0])],
				'error' => []
			];
			
		} else {
			
			$transfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
			
		}
		
		return $transfer;
	}
	
	private function findSmallestNumberWithNDigits($n) {
		if ($n <= 0) {
			throw new \InvalidArgumentException('Number of digits has to be greater zero.');
		}
		return pow(10, $n - 1);
	}


	public function manipulateSqlParts(&$aSqlParts, $sView = null): void
	{
		$aSqlParts['select'] .= ", 
		 COUNT(usages.inquiry_id) AS usage_count
		";


	}
}