<?php

namespace Tc\Service;

use Carbon\Carbon;
use Core\Helper\BitwiseOperator;
use Illuminate\Container\Container;
use Psr\Log\LoggerInterface;
use Tc\Events\EntityEventDispatched;
use Tc\Events\EventManagerFailed;
use Tc\Exception\EventManagerException;
use Tc\Exception\EventPipelineException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Tc\Handler\SequentialProcessing\EventDto;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\EventManager\Repository;
use Tc\Interfaces\EventManager\TestableEvent;
use Tc\Interfaces\Events\Settings;
use TcExternalApps\Service\AppService;

/**
 * TODO Interfaces
 */
class EventManager {

	const L10N_PATH = 'Fidelo » Events';
	const LOG_LEVEL_LOW = 0;
	const LOG_LEVEL_ALL = 1;

	private bool $turnedOff = false;

	private int $logLevel = 0;

	private ?string $logEvent = null;

	private ?LoggerInterface $logger = null;

	private ?LanguageAbstract $l10n = null;

	private array $events = [];

	private static array $eventProcessBinding = [];

	private static array $eventsListenersAndConditions = [];

	public function __construct(
		private readonly Container $container,
		private readonly Repository $repository
	){
		//$this->listen(EntityEventDispatched::class); // TODO
		$this->logLevel |= self::LOG_LEVEL_LOW;
	}

	/**
	 * Event-Management abschalten
	 *
	 * @return $this
	 */
	public function turnOff(): static
	{
		$this->turnedOff = true;
		return $this;
	}

	/**
	 * Jeden Schritt in ein Log-File schreiben
	 *
	 * @return $this
	 */
	public function enableLogging(string $eventName = null): static
	{
		$this->logEvent = $eventName;
		$this->logLevel |= self::LOG_LEVEL_ALL;
		EventPipeline::enableDebug();
		return $this;
	}

	/**
	 * Informationen über Events die durchgelaufen sind
	 *
	 * @param string|null $eventName
	 * @return array
	 */
	public function getDebug(string $eventName = null): array
	{
		return EventPipeline::getDebug($eventName);
	}

	/**
	 * Events zum Abhören dem Event-Manager hinzufügen (z.b. über Service-Provider hinzufügen)
	 *
	 * @param string|array $payload
	 * @return $this
	 */
	public function listen(string $eventName, array $config = []): static
	{
		$this->events[$eventName] = $config;
		return $this;
	}

	/**
	 * Prüft, ob es Aktionen zu einem bestimmten Event gibt
	 *
	 * @param string $eventName
	 * @param bool $checkProcesses
	 * @return bool
	 */
	public function isListening(string $eventName, bool $checkProcesses = true): bool
	{
		if (
			$this->getEventList()->has($eventName) &&
			(!$checkProcesses || $this->getEventProcesses($eventName)->isNotEmpty())
		) {
			return true;
		}

		return false;
	}

	/**
	 * Event verarbeiten
	 *
	 * TODO SequentialProcessing?
	 *
	 * @param string $eventName
	 * @param mixed $payload
	 * @return void
	 */
	public function handle(string $eventName, mixed $payload, bool $force = false): void
	{
		// EventManager ist ausgeschaltet
		if ($this->turnedOff) {
			$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Event manager turned off'));
			return;
		}

		if (class_exists($eventName) && is_array($payload)) {
			// https://laravel.com/docs/9.x/events#wildcard-event-listeners
			// Laravel macht diesen Fall etwas komisch, da bei einem Wildcard-Listener das Payload immer ein Array ist
			// und nicht wie angenommen das Event-Objekt selber. In diesem Fall steht das Event-Objekt als erstes Element
			// im Array und damit wir bei den Listeners wieder das Event-Objekt als Payload haben schreiben wir hier das
			// Payload um.
			$payload = Arr::first($payload);
		}

		if (
			php_sapi_name() !== 'cli' &&
			!\Util::isDebugIP() &&
			!$force
		) {
			\Core\Facade\SequentialProcessing::add('tc/event-manager', new EventDto($eventName, $payload));
			return;
		}

		// TODO Wird aktuell noch nicht benutzt
		/*if ($payload instanceof EntityDispatcher) {
			$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Entity dispatched', ['event' => $eventName, 'entity' => get_class($payload->getEntity()), 'id' => $payload->getEntity()->id]));
			// Dieses Event betrifft eine Entität die beobachtet werden kann, d.h. falls ein Benutzer diese Entität beobachtet,
			// wird dieser über alle Aktionen dieser Entität informiert
			EntityEventDispatched::dispatch($payload);
		}*/

		// Event steht für den EventManager nicht zur Verfügung
		if (!$this->getEventList()->has($eventName)) {
			//$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Not listening on event', ['event' => $eventName]));
			return;
		}

		$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Run event', ['event' => $eventName]));

		// Alle Prozesse zu diesem Event laden
		$eventProcesses = $this->getEventProcesses($eventName, $payload);

		if ($eventProcesses->isEmpty()) {
			$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('No event processes found', ['event' => $eventName]));
			return;
		}

		$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Number of processes found', ['event' => $eventName, 'processes' => $eventProcesses->count()]));

		foreach ($eventProcesses as $process) {
			/* @var Process $process */
			try {

				$start = microtime(true);

				$this->runProcess($eventName, $payload, $process);

				$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Event successfully executed', ['event' => $eventName, 'process' => $this->buildProcessId($process), 'time' => microtime(true) - $start]));

			} catch (\Throwable $throwable) {

				$this->handleThrowable($process, $throwable);

			}
		}

	}

	/**
	 * Alle Entitäten mit einer bestimmten Ausführungszeit ausführen (Scheduler)
	 *
	 * @param Carbon $date
	 * @param ...$args
	 * @return void
	 */
	public function handleScheduled(Carbon $date, ...$args): void
	{
		$this->log('scheduler', self::LOG_LEVEL_LOW, fn ($log) => $log->info('Scheduled events', ['date' => $date->toDateTimeString()]));

		$processes = $this->repository->forTime($date);

		$processes->each(function (Process $process) use ($date, $args) {

			$eventName = $process->getProcessName();

			$this->log($eventName, self::LOG_LEVEL_LOW, fn ($log) => $log->info('Execute scheduled event', ['event' => $eventName, 'process' => $this->buildProcessId($process)]));

			// Entität für dieses Event festsetzen, damit keine weiteren Entitäten ausgeführt werden (siehe getEventEntities())
			self::$eventProcessBinding[$eventName] = $process;

			try {
				// TODO evtl. mit app()->call() umsetzen damit man Services injecten kann
				// Statische Methode in der Event-Klasse welche das eigentliche Event auslöst
				$eventName::dispatchScheduled($date, $process, ...$args);

				$this->log($eventName, self::LOG_LEVEL_LOW, fn ($log) => $log->info('Finished scheduled event', ['event' => $eventName, 'process' => $this->buildProcessId($process)]));

			} catch (\Throwable $throwable) {

				$this->handleThrowable($process, $throwable);

			}

			unset(self::$eventProcessBinding[$eventName]);
		});

		$this->log('scheduler', self::LOG_LEVEL_LOW, fn ($log) => $log->info('Finished scheduled events', ['date' => $date->toDateTimeString()]));
	}

	/**
	 * Event anhand der Einstellungen aus dem Interface verarbeiten
	 *
	 * @param string $eventName
	 * @param mixed $payload
	 * @param Process $process
	 * @return void
	 */
	private function runProcess(string $eventName, mixed $payload, Process $process): void
	{
		if ($payload instanceof Manageable) {
			$payload->bindManagedObject($process);
		}

		// Alle Listeners und Conditions die für das Event zur Verfügung stehen
		[$manageableListeners, $manageableConditions] = $this->getEventListenersAndConditions($eventName);
		// Alle Listeners und Conditions des Prozesses
		$tasks = [...$process->getListeners(), ...$process->getConditions()];

		$listeners = $conditions = [];

		foreach ($tasks as $task) {
			/* @var Process\Task $task */

			$added = false;
			// Prüfen, ob die eingestellten Listeners und Conditions für das Event überhaupt (noch) verfügbar sind
			if ($task->getType()->isListener()) {
				[$class, $method] = Arr::first($manageableListeners, fn ($listener) => $listener[0] === $task->getClass());
				if ($class && $method) {
					$listeners[] = [$this->resolve($task), $method];
					$added = true;
				}
			} else if ($task->getType()->isCondition()) {
				[$class, $method] = Arr::first($manageableConditions, fn ($condition) => $condition[0] === $task->getClass());
				if ($class && $method) {
					$conditions[] = [$this->resolve($task), $method];
					$added = true;
				}
			}

			if (!$added) {
				$this->log($eventName, self::LOG_LEVEL_LOW, fn ($log) => $log->info('Class not registered', ['event' => $eventName, 'class' => $task->getClass(), 'process' => $this->buildProcessId($process)]));
			}
		}

		if (empty($listeners)) {
			$this->log($eventName, self::LOG_LEVEL_LOW, fn ($log) => $log->info('No listeners defined', ['event' => $eventName, 'process' => $this->buildProcessId($process)]));
			return;
		}

		$this->log($eventName, self::LOG_LEVEL_ALL, function ($log) use ($process, $listeners, $conditions) {
			$log->info('Run event pipeline', [
				'event' => $process->getProcessName(),
				'process' => $this->buildProcessId($process),
				// Nur die Klasse loggen
				'listeners' => array_map(fn ($listener) => $listener[0]::class, $listeners),
				'conditions' => array_map(fn ($condition) => $condition[0]::class, $conditions)
			]);
		});

		(new EventPipeline())
			->send($payload)
			->through($listeners)
			->if($conditions)
			->finally(fn () => $process->updateLastAction())
			->run();
	}

	public function runProcessTest(string $eventName, Process $process, Settings $settings): array
	{
		if (!class_exists($eventName)) {
			throw new \RuntimeException(sprintf('Test modus only works for event objects [given: %s]', $eventName));
		} else if (!is_a($eventName, TestableEvent::class, true)) {
			throw new \RuntimeException('Event is not an instance of %s [%s]', TestableEvent::class, $eventName);
		}

		// Debug-Informationen für die Ausgabe sammeln
		EventPipeline::enableDebug();

		try {
			$testEvent = $eventName::buildTestEvent($settings);

			$this->runProcess($eventName, $testEvent, $process);
			$errors = null;
		} catch (\Throwable $e) {
			$errors = $e->getMessage();
		}

		return [
			'errors' => $errors,
			'pipeline' => Arr::first(EventPipeline::getDebug($eventName))
		];
	}

	/**
	 * Alle im Interface getroffenen Einstellungen zu einem Event laden
	 *
	 * @param string $eventName
	 * @param mixed $payload
	 * @return Collection
	 */
	private function getEventProcesses(string $eventName, mixed $payload = null): Collection
	{
		if (isset(self::$eventProcessBinding[$eventName])) {
			$this->log($eventName, self::LOG_LEVEL_ALL, fn ($log) => $log->info('Fix entity for event', ['event' => $eventName, 'entity' => self::$eventProcessBinding[$eventName]->id]));
			// Für das aktuelle Event wurde ein spezifischer Prozess gesetzt (Scheduler)
			return new Collection([self::$eventProcessBinding[$eventName]]);
		}

		if ($payload instanceof EntityEventDispatched) {
			// Entity Abonnements
			return $this->repository->forEntity($eventName, $payload->getEntity());
		}

		return $this->repository->forEvent($eventName);
	}

	/**
	 * @param string $eventName
	 * @return string
	 * @throws \Exception
	 */
	public function getEventTitle(string $eventName): string
	{
		$customTitle = Arr::get($this->events, $eventName.'.title', null);

		if (class_exists($eventName) && empty($customTitle)) {
			return $eventName::getTitle();
		} else if (!empty($customTitle)) {
			return $this->l10n()->translate($this->events[$eventName]['title']);
		}

		return $eventName;
	}

	/**
	 * Liefert alle möglichen Listeners und Conditions zu einem Event
	 *
	 * @param string $eventName
	 * @return array
	 */
	public function getEventListenersAndConditions(string $eventName): array
	{
		if (isset(self::$eventsListenersAndConditions[$eventName])) {
			return self::$eventsListenersAndConditions[$eventName];
		}

		$manageableListeners = $manageableConditions = [];

		// Config-Datei steht über Instanz

		if (isset($this->events[$eventName]['listeners'])) {
			$manageableListeners = $this->events[$eventName]['listeners'];
		} else if (is_a($eventName, ManageableEvent::class, true)) {
			$manageableListeners = $eventName::getManageableListeners();
		}

		if (isset($this->events[$eventName]['conditions'])) {
			$manageableConditions = $this->events[$eventName]['conditions'];
		} else if (is_a($eventName, ManageableEvent::class, true)) {
			$manageableConditions = $eventName::getManageableConditions();
		}

		self::$eventsListenersAndConditions[$eventName] = [
			$this->completeWithMethodName(Arr::wrap($manageableListeners), 'handle'),
			$this->completeWithMethodName(Arr::wrap($manageableConditions), 'passes')
		];

		return self::$eventsListenersAndConditions[$eventName];
	}

	/**
	 * Korrigiert evtl. fehlende Methodennamen bei einer Definition einer Klasse (Listener, Condition)
	 *
	 * Mögliche Definitionen:
	 * [
	 * 		\Tc\Listeners\SendUserSystemNotification::class,
	 *		\Tc\Listeners\SendUserSystemNotification::class.'@handle'
	 * 		[\Tc\Listeners\SendUserSystemNotification::class, 'handle'] // endgültiges Format
	 * ]
	 *
	 * @param array $classes
	 * @return array
	 */
	private function completeWithMethodName(array $classes, string $defaultMethod): array
	{
		return array_map(
			fn ($class) => is_array($class) ? $class : Str::parseCallback($class, $defaultMethod),
			$classes
		);
	}

	/**
	 * Liefert einer Liste aller Event-Namen auf die der Eventmanager reagieren soll
	 *
	 * @param \Access|null $access
	 * @return Collection
	 */
	public function getEventList(\Access $access = null): Collection
	{
		$events = new Collection($this->events);

		if ($access !== null) {
			$events = $events->filter(function ($config) use ($access) {
				if (!empty($accessRight = Arr::get($config, 'access'))) {
					if (is_string($accessRight) && str_starts_with($accessRight, 'app:')) {
						return AppService::hasApp(Str::after($accessRight, 'app:'));
					} else {
						return $access->hasRight($accessRight);
					}
				}
				return true;
			});
		}

		return $events;
	}

	/**
	 * Liefert eine detaillierte Liste aller Events auf die der Eventmanager reagieren soll
	 *
	 * @param \Access|null $access
	 * @return Collection
	 * @throws \Exception
	 */
	public function getDetailedList(\Access $access = null): Collection
	{
		return $this->getEventList($access)->keys()
			->mapWithKeys(function ($eventName) {

				[$manageableListeners, $manageableConditions] = $this->getEventListenersAndConditions($eventName);

				return [
					$eventName => [
						'title' => $this->getEventTitle($eventName),
						'listeners' => $manageableListeners,
						'conditions' => $manageableConditions,
					]
				];
			});
	}

	/**
	 * Logger
	 *
	 * @return LoggerInterface
	 */
	public function logger(): LoggerInterface
	{
		if ($this->logger === null) {
			$this->logger = \Log::getLogger('events', 'EventManager');
		}
		return $this->logger;
	}

	/**
	 * Logger überschreiben
	 *
	 * @param LoggerInterface $logger
	 * @return $this
	 */
	public function setLogger(LoggerInterface $logger): static
	{
		$this->logger = $logger;
		return $this;
	}

	/**
	 * EventManager Logging
	 *
	 * @deprecated
	 * @return LoggerInterface
	 */
	public function getLogger(): LoggerInterface
	{
		return $this->logger();
	}

	/**
	 * Log schreiben
	 *
	 * @param int $level
	 * @param \Closure $callback
	 * @return void
	 */
	public function log(string $eventName, int $level, \Closure $callback): void
	{
		if (
			BitwiseOperator::has($this->logLevel, $level) &&
			(
				$this->logEvent === null ||
				$this->logEvent === $eventName
			)
		) {
			$callback($this->logger());
		}
	}

	/**
	 * EventManager L10n-Objekt
	 *
	 * @param string|null $language
	 * @return LanguageAbstract
	 * @throws \Exception
	 */
	public function l10n(string $language = null): \Tc\Service\LanguageAbstract
	{
		if ($this->l10n === null) {
			$this->l10n = new \Tc\Service\Language\Backend($language ?? \System::getInterfaceLanguage());
			$this->l10n->setContext(self::L10N_PATH);
		}

		return $this->l10n;
	}

	/**
	 * @param LanguageAbstract $l10n
	 * @return $this
	 */
	public function setL10N(LanguageAbstract $l10n): static
	{
		$this->l10n = $l10n;
		return $this;
	}

	/**
	 * @param Process\Task $task
	 * @return Manageable
	 */
	private function resolve(Process\Task $task): Manageable
	{
		$object = $this->container->make($class = $task->getClass());

		if (!$object instanceof Manageable) {
			throw new \RuntimeException(sprintf('Class does not implement manageable interface [%s]', $class));
		}

		$object->bindManagedObject($task);

		return $object;
	}

	/**
	 * @param Process $process
	 * @param \Throwable $throwable
	 * @return void
	 */
	private function handleThrowable(Process $process, \Throwable $throwable): void
	{
		$eventLog = ['event' => $process->getProcessName(), 'process' => $this->buildProcessId($process)];
		if ($throwable instanceof EventPipelineException) {
			$eventLog['task'] = $throwable->getFailedElement()::class;
			$throwable = $throwable->getPrevious();
		}

		$this->log($process->getProcessName(), self::LOG_LEVEL_LOW, fn ($log) => $log->error(sprintf('Event "%s" failed', $eventLog['event']), array_merge($eventLog, ['message' => $throwable->getMessage(), 'file' => $throwable->getFile(), 'line' => $throwable->getLine(), 'trace' => $throwable->getTrace()])));

		$ex = new EventManagerException($process, $throwable, $throwable->getMessage(), $throwable->getCode());

		// Ansonsten Endlosschleife, wenn in dem EventManagerFailed-Event selbst auch etwas fehlschlägt
		if ($this->container->bound('events') && $process->getProcessName() !== EventManagerFailed::class) {
			EventManagerFailed::dispatch($ex);
		}
	}

	private function buildProcessId(Process $process): string
	{
		return $process::class.'#'.$process->getIdentifier();
	}
}
