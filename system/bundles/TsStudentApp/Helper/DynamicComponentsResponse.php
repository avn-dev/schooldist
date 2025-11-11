<?php

namespace TsStudentApp\Helper;

use TsStudentApp\Components\Component;
use Illuminate\Http\JsonResponse;

class DynamicComponentsResponse
{
	private array $components = [];

	public function add(Component $component) {
		$this->components[] = [
			'name' => $component->getKey(),
			'data' => $component->toArray(),
		];
		return $this;
	}

	public function toArray() {
		return $this->components;
	}

	public function toResponse(): JsonResponse
	{
		return response()
			->json(['components' => $this->toArray()]);
	}

}