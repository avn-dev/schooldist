<?php

namespace Ts\Http\Resources\Admin;

use Admin\Facades\Admin;
use Admin\Http\Resources\UserResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Ext_TS_Inquiry
 */
class InquiryResource extends JsonResource
{
	public function toArray($request)
	{
		$date = new \Ext_Thebing_Gui2_Format_Date();

		//$inquiryAllocations = \Ext_Thebing_Allocation::getAllocationByInquiryId($this->id, 0, true, false);

		$salesPerson = $this->getSalesPerson();

		$inquiry = [
			'id' => $this->id,
			'number' => $this->getNumber(),
			'created' => $date->formatByValue($this->getBookingCreated()),
			'cancelled' => (!empty($cancellation = $this->getCancellationDate())) ? $date->formatByValue($cancellation) : null,
			'inbox' => $this->getInbox()->name,
			'school' => $this->getSchool()->ext_1,
			'service_from' => $date->formatByValue($this->getServiceFrom(true)),
			'service_until' => $date->formatByValue($this->getServiceUntil(true)),
			'sales_person' => ($salesPerson) ? new UserResource($salesPerson) : null,
			'state' => (new \Ext_Thebing_Gui2_Format_CustomerStatus())->formatByValue($this->status_id),
		];

		if ($this->hasAgency()) {
			$inquiry['agency'] = $this->getAgency()->ext_1;
		}

		if ($this->hasGroup()) {
			$inquiry['group'] = $this->getGroup()->name;
		}

		if (!empty($duePayments = $this->getDuePayments())) {
			$inquiry['due_payments'] = $duePayments;
		}

		return $inquiry;
	}

	private function getDuePayments()
	{
		$dueTerms = $this->getDueTerms()
			->reject(fn(\Ext_TS_Document_Version_PaymentTerm $term) => Carbon::make($term->date)->gt(Carbon::now()));

		if ($dueTerms->isNotEmpty()) {
			$dateFormat = new \Ext_Thebing_Gui2_Format_Date();
			return $dueTerms->map(fn (\Ext_TS_Document_Version_PaymentTerm $term) => [
				'amount' => \Ext_Thebing_Format::Number($term->amount, $this->getCurrency(), $this->getSchool()),
				'date' => $dateFormat->format($term->date),
			]);
		}

		return [];
	}

}