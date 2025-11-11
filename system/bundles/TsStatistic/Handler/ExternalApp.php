<?php

namespace TsStatistic\Handler;

class ExternalApp extends \Ts\Handler\ExternalAppPerSchool {

	const APP_NAME = 'quic_report';

	const KEY_CONTACT_NAME = 'quic_report_contact_name';
	const KEY_CENTRE_NAME = 'quic_report_centre_name';
	const KEY_MEMBER_NO = 'quic_report_member_no';
	const KEY_EMAIL = 'quic_report_email';
	const KEY_POSTCODE = 'quic_report_postcode';
	const KEY_CONTACT_TELEPHONE_NUMBER = 'quic_report_contact_telephone_number';
	const KEY_POSITION_OF_MAIN_CONTACT = 'quic_report_position_of_main_contact';

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('QUIC Report');
	}

	/**
	 * @return string
	 */
	public function getDescription() : string {
		return \L10N::t('QUIC Report - Beschreibung');
	}

	public function getIcon(): string {
		return 'fas fa-table';
	}

	public function getSettings(): array {
		return [
			self::KEY_CONTACT_NAME => [
				'label' => 'Contact name',
				'type' => 'input'
			],
			self::KEY_CENTRE_NAME => [
				'label' => 'Centre name',
				'type' => 'input'
			],
			self::KEY_MEMBER_NO => [
				'label' => 'Member No.',
				'type' => 'input'
			],
			self::KEY_EMAIL => [
				'label' => 'E-Mail',
				'type' => 'input'
			],
			self::KEY_POSTCODE => [
				'label' => 'Postcode',
				'type' => 'input'
			],
			self::KEY_CONTACT_TELEPHONE_NUMBER => [
				'label' => 'Contact telephone number',
				'type' => 'input'
			],
			self::KEY_POSITION_OF_MAIN_CONTACT => [
				'label' => 'Position of main contact',
				'type' => 'input'
			],
		];
	}

	public function install(): void {
		// Das Recht ist an das Modul der App gekoppelt, also beim Installieren erscheint dann auch das Rechte
		// -> beim Deinstallieren der App wird das Recht dann ebenfalls "ausgeblendet"
		\Factory::executeStatic('Ext_TC_Update', 'updateAccessDatabase');
		\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);
	}

	public function uninstall(): void {
		$this->install();
	}

}
