<?php

namespace Tc\Service\Wizard;

use User;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class Iteration
{
	private ?Step $currentStep = null;

	public function __construct(
		private int $iteration,
		private ?User $user = null
	) {}

	/**
	 * Aktuellen Step setzen
	 *
	 * @param Step $step
	 * @return $this
	 */
	public function currentStep(Step $step): static
	{
		$this->currentStep = $step;
		return $this;
	}

	/**
	 * Liefert die Nummer der Iteration
	 *
	 * @return int
	 */
	public function getIterationCount(): int
	{
		return $this->iteration;
	}

	/**
	 * Liefert den aktuellen Benutzer - falls vorhanden
	 * @return User|null
	 */
	public function getUser(): ?\User
	{
		return $this->user;
	}

	/**
	 * Liefert den aktuellen Step
	 *
	 * @return Step
	 */
	public function getCurrentStep(): Step
	{
		return $this->currentStep;
	}

	/**
	 * Alle Informationen in ein Array schreiben
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$user = null;
		if ($this->user) {
			$user = [
				'class' => get_class($this->user),
				'id' => $this->user->id,
			];
		}

		return [
			'iteration' => $this->iteration,
			'current_step' => $this->currentStep?->getKey(),
			'user' => $user
		];
	}

	/**
	 * Generiert ein Iterations-Objekt anhand eines Array (siehe ->toArray())
	 *
	 * @param Wizard $wizard
	 * @param array $array
	 * @return static
	 */
	public static function fromArray(Wizard $wizard, array $array): static
	{
		$user = null;
		if (is_array($array['user'])) {
			$user = \Factory::getInstance($array['user']['class'], $array['user']['id']);
		}

		$iteration = new self((int)$array['iteration'], $user);
		if (!empty($array['current_step'])) {
			$step = $wizard->getStructure()->getStep($array['current_step']);
			$iteration->currentStep($step);
		}

		return $iteration;
	}
}