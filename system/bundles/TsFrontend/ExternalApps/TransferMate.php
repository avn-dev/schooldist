<?php

namespace TsFrontend\ExternalApps;

class TransferMate extends \Ts\Handler\ExternalAppPerSchool {

	const KEY = 'transfermate';

	public function getTitle(): string {
		return 'TransferMate';
	}

	public function getDescription(): string {
		return $this->t('TransferMate - Beschreibung');
	}

	public function getIcon(): string {
		return 'fas fa-landmark';
	}
	
	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::PAYMENT_PROVIDER;
	}

	public function getSettings(): array {
		return [
			'transfermate_client' => [
				'label' => 'Client',
				'type' => 'input',
				'description' => 'https://<strong>CLIENT</strong>.transfermateeducation.com/paymentservice.dll'
			],
			'transfermate_username' => [
				'label' => 'Username',
				'type' => 'input'
			],
			'transfermate_password' => [
				'label' => 'Password',
				'type' => 'password'
			],
			'transfermate_bank_account_id' => [
				'label' => 'Bank Account ID',
				'type' => 'input'
			],
			'transfermate_hmac_secret' => [
				'label' => 'HMAC-Secret',
				'type' => 'password'
			]
		];
	}

//	public function install() {
//
//		\DB::executeQuery("
//			CREATE TABLE IF NOT EXISTS `ts_payments_transfermate_processes` (
//				`process_id` mediumint(8) UNSIGNED NOT NULL,
//				`transaction_id` mediumint(8) UNSIGNED NOT NULL COMMENT 'TransferMate-ID',
//				`combination_id` mediumint(8) UNSIGNED NOT NULL,
//				PRIMARY KEY (`process_id`,`transaction_id`)
//			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
//		");
//
//	}
//
//	public function uninstall() {
//
//		\Util::backupTable('ts_payments_transfermate_processes');
//		\DB::executeQuery("DROP TABLE IF EXISTS `ts_payments_transfermate_processes`");
//
//	}

}