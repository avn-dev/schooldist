<?php

namespace TsFlywire\Handler;

class ExternalAppSync extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'flywire_sync';

	public function getIcon(): string {
		return 'fas fa-file-csv';
	}
	
	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Flywire - Reconciliation Sync');
	}

	public function getDescription() : string {
		return \L10N::t('Daily sync of Flywire payments.');
	}

	public function getCategory(): string {
		return \TcExternalApps\Interfaces\ExternalApp::CATEGORY_ACCOUNTING;
	}

	protected function getConfigKeys(): array {
		
		$aSchoolIds = array_keys(\Ext_Thebing_Client::getSchoolList(true));
		$aPaymentMethods = \Util::addEmptyItem(\Ext_Thebing_Admin_Payment::getPaymentMethods(true, $aSchoolIds));
		
		$aCurrencies = \Ext_Thebing_Currency_Util::getAllSchoolsCurrencyList(2);
		$aCurrencies = \Ext_Thebing_Util::addEmptyItem($aCurrencies);

		$aHours = \Ext_TC_Util::getHours();
		
		$aConfigKeys = [];
		
		$aConfigKeys[] = [
			'title' => \L10N::t('SSH-User'),
			'key' => 'flywire_ssh_user'
		];
		$aConfigKeys[] = [
			'title' => \L10N::t('Flywire-Prefix'),
			'key' => 'flywire_prefix'
		];
		$aConfigKeys[] = [
			'title' => \L10N::t('Währung'),
			'key' => 'flywire_currency',
			'type' => 'select',
			'options' => $aCurrencies
		];
		$aConfigKeys[] = [
			'title' => \L10N::t('Zahlungsmethode'),
			'key' => 'flywire_method_id',
			'type' => 'select',
			'options' => $aPaymentMethods
		];
		$aConfigKeys[] = [
			'title' => \L10N::t('Zeitpunkt des täglichen Abgleichs'),
			'key' => 'flywire_sync_time',
			'type' => 'select',
			'options' => $aHours
		];

		return $aConfigKeys;
	}
	
	public function install() {
		\DB::executeQuery("CREATE TABLE IF NOT EXISTS `ts_payments_flywire_filesync` (  `file` varchar(100) NOT NULL,  `payments` smallint(5) UNSIGNED DEFAULT NULL,  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  PRIMARY KEY (`file`)) ENGINE=InnoDB");
	}

	public function uninstall() {
		\Util::backupTable('ts_payments_flywire_filesync');
		\DB::executeQuery("DROP TABLE IF EXISTS `ts_payments_flywire_filesync`");
	}

}