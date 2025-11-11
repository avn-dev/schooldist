<?php

namespace TsApi\Controller;

use TsApi\Exceptions\ApiError;
use Illuminate\Http\Request;
use Tc\Traits\Http\ErrorResponse;
use Illuminate\Validation\Rule;

class PaymentController extends AbstractController {
	
	public function store(Request $request) {

		try {

			\System::setInterfaceLanguage('en');
			
			// Vorabprüfung
			$validator = (new \Core\Factory\ValidatorFactory(\System::getInterfaceLanguage()))
				->make($request->all(), [
					'inquiry_id' => ['integer', 'required_without:school_id'],
					'school_id' => ['integer', 'required_without:inquiry_id'],
					'payment_date'       => ['required'],
					'payment_method_id'  => ['required'],
					'payment_amount'     => ['required'],
					'payment_comment'    => ['nullable'],
					// payment_currency nur erlaubt, wenn KEINE inquiry_id vorhanden ist
					'payment_currency'  => [
						'nullable',
						function ($attribute, $value, $fail) use ($request) {
							if ($request->filled('inquiry_id') && $value !== null && $value !== '') {
								$fail('The payment_currency field must not be present when inquiry_id is provided.');
							}
						},
					],
				]);

			if ($validator->fails()) {
				return response()
					->json(['errors' => $validator->messages()->all()], 422);
			}
			
			$validated = $validator->validated();
 
			$school = $inquiry = null;
			if(!empty($validated['inquiry_id'])) {
				$inquiry = \Ext_TS_Inquiry::getInstance($validated['inquiry_id']);
				$school = $inquiry->getSchool();
				if(!$inquiry->exist()) {
					return response()
						->json(['errors' => 'Booking not found'], 404);
				}
			}

			$schoolIds = array_keys(\Ext_Thebing_Client::getSchoolList(true));
			if(!empty($validated['school_id'])) {
				$school = \Ext_Thebing_School::getInstance($validated['school_id']);
				if(!$school->exist()) {
					return response()
							->json(['errors' => 'School not found'], 404);
				}
				$schoolIds = [$school->id];
			}

			if($inquiry !== null) {
				
				$inquirySchool = $inquiry->getSchool();
				
				if($school !== $inquirySchool) {
					return response()
						->json(['errors' => 'The specified booking does not belong to the given school.'], 400);
				}
				
			}
			
			$paymentMethods = array_keys(\Ext_Thebing_Admin_Payment::getPaymentMethods(true, $schoolIds));
			$schoolCurrencyIsos = array_column($school->getCurrencies(), 'iso4217');			

			$validator = (new \Core\Factory\ValidatorFactory(\System::getInterfaceLanguage()))
				->make($request->all(), [
					'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
					'payment_method_id' => ['required', 'integer', Rule::in($paymentMethods)],
					'payment_amount' => ['required', 'numeric', 'min:0.01'],
					'payment_comment' => ['nullable', 'string', 'max:1000'],
					'payment_firstname' => ['string'],
					'payment_lastname' => ['string'],
					'payment_transaction_code' => ['string'],
					'payment_currency' => [Rule::in($schoolCurrencyIsos)]
				]);

			if ($validator->fails()) {
				return response()
					->json(['errors' => $validator->messages()->all()], 422);
			}

			if($school === null) {
				$school = \Ext_Thebing_Client::getFirstSchool();
			}
			
			$validated = $validator->validated();

			if($inquiry !== null) {
				$currencyId = $inquiry->currency_id;
			} elseif(!empty($validated['payment_currency'])) {
				$currencyId = \Ext_Thebing_Currency::getCurrencyByIso($validated['payment_currency'])->id;
			} else {
				$currencyId = $school->getCurrency();
			}

			$unallocatedPayment = new \Ext_TS_Inquiry_Payment_Unallocated();
			$unallocatedPayment->comment = $validated['payment_comment'];
			$unallocatedPayment->amount = $validated['payment_amount'];
			$unallocatedPayment->amount_currency = (int)$currencyId;
			$unallocatedPayment->payment_date = $validated['payment_date'];
			$unallocatedPayment->additional_info = json_encode(['type' => 'api']);
			$unallocatedPayment->payment_method_id = $validated['payment_method_id'];
			$unallocatedPayment->firstname = $validated['payment_firstname']??'';
			$unallocatedPayment->lastname = $validated['payment_lastname']??'';
			$unallocatedPayment->transaction_code = $validated['payment_transaction_code']??'';

			if($inquiry !== null) {
				
				$payment = $unallocatedPayment->createInquiryPayment($inquiry, $validated['payment_method_id']);
				
				return $this->sendResponse(200, 'Payment successfully created', ['payment_id' => $payment->id]);
			} else {
				
				$unallocatedPayment->save();
				
				return $this->sendResponse(200, 'Unallocated payment successfully created', ['unallocated_payment_id' => $unallocatedPayment->id]);
			}

		} catch(\Throwable $e) {
			dd($e);
			return $this->handleException($request, $e);
		}
		
	}

}
