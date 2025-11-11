<?php

namespace TsFrontend\Interfaces\PaymentProvider;

use Illuminate\Support\Collection;
use TsFrontend\Exceptions\PaymentChallenge;
use TsFrontend\Exceptions\PaymentError;

interface RegistrationForm extends PaymentProvider {

	/**
	 * @TODO checkPayment wird auch im Payment Form verwendet für PaymentPopup.vue (TM, Flywire, Redsys)
	 *
	 * Validierung: Gültigkeit der autorisierten Zahlung überprüfen BEVOR die Buchung gespeichert wird
	 *
	 * @param Collection $data
	 * @return bool
	 * @throws PaymentChallenge
	 * @throws PaymentError
	 */
	public function checkPayment(Collection $data): bool;

}
