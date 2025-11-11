<?php

namespace Ts\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Ext_TS_Inquiry_Contact_Traveller
 */
class TravellerResource extends JsonResource
{
	public function toArray($request)
	{
		$date = new \Ext_Thebing_Gui2_Format_Date();

		return [
			'id' => $this->id,
			'name' => $this->getName(),
			'customer_number' => $this->getCustomerNumber(),
			'initials' => $this->getInitials(),
			'avatar' => !empty($photo = $this->getPhoto())
				? $photo.'?v='.md5(filemtime(base_path($photo)))
				: null,
			'email' => $this->getFirstEmailAddress()->email,
			'birthday' => $date->formatByValue($this->birthday),
			'nationality' => (new \Ext_Thebing_Gui2_Format_Nationality())->formatByValue($this->nationality),
			'age' => $this->getAge(),
		];
	}

	private function getInitials(): string
	{
		if (!empty($this->firstname) && !empty($this->lastname)) {
			$initials = substr($this->firstname, 0, 1).substr($this->lastname, 0, 1);
		} else if (!empty($this->firstname)) {
			$initials = substr($this->firstname, 0, 2);
		} else {
			$initials = substr($this->lastname, 0, 2);
		}

		return strtoupper($initials);
	}
}