<?php

namespace TsStudentApp\Components;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TsStudentApp\Http\Resources\PropertyResource;
use TsStudentApp\Messenger\Message;
use TsStudentApp\Properties\Property;

class MessagesList implements Component
{
	private ?Property $property = null;

	private ?Collection $messages = null;

	public function __construct(private readonly Request $request) {}

	public function getKey(): string
	{
		return 'messages-list';
	}

	public function property(Property $property): static
	{
		$this->property = $property;
		return $this;
	}

	public function messages(Collection $messages): static
	{
		$this->messages = $messages;
		return $this;
	}

	public function toArray(): array
	{
		$array = [];
		$array['property'] = null;
		$array['messages'] = [];

		if ($this->property !== null) {
			$array['property'] = (new PropertyResource($this->property))->toArray($this->request);
		}

		if ($this->messages !== null) {
			$array['messages'] = $this->messages->map(fn (Message $message) => $message->toArray());
		}

		return $array;
	}

}