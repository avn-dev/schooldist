<?php

namespace TsFrontend\Handler\Payment;

use Illuminate\Support\Collection;
use Tc\Service\Language\Frontend;
use TsFrontend\Interfaces\PaymentProvider;
use TsFrontend\Traits\PaymentProviderTrait;

class Skip implements PaymentProvider\RegistrationForm {

	use PaymentProviderTrait;

	const KEY = 'skip';

	public function getScriptUrl(): string {
		return '';
	}

	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array {
		return [];
	}

	public function checkPayment(Collection $data): bool {
		return true;
	}

	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated {
		return null;
	}

	public function getTranslations(Frontend $languageFrontend): array {
		return [
			'description' => $languageFrontend->translate('You\'ll be contacted later to perform your payment.'),
			'book_now' => 'BOOK_NOW' // Übersetzung wird mit der entsprechenden Übersetzung vom Form in PaymentRequestHelper ersetzt, da Skip nur im RegForm existiert
		];
	}

	public function getPaymentMethods(Frontend $languageFrontend): array {

		return [
			[
				'key' => self::KEY,
				'label' => $languageFrontend->translate('Skip payment'),
				'description' => $languageFrontend->translate('The payment is optional in this step.'),
				'icon_class' => 'fas fa-forward'
			]
		];

	}

	public function getSortOrder(): int {
		return 0;
	}

}