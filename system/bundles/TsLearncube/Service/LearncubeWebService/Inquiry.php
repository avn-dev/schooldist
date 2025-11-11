<?php

namespace TsLearncube\Service\LearncubeWebService;
use Core\Facade\Cache;

class Inquiry extends \TsLearncube\Service\LearncubeWebService {

	// Bezüglich Anfragen, falls das später noch hinzgefügt wird: An die Gruppenanfragen denken!
	// (-> mehrere Buchungen?)

	/**
	 * @param \Ext_TS_Inquiry $inquiry
	 */
	public function sync(\Ext_TS_Inquiry $inquiry)
	{
		$customer = $inquiry->getCustomer();
		$email = $customer->getEmail();

		// E-Mail ist in Learncube ein Pflichtfeld, bei uns nicht
		// (Ohne E-Mail funktioniert weder das Updaten, noch das Erstellen)
		// Löschen funktioniert auch noch nicht, aber der "ts_inquiry_save"-Hook wird beim Löschen aufgerufen
		if (
			empty($email) ||
			$inquiry->active == 0
		) {
			return false;
		}

		$client = $this->getClient();

		$auth = $this->getAuthArray();

		$token = $this->getToken();

		$userReference = $inquiry->id;

		// Für die 2. Verifizierung im VerificationController
		Cache::put($this::getCacheKey($userReference), 20, $token);

		// Schüler erstellen / aktualisieren (API aktualisiert, wenn "user_reference" schon vorhanden ist)
		$client->post('rest-api/v3/create-user/', [
			'json' =>
				[
					'email' => $email,
					'first_name' => $customer->firstname,
					'last_name' => $customer->lastname,
					'token' => $token,
					'profile' => [
						// "user_reference ist die ID vom Schüler in Learncube
						'user_reference' => $userReference,
						'subscription' => 'active',
						'locale' => $customer->corresponding_language,
					],
					'sso_type' => 'token'
				],
			'headers' => $auth
		]);

		return true;
	}

}
