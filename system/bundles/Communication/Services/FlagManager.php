<?php

namespace Communication\Services;

use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Collection;

class FlagManager
{
	public function __construct(
		private readonly Communication $communication
	) {}

	public function handleFlags(HasCommunication $model, Collection $usedFlags, \Ext_TC_Communication_Message $log, bool $finalOutput, array $confirmedErrors): array
	{
		$applicationFlags = $this->communication->getFlags();
		$errors = [];

		foreach ($applicationFlags as $class) {

			$flag = $this->communication->getFlag($class);

			$errors = [
				...$errors,
				...$flag->validate($usedFlags->contains($class), $this->communication->l10n(), $model, $log, $finalOutput, $confirmedErrors)
			];
		}

		return array_unique($errors);
	}

}