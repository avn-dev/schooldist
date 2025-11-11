<?php

namespace TsMews\Api\Operations;

use TsMews\Api\Request;
use TsMews\Exceptions\FailedException;
use TsMews\Interfaces\Operation;
use Illuminate\Support\Collection;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/customers#add-customer
 */
class AddCustomer extends UpdateCustomer implements Operation {

    public function getUri(): string {
        return '/customers/add';
    }

    public function manipulateRequest(Request $request): Request {

		$mewsId = $this->customer->getMeta('mews_id');

        if ($mewsId !== null) {
            throw new \LogicException('Customer already exists in mews, please use update operation.');
        }

        $request = $this->setDefaultValues($request);

        // Beim Hinzufügen kommt noch die Adresse hinzu

        $address = $this->customer->getFirstAddress();

        $addressData = [];
        if ($address->exist()) {
            if (!empty($address->address)) $addressData['Line1'] = $address->address;
            if (!empty($address->address_additional)) $addressData['Line2'] = $address->address_additional;
            if (!empty($address->city)) $addressData['City'] = $address->city;
            if (!empty($address->zip)) $addressData['PostalCode'] = $address->zip;
            if (!empty($address->country_iso)) $addressData['CountryCode'] = $address->country_iso;
        }

        if (!empty($addressData)) {
            $request->set('Address', $addressData);
        }

        return $request;
    }

    public function handleResponse(Collection $response) {
    	// Bei den Reservierungen wird ein Dummy-Kontakt erzeugt zu dem keine Mews-Id gesetzt werden soll (siehe \TsMews\Api::addReservation())
        if (!$this->customer->exist()) {
           return;
        }

		$this->customer->setMeta('mews_id', $response->get('Id'));
		$this->customer->save();
    }
}
