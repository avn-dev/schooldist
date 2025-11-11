<?php

namespace Tc\Traits\Wizard;

use Illuminate\Http\Request;
use Tc\Controller\WizardController;
use Tc\Interfaces\Wizard\Log;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Block;
use Tc\Service\Wizard\Structure\Separator;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TsWizard\Handler\Setup\Steps\Season\BlockSeasons;

trait HasLevels
{
	/**
	 * @var AbstractElement[]
	 */
	protected array $elements = [];

	/**
	 * Block hinzufügen
	 *
	 * @param string $key
	 * @param Block $block
	 * @return $this
	 */
	public function block(string $key, Block $block): static
	{
		return $this->addElement($key, $block);
	}

	/**
	 * Step hinzufügen
	 *
	 * @param string $key
	 * @param Step $step
	 * @return $this
	 */
	public function step(string $key, Step $step): static
	{
		return $this->addElement($key, $step);
	}

	/**
	 * Separator hinzufügen
	 *
	 * @param string $key
	 * @param Separator $separator
	 * @return $this
	 */
	public function separator(string $key, Separator $separator): static
	{
		return $this->addElement($key, $separator);
	}

	/**
	 * @param string $key
	 * @param AbstractElement $element
	 * @return $this
	 */
	private function addElement(string $key, AbstractElement $element): static
	{
		if ($this instanceof Block) {
			$element->parent($this);
		}

		// Prüfen ob der Key möglich ist
		$this->checkForbiddenElementKey($key, $element);

		$this->elements[$key] = $element;
		return $this;
	}

	/**
	 * Liefert ein Element anhand eines Keys (Kindobjekte werden mit durchsucht)
	 *
	 * @param string $key
	 * @return AbstractElement|null
	 */
	public function get(string $key): ?AbstractElement
	{
		if (isset($this->elements[$key])) {
			return $this->elements[$key];
		}

		// Key auf die einzelnen Ebenen aufsplitten um rekursiv danach zu suchen
		$levels = explode(AbstractElement::KEY_SEPARATOR, $key);

		$element = $this->get(array_shift($levels));
		foreach ($levels as $levelKey) {
			$element = $element->get($levelKey);
			if ($element === null) {
				break;
			}
		}

		return $element;
	}

	/**
	 * Liefert das nächste Element nach einem anderen Element
	 *
	 * @param AbstractElement $after
	 * @return AbstractElement|null
	 */
	public function getNextElement(AbstractElement $after): ?AbstractElement
	{
		$key = $after->getElementKey();

		if (!isset($this->elements[$key])) {
			throw new \RuntimeException('Unknown element key ['.$key.']');
		}

		$elements = array_filter($this->elements, fn (AbstractElement $element) => $element->isVisitable(true));

		$keys = array_keys($elements);
		$index = array_search($key, $keys);

		if (isset($keys[$index + 1])) {
			return $this->get($keys[$index + 1]);
		}

		// Es gibt kein weiteres Element
		return null;
	}

	/**
	 * Liefert den ersten Step innerhalb dieses Elementes
	 *
	 * @return Step|null
	 */
	public function getFirstStep(): ?Step
	{
		//$steps = Arr::flatten($this->getSteps());
		//return Arr::first($steps);

		// Über getFirstStep() gehen um evtl. dortige Logik zu beachten
		foreach ($this->elements as $element) {
			if ($element instanceof Step) {
				return $element;
			} else if ($element instanceof Block) {
				return $element->getFirstStep();
			}
		}

		return null;
	}

	/**
	 * Liefert einen Step anhand seines Keys
	 *
	 * @param string $key
	 * @return Step|null
	 */
	public function getStep(string $key): ?Step
	{
		return Arr::get($this->getSteps(), $key);
	}

	/**
	 * Liefert alle Steps innerhalb dieses Elementes
	 *
	 * @return array
	 */
	public function getSteps(): array
	{
		$steps = [];
		foreach ($this->elements as $key => $element) {
			if ($element instanceof Step) {
				$steps[$key] = $element;
			} else if ($element instanceof Block) {
				$steps[$key] = $element->getSteps();
			}
		}

		return $steps;
	}

	/**
	 * Generiert ein Array mit allen Informationen über die Struktur dieses Objektes
	 *
	 * @param Wizard $wizard
	 * @param Step|null $currentStep
	 * @param array $finishedLogs
	 * @return array
	 */
	public function toSitemapArray(Wizard $wizard, Step $currentStep = null, array $finishedLogs = []): array
	{
		$sitemap = [];

		foreach ($this->elements as $key => $element) {

			if ($element->isHidden()) {
				continue;
			}

			$node = null;

			$log = Arr::first($finishedLogs, fn (Log $log) => $log->getStepKey() === $element->getKey());

			if ($element instanceof Block) {
				$active = false;
				if ($currentStep) {
					$active = str_starts_with($currentStep->getKey(), $element->getKey().AbstractElement::KEY_SEPARATOR);
				}

				$icon = $element->getConfig('icon', ($active) ? 'fa fa-folder-open' :  'fa fa-folder');

				$step = $element->getFirstStep();

				$title = $element->getSitemapTitle($wizard);

				if (empty($title)) {
					// Wenn es keinen Titel für die Ebene gibt, alles eine Ebene nach vorne verschieben (sieht in der
					// Darstellung einfach besser aus)
					$nodes = $element->toSitemapArray($wizard, $currentStep, $finishedLogs);

					if ($sitemap[(count($sitemap) - 1)]['type'] == Structure::BLOCK) {
						$currentLevelKey = $element->getKey();
						$lastLevelKey = $sitemap[(count($sitemap) - 1)]['key'];
					} else {
						$currentLevelKey = Str::beforeLast($element->getKey(), AbstractElement::KEY_SEPARATOR);
						$lastLevelKey = Str::beforeLast($sitemap[(count($sitemap) - 1)]['key'], AbstractElement::KEY_SEPARATOR);
					}

					if ($lastLevelKey === $currentLevelKey) {
						if (Arr::first($nodes, fn ($node) => $node['active'])) {
							$sitemap[(count($sitemap) - 1)]['active'] = true;
						}

						foreach ($nodes as $subNode) {
							$sitemap[(count($sitemap) - 1)]['elements'][] = $subNode;
						}
					} else {
						foreach ($nodes as $subNode) {
							$sitemap[] = $subNode;
						}
					}

				} else {
					$node = [
						'key' => $element->getKey(),
						'type' => Structure::BLOCK,
						'title' => $title,
						'step' => $step,
						'icon' => $icon,
						'color' => $element->getConfig('color', '#65c7d8'),
						'active' => $active,
						'disabled' => $element->isDisabled(),
						'checked' => $log !== null,
						'process_status' => $element->getIterationStatus($finishedLogs),
						'elements' => $element->toSitemapArray($wizard, $currentStep, $finishedLogs)
					];
				}

			} else if ($element instanceof Step) {

				$active = false;
				if ($currentStep) {
					$active = $currentStep->getKey() === $element->getKey();
				}

				$icon = $element->getConfig('icon', 'far fa-file');

				$node = [
					'key' => $element->getKey(),
					'type' => Structure::STEP,
					'title' => $element->getSitemapTitle($wizard),
					'step' => $element,
					'icon' => $icon,
					'color' => $element->getConfig('color', '#65c7d8'),
					'active' => $active,
					'disabled' => $element->isDisabled(),
					'checked' => $log !== null,
					'process_status' => $element->getIterationStatus($finishedLogs),
				];
			} else if ($element instanceof Separator) {
				$node = [
					'key' => $element->getKey(),
					'type' => Structure::SEPARATOR
				];
			}

			if ($node) {
				$sitemap[] = $node;
			}
		}

		return $sitemap;
	}

	/**
	 * Prüfen, ob in invalider Key benutzt wird
	 *
	 * @param string $key
	 * @param AbstractElement $element
	 * @return void
	 */
	protected function checkForbiddenElementKey(string $key, AbstractElement $element): void {}

}