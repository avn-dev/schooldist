<?php

namespace Communication\Dto\Message;

use Communication\Interfaces\Model\HasCommunication;

class Recipient extends \Core\Notifications\Recipient
{
	private ?HasCommunication $source = null;

	public function source(HasCommunication $source): static
	{
		$this->source = $source;
		return $this;
	}

	public function getSource(): ?HasCommunication
	{
		return $this->source;
	}

	public function toArray(): array
	{
		$array = parent::toArray();
		$array['source'] = $this->source ? sprintf('%s::%d', $this->source::class, $this->source->id) : null;
		return $array;
	}

	public static function fromArray(array $array): static
	{
		$recipient = parent::fromArray($array);

		if (!empty($array['source'])) {
			[$class, $id] = explode('::', $array['source']);
			$recipient->source(\Factory::getInstance($class, $id));
		}

		return $recipient;
	}
}