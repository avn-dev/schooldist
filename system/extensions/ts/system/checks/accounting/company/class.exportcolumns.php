<?php

/**
 * Default-Settings für neu eingeführte Auswahl der Spalten für Buchungsstapel-Export
 *
 * https://redmine.thebing.com/redmine/issues/8826
 */
class Ext_TS_System_Checks_Accounting_Company_ExportColumns extends GlobalChecks {

	public function getTitle() {
		return 'Booking stack: Exportable columns';
	}

	public function getDescription() {
		return 'Set default columns in company settings for booking stack export.';
	}

	public function executeCheck() {

		$oFactory = new Ext_Gui2_Factory('ts_booking_stack');
		$oBookingstackGui = $oFactory->createGui();
		$oGuiData = $oBookingstackGui->getDataObject(); /** @var Ext_TS_Accounting_BookingStack_Gui2_Data $oGuiData */

		$sSql = "
			SELECT
				`ts_com`.`id`
			FROM
				`ts_companies` `ts_com` LEFT JOIN
				`ts_companies_colums_export` `ts_comce` ON
					`ts_comce`.`company_id` = `ts_com`.`id`
			WHERE
				`ts_comce`.`column` IS NULL
			GROUP BY
				`ts_com`.`id`
		";

		$aCompanyIds = (array)DB::getQueryCol($sSql);

		DB::begin(__CLASS__);

		foreach($aCompanyIds as $iCompanyId) {

			// Alle Columns holen, Flex ignorieren
			$aColumns = $oBookingstackGui->getColumnList();

			// Columns nach Firmeneinstellungen filtern (die waren vorher auch nicht in der Liste und im Export)
			$aColumns = array_filter($aColumns, function($oColumn) use($oGuiData) {
				if(
					(
						$oColumn->db_column === 'account_number_expense' ||
						$oColumn->db_column === 'qb_number' ||
						$oColumn->db_column === 'document_type' ||
						$oColumn->db_column === 'customer_number' ||
						$oColumn->db_column === 'customer_name'
					) &&
					!$oGuiData->isColumnShown($oColumn->db_column)
				) {
					return false;
				}

				return true;
			});

			$aColumns = array_map(function($oColumn) {
				return $oColumn->db_column;
			}, $aColumns);

			DB::updateJoinData('ts_companies_colums_export', ['company_id' => $iCompanyId], $aColumns, 'column', 'position');

		}

		DB::commit(__CLASS__);

		return true;

	}
}