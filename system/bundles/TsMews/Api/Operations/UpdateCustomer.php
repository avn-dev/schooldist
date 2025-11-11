<?php

namespace TsMews\Api\Operations;

use TsMews\Api;
use TsMews\Api\Request;
use TsMews\Entity\Customer;
use TsMews\Exceptions\FailedException;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Interfaces\Operation;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/customers#update-customer
 * @package TsMews\Api\Operations
 */
class UpdateCustomer implements Operation {

    /**
     * @var \Ext_TS_Inquiry_Contact_Traveller
     */
    protected $customer;

    public function __construct(\Ext_TS_Inquiry_Contact_Traveller $customer) {
    	$this->customer = $customer;
    }

    public function getUri(): string {
        return '/customers/update';
    }

    public function manipulateRequest(Request $request): Request {

		$mewsId = $this->customer->getMeta('mews_id');

        if($mewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for customer "%s"', $this->customer->getName()));
        }

        $request->set('CustomerId', $mewsId);

        return $this->setDefaultValues($request);
    }

    protected function setDefaultValues(Request $request): Request {

        //$email = $this->customer->getFirstEmailAddress();
        $phone = $this->customer->getFirstPhoneNumber();

        $request->set('FirstName', $this->customer->firstname);
        $request->set('LastName', $this->customer->lastname);

        if($this->customer->gender == 1) {
            $request->set('Title', 'Mister');
        } else if($this->customer->gender == 2) {
            $request->set('Title', 'Miss');
        }

        if (!empty($this->customer->nationality)) {
            $request->set('NationalityCode', strtoupper($this->customer->nationality));
        }
        if (!empty($this->customer->birthday) && $this->customer->birthday !== '0000-00-00') {
            $request->set('BirthDate', $this->customer->birthday);
        }
        /*if(!empty($email->email)) {
            $request->set('Email', $email->email);
        }*/
        if (!empty($phone)) {
            $request->set('Phone', $phone);
        }

        return $request;
    }

}
