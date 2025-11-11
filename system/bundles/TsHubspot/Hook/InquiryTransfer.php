<?php

namespace TsHubspot\Hook;

use TsHubspot\Service\Inquiry;

/** Die Klasse wird nur vor dem Buchungsspeichern aufgerufen und setzt die TravellerHubspotId, die durch die Hubspot-Kontaktsuche kommt
 * Im normalen Inquiry save Hook gibt es kein Request Objekt mit dem gearbeitet werden kann.
 */

class InquiryTransfer extends Transfer {

	public function run($data) {

		if(\TsHubspot\Handler\ExternalApp::isActive()) {

			$request = $data['request'];

			$hubspotId = (int)$request->get('replaceHubspotContactId');

			// Bei Hubspot-Kontaktsuche
			if (!empty($hubspotId)) {
				Inquiry::$travellerHubspotId = $hubspotId;
			}

		}

	}

}

