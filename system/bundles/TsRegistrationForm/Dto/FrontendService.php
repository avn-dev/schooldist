<?php

namespace TsRegistrationForm\Dto;

use Illuminate\Contracts\Support\Arrayable;

/**
 * DTO wird mit allen Properties ans Frontend übermittelt
 */
abstract class FrontendService implements Arrayable
{
	protected array $hidden = [];

	public function toArray()
	{
		return array_diff_key(get_object_vars($this), array_flip([...$this->hidden, 'hidden']));
	}

	public function fromArray(array $data)
	{
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}

		return $this;
	}
}