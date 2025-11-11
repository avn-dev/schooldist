<?php

namespace TsMews;

use Core\DTO\DateRange;
use TsMews\Api\Operations;
use TsMews\Api\Operations\AddReservation;
use TsMews\Api\Operations\CancelReservation;
use TsMews\Api\Operations\DeleteCompanion;
use TsMews\Api\Operations\UpdateReservation;
use TsMews\Api\Operations\UpdateReservationSpace;
use TsMews\Api\Request;
use TsMews\Exceptions\AuthenticateException;
use TsMews\Exceptions\FailedException;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Handler\ExternalApp;
use TsMews\Interfaces\Operation;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use TsMews\Service\Synchronization;

/**
 * https://mews-systems.gitbook.io/connector-api/
 * @package TsMews
 */
class Api {

	const LOGGER_KEY = 'ts_mews';

	const STATE_OPTIONAL = "Optional";

	const STATE_CONFIRMED = "Confirmed";

	const STATE_CANCELED = "Canceled";

	const STATE_STARTED = "Started";

	const STATE_PROCESSED = "Processed";

    private $url;

    private $clientToken;

    private $accessToken;

    public function __construct(string $url, string $clientToken, string $accessToken) {
        $this->url = Str::finish($url, '/').'api/connector/v1';
        $this->clientToken = $clientToken;
        $this->accessToken = $accessToken;
    }

    /**
     * Synchronisiert einen Customer (wird hinzugefügt oder aktualisiert)
     *
     * @param \Ext_TS_Inquiry_Contact_Traveller $customer
     * @return Collection
     */
    public function syncCustomer(\Ext_TS_Inquiry_Contact_Traveller $customer): Collection {

    	$mewsId = $customer->getMeta('mews_id');

        $operation = ($mewsId !== null)
            ? new Operations\UpdateCustomer($customer)
            : new Operations\AddCustomer($customer);

        return $this->request($operation);
    }

    /**
     * Liefert alle Reservierungen für einen Zeitraum
     *
     * @param DateRange $dateRange
     * @return Collection
     */
    public function searchReservations(DateRange $dateRange): Collection {
        $operation = (new Operations\GetReservations())
			->inDateRange($dateRange);

        return $this->request($operation);
    }

    public function addReservation(\Ext_Thebing_Accommodation_Allocation $allocation) {

        if(!$allocation->isReservation()) {
            throw new \InvalidArgumentException(sprintf('Please only send reserverations with "%s"', __METHOD__));
        }

        $reservationData = $allocation->getReservationData();

        $customer = new \Ext_TS_Inquiry_Contact_Traveller();
        $customer->firstname = "Fidelo";
        $customer->lastname = (isset($reservationData['comment'])) ? $reservationData['comment'] : "Fidelo";

        $customer = new Operations\AddCustomer($customer);
        $response = $this->request($customer);

        // Neue Reservierung in Mews anlegen
        $add = new AddReservation($allocation, $response->get('Id'));
        $this->request($add);

		try {
			// In den richtigen Raum verschieben, das geht leider nur über einen separaten Request
			$updateSpace = new UpdateReservationSpace($allocation, $allocation->getRoom());
			$this->request($updateSpace);
		} catch(MissingIdentifierException | FailedException $e) {
			// Reservierung im Fehlerfall löschen damit beide Seiten gleich sind
			$cancel = new Api\Operations\CancelReservation($allocation);
			$this->request($cancel);
			// Exception trotzdem schmeißen damit ein Fehler angezeigt wird
			throw new FailedException($e->getMessage());
		}

    }

    /**
     * Fügt eine Reservierung hinzu. Hier wird automatisch geschaut ob es bereits eine andere Reservierung zu dem Raum gibt
     * und wenn ja dann wird die neue Reservierung als Companion eingetragen
     *
     * @param \Ext_Thebing_Accommodation_Allocation $allocation
     * @throws \Exception
     */
    public function addAllocation(\Ext_Thebing_Accommodation_Allocation $allocation) {

        $inquiry = $allocation->getInquiry();

        // Kontakte syncen ---------------------------------------------------------------------------------------------

        $traveller = $inquiry->getFirstTraveller();

        $this->syncCustomer($traveller);

        // Reservierung ------------------------------------------------------------------------------------------------

        \Ext_Thebing_Allocation::resetStaticCache();

        $sharings = $this->getSharingAllocations($allocation);

        if(!empty($sharings)) {
            // Es gibt bereits Reservierungen für diesen Raum - neue Reservierung als Companion eintragen

            $from = \DateTime::createFromFormat('Y-m-d H:i:s', $allocation->from);
            $sharings[] = $allocation;

            $adultCount = $childCount = 0;
            foreach($sharings as $sharing) {
                $customer = $sharing->getCustomer();
                if($customer->getAge($from) >= $sharing->getSchool()->adult_age) {
                    ++$adultCount;
                } else {
                    ++$childCount;
                }
            }

            // Update adultCount/childCount damit neue Reservierung hinzugefügt werden kann
            $update = new Api\Operations\UpdateReservation($sharings[0], [
                'AdultCount' => ['Value' => $adultCount],
                'ChildCount' => ['Value' => $childCount],
            ]);
            $this->request($update);

            // zu bestehender Reservierung hinzufügen
            $companion = new Operations\AddCompanion($sharings[0], $allocation);
            $this->request($companion);

        } else {

            // Neue Reservierung in Mews anlegen
            $add = new AddReservation($allocation);
            $this->request($add);

            try {
                // In den richtigen Raum verschieben, das geht leider nur über einen separaten Request
                $updateSpace = new UpdateReservationSpace($allocation, $allocation->getRoom());
                $this->request($updateSpace);
            } catch(MissingIdentifierException | FailedException $e) {
                // Reservierung im Fehlerfall löschen damit beide Seiten gleich sind
                $cancel = new Api\Operations\CancelReservation($allocation);
                $this->request($cancel);
                // Exception trotzdem schmeißen damit ein Fehler angezeigt wird
                throw new FailedException($e->getMessage());
            }

        }

    }

	/**
	 * Aktualisiert die wesentlichen Daten einer Mews-Reservierung
	 *
	 * @param \Ext_Thebing_Accommodation_Allocation $allocation
	 */
	public function updateReservation(\Ext_Thebing_Accommodation_Allocation $allocation) {

		$spaceId = ExternalApp::getRoomId($allocation->getRoom());

		$update = ['AssignedResourceId' => ['Value' => $spaceId]];

		if (!$allocation->getInquiry()->isCheckedIn()) {
			$from = Synchronization::getAllocationStartDate($allocation);
			$until = Synchronization::getAllocationEndDate($allocation);

			$update['StartUtc'] = ['Value' => $from->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ISO8601)];
			$update['EndUtc'] = ['Value' => $until->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ISO8601)];
		}

		$operation = new UpdateReservation($allocation, $update);

		$this->request($operation);
	}

    /**
     * Löscht eine Reservierung aus Mews. Falls es noch weitere Reservierungen in dem Raum gibt wird der Customer nur als
     * Companion entfernt und die Reservierung bleibt bestehen
     *
     * @param \Ext_Thebing_Accommodation_Allocation $allocation
     * @throws \Exception
     */
    public function deleteReservation(\Ext_Thebing_Accommodation_Allocation $allocation) {

        $sharings = $this->getSharingAllocations($allocation);

        $from = \DateTime::createFromFormat('Y-m-d H:i:s', $allocation->from);

        // adultCount/child count neu berechnen
        $adultCount = $childCount = 0;
        foreach($sharings as $sharing) {
            if($sharing->getId() !== $allocation->getId()) {
                $customer = $sharing->getCustomer();
                if($customer->getAge($from) >= $sharing->getSchool()->adult_age) {
                    ++$adultCount;
                } else {
                    ++$childCount;
                }
            }
        }

        if(array_sum([$adultCount, $childCount]) === 0) {
            // Es gibt keine weiteren Reservierungen in dem Raum - komplett löschen
            $operation = new CancelReservation($allocation);
            $this->request($operation);
        } else {
            // Es existieren noch andere Reservierungen in dem Raum - nur den Companion löschen
			$deleteOperation = new DeleteCompanion($allocation, $allocation->getCustomer());
            $this->request($deleteOperation);
            // adultCount/childCount aktualisieren
            $operation = new UpdateReservation($allocation, [
                'AdultCount' => ['Value' => $adultCount],
                'ChildCount' => ['Value' => $childCount],
            ]);
            $this->request($operation);

			$allocation->unsetMeta('mews_id');
        }

    }

    /**
     * Es werden nur POST-Requests mit Content-Type "application/json" akzeptiert
     * https://mews-systems.gitbook.io/connector-api/guidelines

     * @param Operation $operation
     * @param bool $logging
     * @return Collection
     */
    public function request(Operation $operation, bool $logging = false): Collection {

        $url = $this->url.Str::start($operation->getUri(), '/');

        $request = new Request('POST', $url, [
            'Content-Type' => 'application/json'
        ]);

        // Default Daten setzen
        $request->set('ClientToken', $this->clientToken)
            ->set('AccessToken', $this->accessToken)
			//->set('Client', 'Sample Client 1.0.0')
		;

        $request = $operation->manipulateRequest($request);

		if($logging) {
			self::getLogger()->info('Request operation', ['operation' => get_class($operation), 'request' => $request->toArray()]);
		}

        $curl = curl_init($request->getUrl());

		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request->toArray()));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $request->getCurlHeaders());
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_TIMEOUT, 120);

        $response = json_decode(curl_exec($curl), true);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($status !== 200) {
            switch($status) {
                case 401:
					self::getLogger()->error('Authentification failed', ['url' => $request->getUrl(), 'request' => $request->toArray(), 'response' => $response]);
                    throw new AuthenticateException($response['Message']);
				case 429:
					self::getLogger()->error('Too many requests', []);
					throw new FailedException('Too many requests');
                default:
                    self::getLogger()->error('Request failed', ['status' => $status, 'url' => $request->getUrl(), 'request' => $request->toArray(), 'response' => $response]);
                    throw new FailedException($response['Message']);
            }
        }

        $response = collect($response);

        if(method_exists($operation, 'handleResponse')) {
            $operation->handleResponse($response);
        }

		if($logging) {
			self::getLogger()->info('Response', ['data' => $response->toArray()]);
		}

        return $response;
    }

    private function getSharingAllocations(\Ext_Thebing_Accommodation_Allocation $allocation) {

        $sharing = collect($allocation->getRoomSharingAllocations())
            ->filter(function($sharing) use ($allocation) {
                return $sharing->getRoom()->getId() === $allocation->getRoom()->getId();
            })
            ->toArray();

        return $sharing;
    }

    public static function getLogger() {
		return \Log::getLogger(self::LOGGER_KEY);
	}

    public static function default() {
		return new self(
			\System::d(ExternalApp::CONFIG_URL),
			\System::d(ExternalApp::CONFIG_CLIENT_TOKEN),
			\System::d(ExternalApp::CONFIG_ACCESS_TOKEN)
		);
	}
}
