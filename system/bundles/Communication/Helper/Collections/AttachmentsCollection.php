<?php

namespace Communication\Helper\Collections;

use Communication\Dto\Message\Attachment;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AttachmentsCollection extends Collection
{
	public function getGroups(): Collection
	{
		return (new Collection($this->items))
				->map(fn (Attachment $attachment) => $attachment->getGroups())
				->flatten()
				->unique()
				->values();
	}

	/*public function getRecipientKeys(): Collection
	{
		return (new Collection($this->items))
			->map(fn (Recipient $address) => $address->getRecipientKeys())
			->flatten()
			->unique()
			->values();
	}*/

	public function getByGroup(string|array $group): static
	{
		return (new static($this->items))
			->filter(fn (Attachment $attachment) => !empty(array_intersect($attachment->getGroups(), Arr::wrap($group))));
	}

}