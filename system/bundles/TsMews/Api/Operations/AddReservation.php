<?php

namespace TsMews\Api\Operations;

use TsMews\Api;
use TsMews\Api\Request;
use TsMews\Entity\Allocation;
use TsMews\Entity\Customer;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Handler\ExternalApp;
use TsMews\Interfaces\Operation;
use Illuminate\Support\Collection;
use TsMews\Service\Synchronization;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#add-reservations
 */
class AddReservation implements Operation {

    const IDENTIFIER_PREFIX = 'FID';
    const IDENTIFIER_PREFIX_RESERVATION = 'FID-R';


    private $allocation;

    private $customerMewsId;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation, $customerMewsId = null) {
        $this->allocation = $allocation;
        $this->customerMewsId = $customerMewsId;
    }

    public function getUri(): string {
        return '/reservations/add';
    }

    public function manipulateRequest(Request $request): Request {

        $request->set('ServiceId', ExternalApp::getServiceId());
        $request->set('SendConfirmationEmail', false);
		$request->set('CheckOverbooking', true);
		$request->set('CheckRateApplicability', false);

        //if($inquiry->hasGroup()) {
        //    $group = $inquiry->getGroup();
        //    $request->set('GroupId', '9d1b1a90-07e9-46b0-b95a-abc80071c19e');
        //    $request->set('GroupName', 'FID01');
        //}

        $reservations = [];
        $reservations[] = $this->buildReservation();

        $request->set('Reservations', $reservations);

        return $request;
    }

    public function handleResponse(Collection $response) {
        $this->allocation->setMeta('mews_id', $response->get('Reservations')[0]['Reservation']['Id']);

		// Nicht über $this->allocation->save() gehen da sonst die Hooks erneut ausgeführt werden

		$metadata = $this->allocation->getJoinedObjectChilds('attributes', true);

		foreach ($metadata as $metaObject) {
			if (!$metaObject->exist()) {
				$metaObject->save();
			}
		}
    }

    private function buildReservation() {

        $room = $this->allocation->getRoom();
        $roomType = $room->getType();

        $roomTypeMewsId = ExternalApp::getRoomTypeId($roomType);

        if (empty($roomTypeMewsId)) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for room type "%s"', $roomType->getName()));
        }

        $from = Synchronization::getAllocationStartDate($this->allocation);
		$until = Synchronization::getAllocationEndDate($this->allocation);

        $reservation = [];

		// UTC -> siehe TsMews\Api\Request:.format()
        $reservation['StartUtc'] = $from;
        $reservation['EndUtc'] = $until;

        $adultCount = $childCount = 0;

        if ($this->allocation->isReservation()) {

            $customerMewsId = $this->customerMewsId;
            $reservationData = $this->allocation->getReservationData();

            $reservation['State'] = Api::STATE_OPTIONAL;
            $reservation['Identifier'] = self::IDENTIFIER_PREFIX_RESERVATION.$this->allocation->getId();
            $reservation['BookerId'] = $customerMewsId;

            if(
                isset($reservationData['age']) &&
                $reservationData['age'] === 'minor'
            ) {
                ++$childCount;
            } else {
                ++$adultCount;
            }

            $note = "";
            $globalNote = \System::d('mews_reservation_notes', "");

        } else {

            $customer = $this->allocation->getCustomer();
            $customerMewsId = $customer->getMeta('mews_id');

			$inquiry = $this->allocation->getInquiry();
			$school = $inquiry->getSchool();

            $reservation['State'] = Api::STATE_CONFIRMED;
            $reservation['Identifier'] = self::IDENTIFIER_PREFIX.$inquiry->getId();
			$reservation['BookerId'] = $customerMewsId;

            if ($customer->getAge($from) >= $school->adult_age) {
                ++$adultCount;
            } else {
                ++$childCount;
            }

            $note = $inquiry->getMatchingData()->acc_comment;
            $globalNote = \System::d('mews_reservation_allocation_notes', "");
        }

        if (empty($customerMewsId)) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for customer "%s"', $customer->getName()));
        }

        $reservation['CustomerId'] = $customerMewsId;
        $reservation['AdultCount'] = $adultCount;
        $reservation['ChildCount'] = $childCount;
        $reservation['RequestedCategoryId'] = ExternalApp::getRoomTypeId($roomType);
        $reservation['RateId'] = ExternalApp::getRateId();

        if (!empty($globalNote)) {
            $note = $globalNote.PHP_EOL.$note;
        }

        $reservation['Notes'] = $note;

        return $reservation;
    }
}
