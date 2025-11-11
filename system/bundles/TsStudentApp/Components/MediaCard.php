<?php

namespace TsStudentApp\Components;

use Illuminate\Support\Arr;
use TsStudentApp\AppInterface;
use TsStudentApp\Service\Util;

class MediaCard extends Card
{
	private string $image = '';

	public function image(string $type, string $id): static
	{
		$this->image = Util::imageUrl($type, $id);
		return $this;
	}

	public function toArray(): array
	{
		$data = parent::toArray();
		if (!empty($this->image)) {
			$data = Arr::prepend($data, $this->image, 'image');
		}
		return $data;
	}
}