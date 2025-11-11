<?php

namespace Communication\Helper\Collections;

use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AddressContactsCollection extends Collection
{
	public function getGroups(): Collection
	{
		return (new Collection($this->items))
				->map(fn (AddressBookContact $address) => $address->getGroups())
				->flatten()
				->unique()
				->values();
	}

	public function getCorrespondingLanguages(): Collection
	{
		return (new Collection($this->items))
			->map(function (AddressBookContact|array $recipient) {
				if (is_array($recipient)) {
					return $recipient[0]->getCorrespondenceLanguages();
				}
				return $recipient->getCorrespondenceLanguages();
			})
			->flatten()
			->unique()
			->values();
	}

	public function getRecipientKeys(): Collection
	{
		return (new Collection($this->items))
			->map(function (AddressBookContact|array $recipient) {
				if (is_array($recipient)) {
					return $recipient[0]->getRecipientKeys();
				}
				return $recipient->getRecipientKeys();
			})
			->flatten()
			->unique()
			->values();
	}

	public function getByGroup(string|array $group): static
	{
		return (new static($this->items))
			->filter(fn (AddressBookContact $address) => !empty(array_intersect($address->getGroups(), Arr::wrap($group))));
	}

}