<?php

namespace Tc\Handler\SequentialProcessing;

use Core\Handler\SequentialProcessing\TypeHandler;

class EventManager extends TypeHandler
{
	/**
	 * @param EventDto $object
	 * @return void
	 */
	public function execute($object)
	{
		\Tc\Facades\EventManager::handle($object->getEventName(), $object->getPayload(), true);
	}

	public function check($object)
	{
		return $object instanceof EventDto;
	}
}