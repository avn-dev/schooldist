<?php

namespace Admin;

use Admin\Attributes\Component\Parameter;
use Admin\Dto\Component\Parameters;
use Admin\Helper\ComponentPlaceholders;
use Admin\Interfaces\Component;
use Admin\Interfaces\Component\InteractsWithSearch;
use Admin\Interfaces\Component\VueComponent;
use Admin\Interfaces\Tenants;
use Core\Helper\BitwiseOperator;
use Core\Service\Routing\ModelBinding;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class Instance
{
	const COMPONENT_VUE = 1;

	protected array $bootProcesses = [];

	protected bool $booted = false;

	protected array $components = [];

	protected array $componentsPlaceholders = [];

	protected array $search = [];

	protected ?string $helpdesk = null;
	protected ?string $supportChat = null;

	protected ?string $tenants = null;

	public function __construct(protected Container $container) {
		$this->booting(function (self $instance) {
			$instance->search(Search\Navigation::KEY, Search\Navigation::class);
			$instance->component(Components\NavigationComponent::KEY, Components\NavigationComponent::class);
			$instance->component(Components\UserBoardComponent::KEY, Components\UserBoardComponent::class);
			$instance->component(Components\SearchComponent::KEY, Components\SearchComponent::class);
			$instance->component(Components\BookmarksComponent::KEY, Components\BookmarksComponent::class);
			$instance->component(Components\TabAreaComponent::KEY, Components\TabAreaComponent::class);
			$instance->component(Components\SupportComponent::KEY, Components\SupportComponent::class);
			$instance->component(Components\AccessDeniedComponent::KEY, Components\AccessDeniedComponent::class);
			$instance->component(Components\MyProfileComponent::KEY, Components\MyProfileComponent::class);
			$instance->component(Components\DashboardComponent::KEY, Components\DashboardComponent::class);
			$instance->component(Components\Dashboard\SystemUpdateWidgetComponent::KEY, Components\Dashboard\SystemUpdateWidgetComponent::class);
			$instance->component(Components\Gui2DialogComponent::KEY, Components\Gui2DialogComponent::class);
		});
	}

	public function booting(callable $process): static
	{
		$this->bootProcesses[] = $process;

		if ($this->booted) {
			$this->resolveProcess($process);
		}

		return $this;
	}

	public function boot(): void
	{
		if ($this->booted) {
			return;
		}

		$processes = $this->bootProcesses;
		array_walk($processes, fn ($process) => $this->resolveProcess($process));

		$this->booted = true;
	}

	public function tenants(string $class): static
	{
		if (!is_a($class, Tenants::class, true)) {
			throw new \InvalidArgumentException(sprintf('Tenant class "%s" must be an instance of "%s"', $class, Tenants::class));
		}

		$this->tenants = $class;

		return $this;
	}

	public function getTenants(): ?Tenants
	{
		$this->boot();

		if (empty($this->tenants)) {
			return null;
		}

		return $this->container->make($this->tenants);
	}

	public function component(string $name, string $class)
	{
		if (!is_a($class, Interfaces\Component::class, true)) {
			throw new \InvalidArgumentException(sprintf('Components must be an instance of %s [%s]', Interfaces\Component::class, $class));
		}

		$type = 0;

		if (is_a($class, VueComponent::class, true)) {
			BitwiseOperator::add($type, self::COMPONENT_VUE);
		}

		$bindingKey = $this->prepareComponentName($name);

		if (
			isset($this->components[$bindingKey]) &&
			!is_a($class, $this->components[$bindingKey]['class'], true)
		) {
			// Das überschreibende Component sollte von der anderen Komponente ableiten
			throw new \RuntimeException(sprintf('Overriding component class should be an instance of %s [%s]', $this->components[$bindingKey]['class'], $class));
		}

		$this->components[$bindingKey] = ['key' => $bindingKey, 'type' => $type, 'class' => $class];

		return $this;
	}

	public function getComponent(string $component, array $parameters = []): Component
	{
		$this->boot();

		$entry = $this->findComponent($component, $parameters);

		return $this->resolveComponent($entry['key'], $entry['class'], $entry['placeholders'], $entry['parameters']);
	}

	public function getComponentBindingKey(string|Component $component, array $placeholders = []): string
	{
		$this->boot();

		if ($component instanceof Component) {
			$placeholders = $this->componentsPlaceholders[spl_object_hash($component)] ?? [];
			$component = $component::class;
		}

		$entry = $this->findComponent($component, $placeholders);

		foreach ($entry['needed_placeholders'] as $placeholder) {
			$entry['key'] = str_replace('{'.$placeholder.'}', urlencode($placeholders[$placeholder]), $entry['key']);
		}

		return $entry['key'];
	}

	private function findComponent(string $component, array $parameters = [])
	{
		$entry = null;
		$neededPlaceholders = [];

		if (class_exists($component)) {
			$entry = Arr::first($this->components, fn (array $entry) => $entry['class'] === $component);
			if ($entry) {
				$neededPlaceholders = ComponentPlaceholders::getPlaceholders($entry['key']);
			}
		} else {
			[$key, $placeholders] = ComponentPlaceholders::matchAgainst($this->prepareComponentName($component), array_keys($this->components));

			if (!empty($key)) {
				$neededPlaceholders = ComponentPlaceholders::getPlaceholders($key);
				$entry = $this->components[$key];
				$parameters = [...$parameters, ...$placeholders];
			}
		}

		if (empty($entry)) {
			$this->getLogger('Components')->error('Component not found', ['component' => $component, 'booted' => array_keys($this->components)]);
			throw new \InvalidArgumentException(sprintf('Unknown component [%s]', $component));
		}

		if (!empty($neededPlaceholders) && !empty($diff = array_diff($neededPlaceholders, array_keys($parameters)))) {
			$this->getLogger('Components')->error('Missing component placeholders', ['component' => $component, 'missing' => $diff, 'needed' => $neededPlaceholders]);
			throw new \InvalidArgumentException(sprintf('Missing component placeholders [%s]', implode(', ', $diff)));
		}

		$entry['placeholders'] = Arr::only($parameters, $neededPlaceholders);
		$entry['needed_placeholders'] = $neededPlaceholders;
		$entry['parameters'] = Arr::except($parameters, $neededPlaceholders);

		return $entry;
	}

	/**
	 * @param int|null $flags
	 * @return Collection
	 */
	public function getComponents(int $flags = null): Collection
	{
		$this->boot();

		$components = $this->components;
		if ($flags !== null) {
			$components = array_filter($components, fn (array $entry) => BitwiseOperator::has($entry['type'], $flags));
		}

		return collect($components)->pluck('class', 'key');
	}

	public function search(string $name, string $class)
	{
		if (!is_a($class, InteractsWithSearch::class, true)) {
			throw new \InvalidArgumentException(sprintf('Components must be an instance of %s [%s]', InteractsWithSearch::class, $class));
		}

		$this->search[$name] = ['key' => $name, 'class' => $class];
	}

	public function getSearchInstance(string $search): InteractsWithSearch
	{
		$this->boot();

		if (class_exists($search)) {
			$entry = Arr::first($this->search, fn (array $entry) => $entry['class'] === $search);
		} else {
			$entry = $this->search[$search] ?? null;
		}

		if (empty($entry)) {
			$this->getLogger('Search')->error('Search instance not found', ['search' => $search, 'booted' => array_keys($this->search)]);
			throw new \InvalidArgumentException(sprintf('Unknown search instance [%s]', $search));
		}

		return $this->resolveSearch($entry['key'], $entry['class']);
	}

	public function getSearch(): Collection
	{
		$this->boot();

		return collect($this->search)->mapWithKeys(fn ($entry, $key) => [$key => $this->getSearchInstance($entry['class'])]);
	}

	/*
	 * TODO Support-Bereich ausbauen -------------------------------------------------
	 */

	public function helpdesk(string $url): static
	{
		$this->helpdesk = $url;
		return $this;
	}

	public function supportChat(string $url): static
	{
		$this->supportChat = $url;
		return $this;
	}

	public function getSupportFeatures(): array
	{
		$this->boot();

		$features = [];
		if ($this->helpdesk) $features['helpdesk'] = $this->helpdesk;
		if ($this->supportChat) $features['support_chat'] = $this->supportChat;

		return $features;
	}

	public function getSupportChatUrl(): ?string
	{
		return $this->supportChat;
	}

	public function hasSupport(): bool
	{
		return $this->helpdesk !== null || $this->supportChat !== null;
	}

	/*
	 * -------------------------------------------------------------------------------
	 */

	public function getLogger(string $namespace = 'default'): LoggerInterface
	{
		return \Log::getLogger('admin', $namespace);
	}

	public function translate(string $value, string|array $section = null): string
	{
		return \L10N::t($value, $this->buildL10NContext($section));
	}

	public function buildL10NContext(string|array $section = null): string
	{
		$path = ['Admin'];
		if ($section) {
			$path = [...$path, ...Arr::wrap($section)];
		}
		return implode(' » ', $path);
	}

	private function resolveProcess(callable $process): void
	{
		$this->container->call($process);
	}

	private function resolveComponent(string $key, string $class, array $placeholders = [], array $parameters = []): Component
	{
		foreach ($placeholders as $placeholder => $value) {
			$key = str_replace('{'.$placeholder.'}', urlencode($value), $key);
		}

		$bindingKey = 'admin.component.'.$key;

		// Wurde das Component bereits erzeugt?
		if ($this->container->has($bindingKey)) {
			return $this->container->make($bindingKey);
		}

		if (method_exists($class, '__construct') && !empty($placeholders)) {
			// Entity-Platzhalter direkt auflösen
			$placeholders = ModelBinding::resolveModelsForCall($class, '__construct', $placeholders);
		}

		/* @var Component $instance*/
		$instance = $this->container->make($class, $placeholders);

		if ($instance instanceof Component\HasParameters) {
			$attributes = (new \ReflectionClass($instance))->getAttributes(Parameter::class);

			$availableParameters = array_map(fn (\ReflectionAttribute $attribute) => $attribute->getArguments()['name'], $attributes);

			$valid = $instance->validate(Arr::only($parameters, $availableParameters));

			$instance->setParameters(new Parameters($valid));
		} else {
			$parameters = [];
		}

		if (!empty($placeholders)) {
			$this->componentsPlaceholders[spl_object_hash($instance)] = $placeholders;
		}

		if (empty($parameters)) {
			$this->container->instance($bindingKey, $instance);
		}

		return $instance;
	}

	private function resolveSearch(string $key, string $class): InteractsWithSearch
	{
		$bindingKey = 'admin.search.'.$key;

		if ($this->container->has($bindingKey)) {
			return $this->container->make($bindingKey);
		}

		$instance = $this->container->make($class);

		$this->container->instance($bindingKey, $instance);

		return $instance;
	}

	private function prepareComponentName(string $name): string
	{
		$placeholders = ComponentPlaceholders::getPlaceholders($name);

		// Platzhalter beibehalten
		foreach ($placeholders as $index => $placeholder) {
			$name = str_replace('{'.$placeholder.'}', 'placeholder'.$index, $name);
		}

		// Tabs und Spaces entfernen
		$name = preg_replace('/\s+/', '', $name);
		// Wird z.b. in der Component-Api in der URL benutzt
		$name = Str::slug(Str::headline($name), dictionary: ['.' => '-']);

		// Platzhalter wieder einfügen
		foreach ($placeholders as $index => $placeholder) {
			$name = str_replace('placeholder'.$index, '{'.$placeholder.'}', $name);
		}

		return $name;
	}
}