<?php

namespace Tc\Service;

use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use User;
use Gui2\Entity\InfoText;
use Tc\Middleware\AbstractWizardMiddleware;
use Tc\Interfaces\Wizard\Log;
use Tc\Middleware\WizardStep;
use Tc\Interfaces\Wizard\LogStorage;
use Tc\Controller\WizardController;
use Tc\Service\Wizard\Iteration;
use Tc\Service\Wizard\Structure;
use Illuminate\Support\Facades\Route;
use Core\Handler\SessionHandler;
use Core\Helper\Routing;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;


class Wizard
{
	public ?string $heading = null;

	public ?string $subHeading = null;

	private ?User $user = null;

	private ?\Access $access = null;

	private SessionHandler $session;

	private ?Structure\Step $indexStep = null;

	private array $enabled = [
		'stop_and_continue' => true, // Speichern und Schließen
		'progress_icons' => true // Haken wenn Step abgeschlossen
	];

	public function __construct(
		private string $key,
		private string $routingPrefix, // TODO durch Routing-Klasse ersetzen
		private LanguageAbstract $l10n,
		private LogStorage $logStorage,
		private Structure|\Closure $structure
	) {
		$this->session = SessionHandler::getInstance();
	}

	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Aktuellen Benutzer setzen
	 *
	 * @param User $user
	 * @return $this
	 */
	public function user(User $user, \Access $access = null): static
	{
		$this->user = $user;
		$this->access = $access;
		return $this;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function getAccess(): ?\Access
	{
		return $this->access;
	}

	/**
	 * Überschrift des Wizards
	 *
	 * @param string $heading
	 * @param string|null $subHeading
	 * @return $this
	 */
	public function heading(string $heading, string $subHeading = null): static
	{
		$this->heading = $heading;
		$this->subHeading = $subHeading;
		return $this;
	}

	/**
	 * Übersetzen
	 *
	 * @param string $translate
	 * @return string
	 */
	public function translate(string $translate): string
	{
		return $this->l10n->translate($translate);
	}

	/**
	 * L10N-Objekt
	 *
	 * @return LanguageAbstract
	 */
	public function getLanguageObject(): LanguageAbstract
	{
		return $this->l10n;
	}

	public function disable(string $option): static
	{
		$this->enabled[$option] = false;
		return $this;
	}

	public function enable(string $option): static
	{
		$this->enabled[$option] = true;
		return $this;
	}

	public function isEnabled(string $option): bool {
		return $this->enabled[$option];
	}

	/**
	 * FlashBag-Message setzen
	 *
	 * @param string $type
	 * @param string|array $message
	 * @return $this
	 */
	public function message(string $type, string|array $message): static
	{
		foreach (Arr::wrap($message) as $message) {
			$this->session->getFlashBag()->add($type, $message);
		}
		return $this;
	}

	/**
	 * Liefert alle FlashBag-Messages
	 * @param string $type
	 * @return array
	 */
	public function getMessages(string $type): array
	{
		return $this->session->getFlashBag()->get($type, []);
	}

	/**
	 * Session-Objekt
	 *
	 * @return SessionHandler
	 */
	public function getSession(): SessionHandler
	{
		return $this->session;
	}

	/**
	 * Liefert die komplette Struktur aus Blöcken und Steps
	 *
	 * @return Structure
	 */
	public function getStructure(): Structure
	{
		if ($this->structure instanceof \Closure) {
			$structure = $this->structure;
			$this->structure = $structure($this);
			if (!$this->structure instanceof Structure) {
				throw new \RuntimeException('Please provide instance of '.Structure::class);
			}
		}
		return $this->structure;
	}

	public function index(string|Structure\Step $step): static
	{
		if (is_string($step)) {
			$step = $this->getStructure()->getStep($step);
		}

		if ($step instanceof Structure\Step) {
			$this->indexStep = $step;
		}

		return $this;
	}

	public function isIndexStep(Structure\Step $step): bool
	{
		return $this->indexStep === $step;
	}

	/**
	 * Wizard-Startseite generieren
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function showIndex(Request $request)
	{
		if ($this->indexStep) {
			$this->newIteration()->currentStep($this->indexStep);
			$this->writeLog($this->indexStep);
			return $this->visit($this->indexStep, $request);
		}

		$finishedLogs = collect($this->getLogs())
			->filter(fn($log) => $log->isFinished())
			->toArray();

		$templateData = [
			'wizard' => $this,
			'structure' => $this->getStructure()->toSitemapArray($this, null, $finishedLogs),
		];

		return response()->view('wizard/index', $templateData);
	}

	/**
	 * Wizard an gegebenen Element starten oder von vorne beginnen
	 *
	 * @param string|null $elementKey
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function start(string $elementKey = null) {

		if ($elementKey === null) {
			$step = $this->getStructure()->getFirstStep();
		} else {
			$elementKey = Wizard\Structure\AbstractElement::toKey($elementKey);
			$step = $this->getStructure()->get($elementKey);
			if ($step instanceof Structure\Block) {
				$step = $step->getFirstStep();
			}
		}

		if (!$step) {
			$this->message('error', $this->translate('Unbekannter Einstiegspunkt'));
			return $this->redirectToHome();
		}

		if ($elementKey === null) {
			// Neue Iteration starten
			$this->newIteration()->currentStep($step);
		} else {
			$this->continueIteration()->currentStep($step);
		}

		return $this->redirect($step);
	}

	/**
	 * Prüft, ob ein vorheriger Prozess fortgeführt werden kann oder ob ein neuer Prozess gestartet werden muss
	 *
	 * @return bool
	 */
	public function canContinue(): bool
	{
		return $this->isEnabled('stop_and_continue') && $this->logStorage->getLastLog($this->user) !== null;
	}

	/**
	 * Vorherigen Prozess fortführen
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function continue() {
		// Prüfen, ob es überhaupt einen vorherigen Prozess gibt
		if(!$this->canContinue()) {
			$this->message('error', $this->translate('Bitte starten Sie einen neuen Prozess.'));
			return $this->redirectToHome();
		}

		$lastLog = $this->logStorage->getLastLog($this->user);

		$step = $this->getStructure()->get($lastLog->getStepKey());

		if (!$step instanceof Structure\Step) {
			$this->message('error', $this->translate('Unbekannter Einstiegspunkt'));
			return $this->redirectToHome();
		}

		$this->continueIteration()->currentStep($step);

		return $this->redirect($step, $lastLog->getQueryParameters());
	}

	/**
	 * Prozess abschließen
	 *
	 * @return $this
	 */
	public function finish(): static
	{
		// Alle Log-Einträge löschen
		$this->logStorage->removeLogs($this->user);
		$this->session->remove($this->getSessionKey());
		return $this;
	}

	/**
	 * Step aufrufen
	 *
	 * @param Structure\Step $step
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function visit(Structure\Step $step, Request $request)
	{
		$this->updateIteration($step);

		$lastLog = $this->getLastLog();

		if ($lastLog === null || $lastLog->getStepKey() !== $step->getKey()) {
			// Log für den Step schreiben damit dieser in der Log-Historie auftaucht
			$lastLog = $this->writeLog($step);
		}

		$step->setLog($lastLog);

		$getHelpTexts = function ($step, $language) {
			return InfoText::query()
				->where('dialog_id', 'wizard.'.$this->getKey())
				->where('gui_hash', $step->getHelpTextKey())
				->where('private', 0)
				->get()
				->mapWithKeys(fn (InfoText $infoText) => [$infoText->field => $infoText->getInfoText($language)])
			;
		};

		/* @var Collection $helpTexts */
		$helpTexts = $getHelpTexts($step, \System::getInterfaceLanguage());

		if (\System::getInterfaceLanguage() !== 'en') {
			$defaultHelpTexts = $getHelpTexts($step, 'en');
			foreach ($defaultHelpTexts as $field => $text) {
				if (empty($helpTexts->get($field))) {
					$helpTexts->put($field, $text);
				}
			}
		}

		$step->setHelpTexts($helpTexts->toArray());

		if ($step->isHidden()) {
			throw new \RuntimeException('Step hidden!');
		} else if ($step->isDisabled()) {
			throw new \RuntimeException(sprintf('Step locked! [%s]', (string)$step->getDisableReason()));
		}

		return $step->render($this, $request);
	}

	/**
	 * Step speichern
	 *
	 * @param Structure\Step $step
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function save(Structure\Step $step, Request $request)
	{
		$iteration = $this->getIteration();

		// Prüfen ob der zu speichernde Step auch der Step ist der zuletzt besucht wurde
		if (!$iteration || $iteration->getCurrentStep()->getKey() !== $step->getKey()) {
			throw new \RuntimeException('Invalid save operation');
		}

		$step->setLog($this->getLastLog());

		$action = $request->input('action', 'save');

		// Closure zur Weiterleitung auf den nächsten Step
		$next = function () use ($step) {
			$nextStep = $this->getStructure()->getNextStep($step);
			if ($nextStep) {
				return $this->redirect($nextStep);
			}
			// Ende des Wizards
			return $this->finish()->redirectToHome();
		};

		return $step->action($this, $action, $request, $next);
	}

	/**
	 * Auf den letzten Step zurückgehen
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function previous()
	{
		/* @var Log[] $lastTwoLogs */
		$lastTwoLogs = array_values(array_slice($this->logStorage->getLogs($this->user), -2));

		if (count($lastTwoLogs) < 2) {
			// Wenn es nicht mehr als zwei Logs gibt, dann befindet man sich noch auf dem ersten Step des Wizards, von hier
			// aus kann man nur auf die Startseite des Wizards zurückgehen
			return $this->redirectToHome();
		}

		[$previousLog, $currentLog] = $lastTwoLogs;

		// Log in der Historie entfernen
		$this->logStorage->removeLogs($this->user, $currentLog);

		return $this->redirect($previousLog->getStepKey(), $previousLog->getQueryParameters());
	}

	/**
	 * Weiterleitung auf ein bestimmtes Element im Wizard
	 *
	 * @param Structure\AbstractElement|string $element
	 * @param array $queryParameters
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function redirect(Structure\AbstractElement|string $element, array $queryParameters = [])
	{
		if (is_string($element)) {
			$element = $this->getStructure()->get($element);
		}

		if ($element instanceof Structure\Block) {
			$element = $element->getFirstStep();
		}

		if (!empty($queryParameters)) {
			$element->setQueryParameters($queryParameters);
		}

		return redirect($this->routeStep($element));
	}

	/**
	 * Weiterleitung auf die Startseite des Wizards
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function redirectToHome()
	{
		return redirect($this->route('index'));
	}

	/**
	 * Alle Log-Einträge liefern
	 *
	 * @return array
	 */
	public function getLogs(): array
	{
		return $this->logStorage->getLogs($this->user);
	}

	/**
	 * Letzten Log-Eintrag liefern
	 *
	 * @return Log|null
	 */
	public function getLastLog(): ?Log
	{
		return $this->logStorage->getLastLog($this->user);
	}

	/**
	 * Log-Eintrag für einen Step in der aktuellen Iteration schreiben
	 *
	 * @param Structure\Step $step
	 * @param Log|null $log
	 * @return Log
	 */
	public function writeLog(Structure\Step $step, Log $log = null): Log
	{
		if (null === $iteration = $this->getIteration()) {
			throw new \RuntimeException('No iteration found to write log for.');
		}

		return $this->logStorage->writeLog($iteration, $step, $log);
	}

	/**
	 * Liefert die aktuelle Iteration
	 * @return Iteration|null
	 */
	public function getIteration(): ?Iteration
	{
		if (!empty($array = $this->session->get($this->getSessionKey(), []))) {
			return Iteration::fromArray($this, $array);
		}
		return null;
	}

	/**
	 * Neue Iteration (Durchlauf) des Wizards starten
	 *
	 * @return Iteration
	 */
	private function newIteration(): Iteration
	{
		// Anzahl der vorherigen Durchläufe prüfen
		$lastLog = $this->logStorage->getLastLog($this->user);
		$lastIteration = ($lastLog) ? $lastLog->getIteration() : 0;

		$iteration = new Iteration($lastIteration + 1, $this->user);

		$this->session->set($this->getSessionKey(), $iteration->toArray());

		// Alle vorherigen Log-Einträge löschen da von vorne begonnen wird
		$this->logStorage->removeLogs($this->user);

		return $iteration;
	}

	/**
	 * Letzte Iteration fortführen
	 *
	 * @return Iteration
	 */
	private function continueIteration(): Iteration
	{
		$lastLog = $this->logStorage->getLastLog($this->user);
		$lastIteration = ($lastLog) ? $lastLog->getIteration() : 0;

		$iteration = new Iteration($lastIteration, $this->user);

		$this->session->set($this->getSessionKey(), $iteration->toArray());

		return $iteration;
	}

	/**
	 * Aktuelle Iteration mit dem aktuellen Step updaten
	 *
	 * @param Structure\Step $step
	 * @return $this
	 */
	public function updateIteration(Structure\Step $step): static
	{
		if (null === $iteration = $this->getIteration()) {
			throw new \RuntimeException('No iteration found to update');
		}

		$iteration->currentStep($step);
		$this->session->set($this->getSessionKey(), $iteration->toArray());
		return $this;
	}

	/**
	 * Route für den Wizard generieren
	 *
	 * @param string $route
	 * @param array $parameters
	 * @return string
	 */
	public function route(string $route, array $parameters = []): string
	{
		return Routing::generateUrl($this->routingPrefix.$route, $parameters);
	}

	/**
	 * Route für einen Step generieren (inkl. Loop-Parameter)
	 *
	 * @param Structure\Step $step
	 * @param string $route
	 * @return string
	 */
	public function routeStep(Structure\Step $step, string $route = 'step.index', array $routeParams = []): string
	{
		$routeParams['stepKey'] = $step->getUrlKey();

		foreach ($step->getQueryParameters() as $key => $value) {
			if (!isset($routeParams[$key])) {
				$routeParams[$key] = $value;
			}
		}

		return $this->route($route, $routeParams);
	}

	/**
	 * Generiert den Session-Key für den Wizard
	 *
	 * @return string
	 */
	private function getSessionKey(): string
	{
		return 'wizard_'.$this->key;
	}

	public function toMessageBag(array $errorData, array $messages, \Closure $fallback, array $fieldLabels = []): MessageBag
	{
		$messageBag = new MessageBag();

		foreach ($errorData as $field => $errors) {
			if (str_contains($field, '.')) {
				$field = Str::afterLast($field, '.');
			}

			foreach (Arr::wrap($errors) as $error) {
				$message = $messages[$error] ?? $fallback($error);
				$messageBag->add($field, sprintf($message, $fieldLabels[$field] ?? ''));
			}
		}

		return $messageBag;
	}

	/**
	 * Alle nötigen Routen des Wizards
	 *
	 * @return void
	 */
	public static function routes(string $wizardMiddleware, array $except = []): void
	{
		if (!is_a($wizardMiddleware, AbstractWizardMiddleware::class, true)) {
			throw new \InvalidArgumentException('Invalid wizard middleware instance ['.$wizardMiddleware.']');
		}

		Route::group([
			'middleware' => [$wizardMiddleware]
		], function() use ($except) {

			// TODO nicht optimal
			Route::group([
				'as' => 'help-text.',
				'prefix' => 'help-text'
			], function () {
				Route::get('load', [WizardController::class, 'loadHelpTextModal'])->name('load');
				Route::post('save', [WizardController::class, 'saveHelpTextModal'])->name('save');
			});

			if (empty($except) || !in_array('index', $except)) {
				Route::get('/', [WizardController::class, 'index'])->name('index');
			}

			if (empty($except) || !in_array('continue', $except)) {
				Route::get('/continue', [WizardController::class, 'continue'])->name('continue');
			}

			Route::get('/start/{stepKey?}', [WizardController::class, 'start'])->where(['stepKey' => '.+?'])->name('start');

			Route::group([
				'as' => 'step.',
				'where' => ['stepKey' => '.+?'],
				'middleware' => [WizardStep::class],
			], function () {
				Route::match(['GET', 'POST'], '/save/{stepKey}', [WizardController::class, 'save'])->name('save');
				Route::get('/{stepKey}', [WizardController::class, 'step'])->name('index');
			});
		});
	}

}