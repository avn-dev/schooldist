<?php

namespace Tc\Service\Wizard;

use Form\Service\Frontend\Conditions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tc\Controller\WizardController;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Block;
use Tc\Service\Wizard\Structure\Separator;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\HasLevels;

class Structure
{
	use HasLevels;

	const BLOCK = 'block';

	const STEP = 'step';

	const SEPARATOR = 'separator';

	/**
	 * Liefert den nächsten Step anhand des vorausgegangenen Steps
	 *
	 * @param AbstractElement $after
	 * @return Step|null
	 */
	public function getNextStep(AbstractElement $after): ?Step
	{
		$nextStep = null;
		if (null !== $parent = $after->getParent()) {
			// Zuerst in dem Eltern-Block des Elementes schauen, ob dieser noch einen weiteren Step hat
			$nextStep = $parent->getNextStep($after);
		}

		if ($nextStep === null) {
			$root = $after->getRootBlock();
			// Ansonsten den nächsten Block/Step nehmen
			$nextElement = $this->getNextElement($root);

			if ($nextElement instanceof Block) {
				$nextStep = $nextElement->getFirstStep();
			} else if ($nextElement instanceof Step) {
				$nextStep = $nextElement;
			}
		}

		return $nextStep;
	}

	/**
	 * Structure-Objekt anhand eines Arrays generieren
	 *
	 * @param Wizard $wizard
	 * @param array $config
	 * @return Structure
	 */
	public static function fromArray(Wizard $wizard, array $config)
	{
		$structure = new self;

		foreach ($config as $elementKey => $elementConfig) {

			if (
				!empty($elementConfig['right']) &&
				!$wizard->getAccess()->hasRight($elementConfig['right'])
			) {
				continue;
			}

			if ($elementConfig['type'] === self::BLOCK) {
				$structure->block($elementKey, Block::fromArray($wizard, $elementConfig, $elementKey));
			} else if ($elementConfig['type'] === self::STEP) {
				$structure->step($elementKey, Step::fromArray($wizard, $elementConfig, $elementKey));
			} else if ($elementConfig['type'] === self::SEPARATOR) {
				$structure->separator($elementKey, Separator::fromArray($wizard, $elementConfig, $elementKey));
			}
		}

		return $structure;
	}

	/**
	 * Alle Bedingungen für ein Element durchlaufen
	 *
	 * @param AbstractElement $element
	 * @return void
	 */
	public static function runConditions(Wizard $wizard, AbstractElement $element): void
	{
		$conditions = $element->getConfig('conditions', []);

		foreach ($conditions as $condition) {

			if (!is_array($condition)) {
				$parameters = [];
				if (str_contains($condition, ':')) {
					$parameters = explode(', ', Str::after($condition, ':'));
					$condition = Str::before($condition,':');
				}

				$condition = Str::parseCallback($condition, '__invoke');
			} else {
				$parameters = $condition[2] ?? [];
			}

			$condition[0] = app()->make($condition[0]);
			$condition($wizard, $element, $parameters);
		}
	}

	/**
	 * Prüfen, ob in invalider Key benutzt wird. In der ersten Ebene dürfen keine Keys benutzt werden die auch als
	 * Controller-Action existieren
	 *
	 * @param string $key
	 * @param AbstractElement $element
	 * @return void
	 */
	protected function checkForbiddenElementKey(string $key, AbstractElement $element): void
	{
		if (
			$element instanceof Block ||
			$element->getParent() !== null
		) {
			// Alle Keys erlaubt
			return;
		}

		// In der ersten Ebene dürfen für Steps und Separators keine Keys benutzt werden die auch als Controller Methode
		// existieren, da das Routing so angegeben ist das sich z.b. /wizard/start und /wizard/{stepKey} in derselben Ebene
		// befinden und $stepKey = 'start' ein Problem ergeben würde
		$controllerMethods = (new \ReflectionClass(WizardController::class))
			->getMethods(\ReflectionMethod::IS_PUBLIC);

		$forbiddenKeys = array_map(fn ($method) => $method->name, $controllerMethods);

		if (in_array($key, $forbiddenKeys)) {
			throw new \LogicException('Please use an other key for element, key ['.$key.'] is already defined as controller action and conflicts with routing');
		}
	}
}