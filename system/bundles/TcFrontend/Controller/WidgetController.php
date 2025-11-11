<?php

namespace TcFrontend\Controller;

use Core\Helper\Bundle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TcFrontend\Dto\WidgetPath;
use TcFrontend\Factory\WidgetCombinationFactory;
use TcFrontend\Factory\WidgetPathHashedFactory;
use TcFrontend\Interfaces\WidgetCombination;

class WidgetController extends \Illuminate\Routing\Controller {

	public const ERROR_KEY = 'Invalid key';

	public const ERROR_DOMAIN = 'Invalid domain';

	/**
	 * widget.js
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Response|\Response
	 */
	public function js(Request $request) {

		$file = $this->buildFile($request);

		$response = response($file);
		$response->header('Content-Type', 'text/javascript');
		$response->header('Expires', '0');
		$response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
		$response->header('Pragma', 'no-cache');

		if ($request->has('json')) {
			return $response->header('Content-Type', 'application/json');
		}

		return $response;

	}

	/**
	 * Direkter Aufruf (z.B. src für iframe)
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Response|\Response|\Illuminate\View\View
	 */
	public function app(Request $request) {

		try {
			$combination = WidgetCombinationFactory::createFromRequest($request);
		} catch (ModelNotFoundException $e) {
			return response(\TcFrontend\Controller\WidgetController::ERROR_KEY);
		}

		if (!$combination->getCombination()->validateReferrer($request)) {
			$combination->log('Invalid domain/referrer (app)', [$request->headers->get('referer'), $request->getHost(), $combination->items_domains]);
			return response(self::ERROR_DOMAIN, 400);
		}

		$additional = '';
		try {
			if (!empty($combination->getCombination()->items_template)) {
				$template = \Ext_TC_Frontend_Template::getInstance($combination->getCombination()->items_template);
				$smarty = new \SmartyWrapper();
				$additional .= $smarty->fetch('string:'.$template->code);
			}
		} catch (\Throwable $e) {
			$combination->log('WidgetController::app: Error in Smarty template id '.$combination->getCombination()->items_template, [$e->getMessage()], true);
		}

		// Die Params werden hier und auch in logUsage verwendet
		$params = [
			'iframe' => 1,
			'referrer' => $request->headers->get('referer')
		];

		return view('widget/app', [
			'title' => $combination->getCombination()->name,
			'widgetUrl' => $this->buildWidgetUrl($request, 'js/widget.js', $params),
			'additional' => $additional
		]);

	}

	/**
	 * Dynamische JS-Datei
	 *
	 * @param Request $request
	 * @return string
	 */
	protected function buildFile(Request $request): string {

		$data = $this->getCombinationData($request);

		$json = json_encode($data, JSON_UNESCAPED_SLASHES | (\Util::isDebugIP() ? JSON_PRETTY_PRINT : 0));
		if ($json === false) {
			$json = '{}';
		}

		if ($request->has('json')) {
			return $json;
		}

		$output = 'var __FIDELO__ = ' . $json . ';';

		if (\Util::isDebugIP()) {
			$output .= "\n";
		}

		$path = (new Bundle())->getBundleDirectory('TcFrontend') . '/Resources/assets/js/widget.js';

		if (
			is_file($path) &&
			filesize($path)
		) {
			$output .= file_get_contents($path);
			$output .= '__FIDELO__.initWidget();';
		} else {
			$output .= "console.error('Internal widget error');";
		}

		return $output;

	}

	/**
	 * Combination anhand Key c suchen
	 *
	 * @param Request $request
	 * @return array
	 */
	private function getCombinationData(Request $request): array {

		try {

			$combination = WidgetCombinationFactory::createFromRequest($request);

			if (!$combination->getCombination()->validateReferrer($request)) {
				throw new \DomainException('Invalid referrer for combination');
			}

			$prependPath = WidgetPath::buildPrependPath($request);

			if (

				$prependPath !== null &&
				$combination->isUsingIframe() &&
				!$request->input('iframe', 0)
			) {
				return $this->generateIframeParentData($request, $combination);
			}

			$widgetData = $combination->getWidgetData(true);

			$scripts = $combination->getWidgetScripts();

			// Parent und Child brauchen beide iframe-resizer, um miteinander kommunizieren zu können (Cross-Origin)
			if ($combination->isUsingIframe()) {
				array_unshift($scripts,
					(new WidgetPathHashedFactory('assets/tc-frontend', 'js/iframe-resizer-child.js', 'widget', 'TcFrontend:assets/'))
					->create()
				);
			}

			$data = [
				'paths' => $this->preparePaths($combination->getWidgetPaths(), $prependPath),
				'scripts' => $this->preparePaths($scripts, $prependPath),
				'styles' => $this->preparePaths($combination->getWidgetStyles(), $prependPath),
				'data' => $widgetData
			];

			$combination->getCombination()->updateLastUse();

			// TODO updateLastUse macht einen Query, aber logUsage/save führt WDBasic-Stack aus (min. 3 Querys)
			$combination->logUsage('widget_data', false);


		} catch (ModelNotFoundException $e) {

			$data = ['error' => self::ERROR_KEY];

		} catch (\DomainException $e) {

			$data = ['error' => self::ERROR_DOMAIN];

		} catch (\Throwable $e) {
			dd($e);

			\Log::getLogger('frontend')->error('Error in WidgetCombination: ' . __METHOD__, ['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
			$data = ['error' => 'Internal error'];
			if (\Util::isDebugIP()) {
				throw $e;
			}

		}

		return $data;

	}

	/**
	 * @param Request $request
	 * @param WidgetCombination $combination
	 * @return array
	 */
	private function generateIframeParentData(Request $request, WidgetCombination $combination): array {

		$prependPath = WidgetPath::buildPrependPath($request);

		$scripts = [
			(new WidgetPathHashedFactory('assets/tc-frontend', 'js/iframe-resizer.js', 'widget', 'TcFrontend:assets/'))
				->create()
				->setAdditional(['callback' => '__FIDELO__.initIframeCallback'])
		];

		return [
			'scripts' => $this->preparePaths($scripts, $prependPath),
			'iframe' => [
				// 'type' => 'parent',
				'container' => 'fidelo-widget',
				'id' => 'fidelo-widget-iframe', // iframe-ID wird von iframe-resizer benötigt
				'src' => $this->buildWidgetUrl($request, 'app/widget'),
				'origin' => $request->getSchemeAndHttpHost(),
				'params' => $combination->getWidgetPassParams()
			]
		];

	}

	/**
	 * Widget-Pfade vorbereiten, die für lokal vs. extern umgeschrieben werden müssen
	 *
	 * @param array $paths
	 * @param string|null $prepend
	 * @return array
	 * @see WidgetPath
	 */
	protected function preparePaths(array $paths, string $prepend = null): array {

		return array_map(function ($path) use ($prepend) {

			if (!$path instanceof WidgetPath) {
				// URL direkt angegeben (CDN)
				return $path;
			}

			return $path->buildUrl($prepend);

		}, $paths);

	}

	private function buildWidgetUrl(Request $request, string $ending, array $additional = []): string {

		$path = WidgetPath::buildPrependPath($request);
		if (empty($path)) {
			throw new \UnexpectedValueException('Widget path header is missing.');
		}

		$params = collect($request->query())->except(['q'])->merge($additional);
		$query = http_build_query($params->toArray());

		return sprintf('https://%s/%s/%s?%s', $request->getHost(), $path, $ending, $query);

	}

}