<?php

namespace Tc\Service\Wizard\Structure;

use Illuminate\Support\Arr;
use Tc\Interfaces\Wizard\Log;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure;
use Tc\Traits\Wizard\HasLevels;

class Block extends AbstractElement
{
	use HasLevels;

	private static array $queryCache = [];

	/**
	 * Liefert den Titel des Blocks (optional mit allen Titeln der Parent-Objekte)
	 *
	 * @param Wizard $wizard
	 * @return string
	 */
	public function getTitle(Wizard $wizard): string
	{
		$title = parent::getTitle($wizard);

		// Informationen zum Schleifendurchlauf anhängen
		if (null !== $loop = $this->getLoop()) {
			$index = $loop->getIndex($this->getQueryParameter($loop->getKey())) + 1;
			$title .= sprintf(' <span class="label label-default"><i class="fa fa-retweet"></i> %d/%d</span>', $index, $loop->count());
		}

		return $title;
	}

	/**
	 * Liefert den nächsten Step anhand des vorausgegangenen Steps
	 *
	 * @param AbstractElement $after
	 * @return Step|null
	 */
	public function getNextStep(AbstractElement $after): ?Step
	{
		$element = $this->getNextElement($after);

		if ($element === null && null !== $loop = $this->getLoop()) {
			// Wenn es kein weiteres Element in diesem Block gibt schauen, ob es einen Loop gibt und wenn es noch einen
			// Schleifendurchlauf gibt wieder auf den Anfang springen
			$currentLoopValue = $after->getQueryParameter($loop->getKey());
			$nextLoopValue = $loop->getNextValue($currentLoopValue);
			if ($nextLoopValue) {
				$this->query($loop->getKey(), $nextLoopValue);
				return $this->getFirstStep()->query($loop->getKey(), $nextLoopValue);
			}
		}

		if ($element instanceof Block) {
			$element->setQueryParameters($after->getQueryParameters());
			return $element->getFirstStep();
		} else if ($element instanceof Step) {
			$element->setQueryParameters($after->getQueryParameters());
			return $element;
		} else if ($this->parent) {
			return $this->parent->getNextStep($this);
		}

		return null;
	}

	/**
	 * Liefert den Query-Parameter des Blocks (standardmäßig gibt es keine)
	 *
	 * @return QueryParam[]
	 */
	public function getQueries(): array
	{
		return [];
	}

	/**
	 * @return QueryParam[]
	 */
	public function getQueriesFromCache(): array
	{
		if (!isset(self::$queryCache[get_called_class()])) {
			self::$queryCache[get_called_class()] = $this->getQueries();
		}
		return self::$queryCache[get_called_class()];
	}

	/**
	 * Liefert den Query-Parameter des Blocks anhand des Keys - falls vorhanden
	 *
	 * @param $key
	 * @return QueryParam|null
	 */
	final public function getQuery($key): ?QueryParam
	{
		return Arr::first($this->getQueriesFromCache(), fn (QueryParam $query) => $query->getKey() === $key);
	}

	/**
	 * Liefert den Loop-Parameter des Blocks (standardmäßig gibt es keinen)
	 *
	 * @return QueryParam[]
	 */
	final public function getLoop(): ?QueryParam
	{
		return Arr::first($this->getQueriesFromCache(), fn (QueryParam $query) => $query->isLoop());
	}

	/**
	 * Bindet einen Loop-Parameter an den Block
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $validate
	 * @return $this|Block
	 */
	public function query(string $key, mixed $value, bool $validate = true)
	{
		// Query-Parameter validieren
		if ($validate && null !== $query = $this->getQuery($key)) {
			if (!$query->has($value)) {
				throw new \RuntimeException('Invalid query parameter ['.$value.']');
			}
		}

		$this->queryParameters[$key] = $value;

		// Den Loop-Parameter für alle Unterelemente des Blockes setzen damit diese alle auf demselben Stand sind
		foreach ($this->elements as $element) {
			if ($element instanceof AbstractElement) {
				$element->query($key, $value, $validate);
			}
		}

		return $this;
	}

	/**
	 * Prozessstatus ermitteln [erledigt, Anzahl aller Steps]
	 *
	 * @param array $finishedLogs
	 * @param array $queryParameters
	 * @return int[]
	 */
	public function getIterationStatus(array $finishedLogs, array $queryParameters = []): array
	{
		$loop = $this->getLoop();

		$elements = array_filter($this->elements, fn ($element) => $element->isVisitable());

		// [erledigt, Anzahl aller Steps]
		$status = [0, 0];

		if ($loop) {
			$loopParameters = $loop->getValues();
			foreach ($loopParameters as $loopParameter) {
				$queryParameters[$loop->getKey()] = $loopParameter;
				foreach ($elements as $element) {
					[$blockDone, $blockAll] = $element->getIterationStatus($finishedLogs, $queryParameters);
					$status[0] += $blockDone;
					$status[1] += $blockAll;
				}
			}
		} else {
			foreach ($elements as $element) {
				[$blockDone, $blockAll] = $element->getIterationStatus($finishedLogs, $queryParameters);
				$status[0] += $blockDone;
				$status[1] += $blockAll;
			}
		}

		return $status;
	}

	/**
	 * Generiert ein Block-Object anhand eines Arrays
	 *
	 * @param array $config
	 * @param string $key
	 * @param array $queryParameters
	 * @return Block
	 */
	public static function fromArray(Wizard $wizard, array $config, string $key, array $queryParameters = []): Block
	{
		if (empty($config['elements'])) {
			throw new \LogicException('No child elements defined for block ['.$key.']');
		}

		$class = $config['class'] ?? self::class;

		/* @var Block $block */
		$block = app()->make($class)
			->key($key)
			->config(Arr::except($config, ['class','elements']));

		$queries = array_merge($queryParameters, $config['queries'] ?? []);

		foreach ($queries as $query => $value) {
			$block->query($query, $value);
		}

		foreach ($config['elements'] as $elementKey => $elementConfig) {

			if (
				!empty($elementConfig['right']) &&
				!\Access_Backend::getInstance()->hasRight($elementConfig['right'])
			) {
				continue;
			}

			if ($elementConfig['type'] === Structure::BLOCK) {
				$block->block($elementKey, self::fromArray($wizard, $elementConfig, $key.AbstractElement::KEY_SEPARATOR.$elementKey, $block->getQueryParameters()));
			} else if ($elementConfig['type'] === Structure::STEP) {
				$block->step($elementKey, Step::fromArray($wizard, $elementConfig, $key.AbstractElement::KEY_SEPARATOR.$elementKey, $block->getQueryParameters()));
			}
		}

		Structure::runConditions($wizard, $block);

		return $block;
	}

}