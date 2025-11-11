<?php

namespace Communication\Interfaces\Model;

use Illuminate\Support\Collection;

interface CommunicationContact
{
	public function getCommunicationName(string $channel): string;

	public function getCommunicationRoutes(string $channel): ?Collection;

	public function getCorrespondenceLanguages(): array;
}