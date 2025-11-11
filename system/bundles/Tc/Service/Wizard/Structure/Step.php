<?php

namespace Tc\Service\Wizard\Structure;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Interfaces\Wizard\Log;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Sitemap\SitemapHtml;

abstract class Step extends AbstractElement
{
	private ?Log $log = null;

	protected array $helpTexts = [];

	public function setHelpTexts(array $texts): static
	{
		$this->helpTexts = array_merge($this->helpTexts, $texts);
		return $this;
	}

	public function getHelpText(string $key, $default = null)
	{
		return $this->helpTexts[$key] ?? $default;
	}

	public function setLog(Log $log): static
	{
		$this->log = $log;
		return $this;
	}

	public function getLog(): ?Log
	{
		return $this->log;
	}

	abstract public function render(Wizard $wizard, Request $request): Response;

	/**
	 * Step anzeigen
	 *
	 * @param Wizard $wizard
	 * @param string $template
	 * @param array $templateVars
	 * @return \Illuminate\Http\Response
	 */
	final protected function view(Wizard $wizard, string $template, array $templateVars = []): \Illuminate\Http\Response
	{
		$uri = new \GuzzleHttp\Psr7\Uri($wizard->routeStep($this, 'step.save'));

		$access = $wizard->getAccess();

		$helpTextMode = false;
		if (
			$access && $access->hasRight('info_texts') &&
			$wizard->getSession()->get('system_infotexts_mode') === true
		) {
			$helpTextMode = true;
		}

		if ($helpTextMode) {
			$templateVars['helpTextLanguages'] = \System::getBackendLanguages();
			$templateVars['helpTextMode'] = true;
		}

		$templateVars['wizard'] = $wizard;
		$templateVars['step'] = $this;
		$templateVars['saveUrl'] = (string)$uri;
		$templateVars['backUrl'] = (string)\GuzzleHttp\Psr7\Uri::withQueryValue($uri, 'action', 'back');
		$templateVars['sitemap'] = new SitemapHtml($wizard);

		if (!isset($templateVars['title'])) {
			$templateVars['title'] = $this->getTitle($wizard);
		}

		return response()
			->view($template, $templateVars);
	}

	/**
	 * Step abschließen (zum Ableiten)
	 *
	 * @param Wizard $wizard
	 * @param Request $request
	 * @return MessageBag|null
	 */
	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		return null;
	}

	/**
	 * Weiterleitung auf den nächsten Step
	 *
	 * @param Wizard $wizard
	 * @param Request $request
	 * @param $next
	 * @return Response
	 */
	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $next($request);
	}

	/**
	 * Step speichern
	 *
	 * @param Wizard $wizard
	 * @param Request $request
	 * @param $next
	 * @return Response
	 */
	final protected function finish(Wizard $wizard, Request $request, $next): Response
	{
		$messageBag = $this->save($wizard, $request);

		if (
			$messageBag instanceof MessageBag &&
			$messageBag->isNotEmpty()
		) {
			foreach ($messageBag->messages() as $message) {
				$wizard->message('error', $message);
			}

			$wizard->getSession()->getFlashBag()->set('old_input', $request->input());
			return back();
		}

		if ($this->log !== null) {
			$wizard->writeLog($this, $this->log->finish());
		}

		// Keine Fehler - auf nächsten Step weiterleiten
		return $next($request);
	}


	/**
	 * Bestimmte Aktion auf den Step ausführen
	 *
	 * @param Wizard $wizard
	 * @param string $action
	 * @param Request $request
	 * @param $next
	 * @return Response
	 */
	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		return match ($action) {
			'back' => $wizard->previous(),
			'save' => $this->finish($wizard, $request, fn () => $this->next($wizard, $request, $next)),
			'save_exit' => $this->finish($wizard, $request, fn () => $wizard->redirectToHome()),
			default => $next($request),
		};
	}

	/**
	 * Prozessstatus ermitteln [erledigt, Anzahl aller Steps]
	 *
	 * @param array $finishedLogs
	 * @param array $queryParameters
	 * @return int[]
	 */
	final public function getIterationStatus(array $finishedLogs, array $queryParameters = []): array
	{
		if (empty($queryParameters)) {
			$done = Arr::first($finishedLogs, fn (Log $log) => $log->getStepKey() === $this->getKey());
		} else {
			// Alle Logs zu diesem Eintrag holen
			$logs = array_filter($finishedLogs, fn (Log $log) => $log->getStepKey() === $this->getKey());
			$done = null;

			/* @var Log[] $logs */
			foreach ($logs as $log) {
				$logQueryParameters = $log->getQueryParameters();
				$match = true;
				foreach ($queryParameters as $parameter => $value) {
					if (
						!isset($logQueryParameters[$parameter]) ||
						$logQueryParameters[$parameter] !== $value
					) {
						$match = false;
						break;
					}
				}

				if ($match)  {
					$done = $log;
					break;
				}
			}
		}

		$status = [0, 1];
		if ($done !== null) {
			$status = [1, 1];
		}

		return $status;
	}

	/**
	 * Generiert ein Step-Objekt anhand eines Arrays
	 *
	 * @param array $config
	 * @param string $key
	 * @return Step
	 */
	public static function fromArray(Wizard $wizard, array $config, string $key = '', array $queryParameters = []): Step
	{
		if (!empty($missing = array_diff(['class', 'title'], array_keys($config)))) {
			throw new \RuntimeException('Missing step configuration ['.implode(', ', $missing).']');
		}

		/* @var Step $step */
		$step = app()->make($config['class'])
			->key($key)
			->config(Arr::except($config, ['class','elements']));

		foreach ($queryParameters as $query => $value) {
			$step->query($query, $value);
		}

		Wizard\Structure::runConditions($wizard, $step);

		return $step;
	}
}