<?php

namespace TsAccounting\Gui2\Data;

class DebtorsReport extends \Ext_Thebing_Gui2_Data {
	
	public static function getOrderby() {
		return ['account_name'=>'ASC'];
	}
	
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {
		global $_VARS;

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= (int)reset($aSelectedIds);

		$sDescription	= $this->_oGui->gui_description;

		// get dialog object
		switch($sIconAction) {
			case 'transactions':

				$sDialogTitle = $this->t('Transaktionen');

				$oDialog = $this->_oGui->createDialog($sDialogTitle, $sDialogTitle, $sDialogTitle);

				$oFactory = new \Ext_Gui2_Factory('tsAccounting_debtors_report_transactions');
				$oGuiChild = $oFactory->createGui('transactions', $this->_oGui);
				
				$oDialog->setElement($oGuiChild);

				$oDialog->save_button = false;

				$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

				break;
			default :
				$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
				break;
		}

		return $aData;
	}
		
	protected function manipulateSqlParts(array &$aSqlParts, string $sView) {
		
		if($this->_oGui->sView == 'report') {

			$aSqlParts['select'] .= ",
				(
					SELECT 
						`ts_at_period`.`id` 
					FROM 
						`ts_accounts_transactions` `ts_at_period` 
					WHERE 
						`ts_at`.`account_type` = `ts_at_period`.`account_type` AND
						`ts_at`.`account_id` = `ts_at_period`.`account_id` AND
						`ts_at_period`.`due_date` BETWEEN '".$this->reportPeriod->getStartDate()->toDateString()."' AND '".$this->reportPeriod->getEndDate()->toDateString()."' 
					LIMIT 1
				)  `transaction_in_period`"
					. "";
			
			$aSqlParts['having'] .= " `ledger_balance` != 0 OR `transaction_in_period` IS NOT NULL";
			
		}
		
	}
	
	public function export_excelReport($aVars) {

		ini_set('memory_limit', '4G');

		$this->_oGui->sView = 'report';
		
		$agedDebtorReport = new \TsAccounting\Service\Accounts\AgedDebtorReport([$this, 't']);

		if(!empty($aVars['filter']['search_date'])) {
			$agedDebtorReport->filterDate = \Ext_Thebing_Format::ConvertDate($aVars['filter']['search_date'], null, 3);
		}

		if(!empty($aVars['filter']['search_string'])) {
			$agedDebtorReport->filterSearch = $aVars['filter']['search_string'];
		}
				
		if(!empty($aVars['filter']['search_type'])) {
			$agedDebtorReport->filterType = $aVars['filter']['search_type'];
		}
		
		if(isset($aVars['filter']['report_back'])) {
			$agedDebtorReport->reportBackIntervals = (int)$aVars['filter']['report_back'];
		}
		
		if(isset($aVars['filter']['report_forth'])) {
			$agedDebtorReport->reportForthIntervals = (int)$aVars['filter']['report_forth'];
		}
		
		if(!empty($aVars['filter']['report_interval'])) {
			$agedDebtorReport->reportInterval = (int)$aVars['filter']['report_interval'];
		}
		
		if(!empty($aVars['filter']['report_include'])) {
			$agedDebtorReport->reportInclude = (string)$aVars['filter']['report_include'];
		}
		
		$this->reportPeriod = $agedDebtorReport->getReportPeriod();
		
		// search_date Filter-Wert darf nicht übernommen werden, da wir alle nicht ausgeglichenen Konten brauchen
		$data = $this->getTableQueryData($filterValues, [], [], true);

		$excel = $agedDebtorReport->generate($data);
		
		$excel->generate();
		$excel->render();
		die();

	}

	static public function getIntervalOptions(\Ext_Gui2 $gui) {
		
		$options = [
			7 => $gui->t('7 Tage Intervall'),
			14 => $gui->t('14 Tage Intervall'),
			30 => $gui->t('30 Tage Intervall')
		];
		
		return $options;
	}

	static public function getIncludeOptions(\Ext_Gui2 $gui) {
		
		$options = [
			'without_proforma' => $gui->t('Ohne Proforma'),
			'with_proforma' => $gui->t('Mit Proforma')
		];
		
		return $options;
	}

	static public function getIntervalNumbersOptions($direction, \Ext_Gui2 $gui) {
		
		if($direction === 'back') {
			
			$options = [
				'0' => $gui->t('-0 Intervalle'),
				'3' => $gui->t('-3 Intervalle'),
				'4' => $gui->t('-4 Intervalle'),
				'5' => $gui->t('-5 Intervalle'),
				'6' => $gui->t('-6 Intervalle')
			];

		} else {

			$options = [
				'0' => $gui->t('+0 Intervalle'),
				'3' => $gui->t('+3 Intervalle'),
				'4' => $gui->t('+4 Intervalle'),
				'5' => $gui->t('+5 Intervalle'),
				'6' => $gui->t('+6 Intervalle')
			];

		}
		
		return $options;
	}
	
}
