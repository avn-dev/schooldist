<?php
/**
 * Zentralisierung der E-Mail_Cronjobs auf TC-Basis
 *
 * Redmine Ticket #3225
 *
 * @since 06.11.2012
 * @author DG <dg@plan-i.de>
 */
class Ext_Thebing_System_Checks_Communication_AutomaticEmails extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Update automatic e-mail configuration';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Update automatic e-mail configuration';
		return $sDescription;
	}

	public function executeCheck(){

		Util::backupTable('kolumbus_email_templates_cronjob');

		// Check, ob Check bereits durchgelaufen ist
		try {
			$aTableDescription = DB::describeTable('kolumbus_email_templates_cronjob', true);
		} catch(DB_QueryFailedException $e) {
			return true;
		}

		// Tabelle entfernen, wenn diese durch das Update kam
		Util::backupTable('tc_communication_automatictemplates');
		$sSql = "
			DROP TABLE IF EXISTS
				`tc_communication_automatictemplates`
		";
		DB::executeQuery($sSql);

		// kolumbus_email_templates_cronjob => tc_communication_automatictemplates
		$sSql = "
			RENAME TABLE
				`kolumbus_email_templates_cronjob`
			TO
				`tc_communication_automatictemplates`
		";
		DB::executeQuery($sSql);

		// Löst die Daten der Spalte »to_customer« auf und schreibt die Werte in die neue JoinTable
		$this->_importToRecipientJoinTable();

		// Tabellenstruktur anpassen
		$sSql = "
			ALTER TABLE `tc_communication_automatictemplates`
				CHANGE `user_id` `editor_id` INT(11) NOT NULL,
				CHANGE `email` `to` VARCHAR(255) NOT NULL,
				DROP `to_customer`,
				DROP `client_id`
		";
		DB::executeQuery($sSql);

		return true;

	}

	/**
	 * Löst die Spalte »to_customer« auf und schreibt die Werte in die neue JoinTable
	 */
	protected function _importToRecipientJoinTable()
	{
		$sSql = "
			SELECT
				`id`, `email`, `to_customer`
			FROM
				`tc_communication_automatictemplates`
		";

		$oStatement = DB::getPreparedStatement("
			INSERT INTO
				`tc_communication_automatictemplates_recipients`
			SET
				`template_id` = ?,
				`recipient` = ?
		");

		$aRows = (array)DB::getQueryRowsAssoc($sSql);
		foreach($aRows as $iRowId => $aRow) {

			if(!empty($aRow['to_customer'])) {
				DB::executePreparedStatement($oStatement, array(
					$iRowId, 'customer'
				));
			}

			if(!empty($aRow['email'])) {
				DB::executePreparedStatement($oStatement, array(
					$iRowId, 'individual'
				));
			}

		}
	}

}
