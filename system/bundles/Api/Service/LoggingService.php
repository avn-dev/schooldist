<?php

namespace Api\Service;

class LoggingService {

	public static function getLogger($channel = 'Log') {
		return \Log::getLogger('api', $channel);
	}

}
