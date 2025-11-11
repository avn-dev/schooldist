<?php

class Ext_TS_System_Checks_Payment_PaymentMethodProviders extends GlobalChecks {

	public function getTitle() {
		return 'Payment provider allocation to payment methods';
	}

	public function getDescription() {
		return 'Instead of chosing the payment method in frontend combination or registration form, the payment method is chosen again globally. If a payment provider is used and there is no matching payment method, the first available payment method will be used.';
	}

	public function printFormContent() {

		$providers = (new \TsFrontend\Factory\PaymentFactory())->getOptions();

		if (empty($providers)) {
			echo 'There are no enabled external apps with payment providers.';
			parent::printFormContent();
			return;
		}

		$providers = Util::addEmptyItem($providers, '', '');
		$methods = Ext_Thebing_Admin_Payment::getPaymentMethods();

		printTableStart();

		foreach ($methods as $method) {
			if (!empty($method->type)) {
				// Scheck+Verrechnung und bereits eingestellte ignorieren
				continue;
			}

			$schools = array_map(fn(Ext_Thebing_School $school) => $school->short, $method->getJoinTableObjects('schools'));
			printFormSelect(sprintf('%s (%s)', $method->name, join(', ', $schools)), 'payment_method['.$method->id.']', $providers, $method->type);
		}

		printTableEnd();

		parent::printFormContent();

	}

	public function executeCheck() {
		global $_VARS;

		Util::backupTable('tc_frontend_combinations_items');
		Util::backupTable('kolumbus_forms_pages_blocks_settings');

		DB::executeQuery("DELETE FROM tc_frontend_combinations_items WHERE item = 'payment_method' AND combination_id IN (SELECT id FROM tc_frontend_combinations WHERE `usage` = 'payment_form')");
		DB::executeQuery("DELETE FROM kolumbus_forms_pages_blocks_settings WHERE setting = 'method' AND block_id IN (SELECT id FROM kolumbus_forms_pages_blocks WHERE block_id = 7)");

		$providers = (new \TsFrontend\Factory\PaymentFactory())->getOptions();
		if (empty($providers)) {
			$this->logInfo('No payment providers.');
			return true;
		}

		$this->logInfo('Submitted options', (array)$_VARS['payment_method']);

		foreach ((array)$_VARS['payment_method'] as $paymentMethodId => $provider) {
			if (empty($provider)) {
				continue;
			}

			if (!isset($providers[$provider])) {
				throw new RuntimeException('Provider '.$provider.' does not exist');
			}

			$paymentMethod = Ext_Thebing_Admin_Payment::getInstance($paymentMethodId);
			$paymentMethod->type = Ext_Thebing_Admin_Payment::TYPE_PROVIDER_PREFIX.$provider;
			$paymentMethod->save();

			$this->logInfo(sprintf('Set payment method id %d type to %s', $paymentMethod->id, $paymentMethod->type));
		}

		return true;

	}

}