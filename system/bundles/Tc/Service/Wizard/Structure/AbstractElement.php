<?php

namespace Tc\Service\Wizard\Structure;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure;

abstract class AbstractElement
{
	const KEY_SEPARATOR = '.';

	protected string $key;

	protected array $config = [];

	protected ?string $title;

	protected ?Block $parent = null;

	protected array $queryParameters = [];

	protected bool $disabled = false;
	protected ?string $disable_reason = null;

	protected bool $hidden = false;

	public function key(string $key): static
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * @param Block $parent
	 * @return $this
	 */
	public function parent(Block $parent): static
	{
		$this->parent = $parent;

		if ($parent->isDisabled()) {
			$this->disable();
		}

		if ($parent->isHidden()) {
			$this->hide();
		}

		$queryParameters = $parent->getQueryParameters();

		foreach ($parent->getQueriesFromCache() as $query) {
			if (!isset($queryParameters[$query->getKey()])) {
				$queryParameters[$query->getKey()] = $query->getFirstValue();
			}
		}

		foreach ($queryParameters as $key => $value) {
			$this->query($key, $value);
		}

		return $this;
	}

	public function config(array $config): static
	{
		$this->config = array_merge($this->config, $config);
		return $this;
	}

	public function disable(string $reason = null): static
	{
		$this->disabled = true;
		$this->disable_reason = $reason;
		return $this;
	}

	public function isDisabled(): bool
	{
		return $this->disabled;
	}

	public function getDisableReason(): ?string
	{
		return $this->disable_reason;
	}

	public function hide(): static
	{
		$this->hidden = true;
		return $this;
	}

	public function isHidden(): bool
	{
		return $this->hidden;
	}

	public function isVisitable(bool $checkAgain = false): bool
	{
		return true;
		// TODO - geht noch nicht
		$visitable = !$this->isHidden() && !$this->isDisabled();

		if (!$visitable && $checkAgain) {
			Structure::runConditions($this, true);
		}

		return $visitable;
	}

	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	public function getUrlKey(): string
	{
		return str_replace(self::KEY_SEPARATOR, '/', $this->key);
	}

	public static function toKey(string $stepKey): string
	{
		return str_replace('/', self::KEY_SEPARATOR, $stepKey);
	}

	public function getElementKey(): string
	{
		$levels = explode(self::KEY_SEPARATOR, $this->key);
		return $levels[count($levels) - 1];
	}

	/**
	 * Key um Info-Texte zu pflegen
	 * @return string
	 */
	public function getHelpTextKey(): string
	{
		$defaultKey = Str::afterLast($this->getKey(), self::KEY_SEPARATOR);
		// Falls ein anderer key in der Config angegeben wurde diesen benutzen
		$key = $this->getConfig('info_texts', $defaultKey);

		if ($this->parent !== null) {
			$key = $this->parent->getHelpTextKey().self::KEY_SEPARATOR.$key;
		}

		return $key;
	}

	public function getRootBlock(): AbstractElement
	{
		if (null !== $parent = $this->getParent()) {
			return $parent->getRootBlock();
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getConfig(string $key, mixed $default = null): mixed
	{
		return Arr::get($this->config, $key, $default);
	}

	public function query(string $key, mixed $value)
	{
		$this->queryParameters[$key] = $value;
		return $this;
	}

	public function setQueryParameters(array $queryParameters): static
	{
		$this->queryParameters = $queryParameters;
		return $this;
	}

	public function getQueryParameters(): array
	{
		return $this->queryParameters;
	}

	public function getQueryParameter(string $key, $default = null): mixed
	{
		return $this->queryParameters[$key] ?? $default;
	}

	/**
	 * @return string
	 */
	public function getTitle(Wizard $wizard): string
	{
		$title = $this->getConfig('title', '');
		$translate = $this->getConfig('translate_title', true);

		if ($translate && !empty($title)) {
			$title = $wizard->translate($title);
		}

		return $title;
	}

	public function getTitlePath(Wizard $wizard, string $separator = ' &raquo; '): string
	{
		return implode($separator, $this->getTitles($wizard));
	}

	public function getSitemapTitle(Wizard $wizard): string
	{
		$title = $this->getConfig('sitemap_title', '');

		if (!empty($title)) {
			$translate = $this->getConfig('translate_title', true);
			if ($translate) {
				$title = $wizard->translate($title);
			}
		} else {
			$title = $this->getTitle($wizard);
		}

		return $title;
	}

	/**
	 * @return string[]
	 */
	public function getTitles(Wizard $wizard): array
	{
		$titles = [$this->getTitle($wizard)];

		if ($this->parent !== null) {
			$titles = array_merge($this->parent->getTitles($wizard), $titles);
		}

		return $titles;
	}

	/**
	 * @return Block|null
	 */
	public function getParent(): ?Block
	{
		return $this->parent;
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
		return [1, 1];
	}

}