<?php

use Core\Helper\Bundle as BundleHelper;
use Core\Service\RoutingService;
use Symfony\Component\HttpFoundation\ParameterBag;

final class MVC_Controller {

	/**
	 * @var DB
	 */
	protected $_oDb;
	
	protected $fStartTime;
	
	/**
	 * Default controller
	 * @var array 
	 */
	protected $_aDefault = array(
		'extension' => 'test',
		'controller' => 'index',
		'action' => 'index'
	);

	/**
	 * Setzt das Request-Objekt
	 * 
	 * @param MVC_Request $oRequest 
	 */
	public function __construct(
		protected \Illuminate\Foundation\Application $oApp,
		protected MVC_Request $_oRequest
	) {}
	
	public function setStartTime($fStartTime) {
		$this->fStartTime = $fStartTime;
	}
	
	/**
	 * @param DB
	 */
	public function setDatabase($oDb) {
		$this->_oDb = $oDb;
	}
		
	/**
	 * @param string $sUrl 
	 * @todo eventuellen Overhead entfernen 
	 */
	public function run($sUrl) {

		$sExtension = $this->_aDefault['extension'];
		$sController = $this->_aDefault['controller'];
		$sAction = $this->_aDefault['action'];
		$aParameter = array();
		$aMiddleware = [];

		System::wd()->executeHook('routing_request', $this->_oRequest);

		// Routes prüfen
		$aRoutingResult = (new RoutingService())->checkRoute($this->_oRequest, '/'.$sUrl);

		// buildControllerExceptionResponse() benutzt die Helper-Methode response() und hier wird 'request' gebraucht
		$this->oApp->instance('request', $this->_oRequest);
		$this->oApp->instance(MVC_Request::class, $this->_oRequest);

		if(!empty($aRoutingResult)) {

			$this->_oRequest->attributes = new ParameterBag($aRoutingResult);

//			$oArgumentResolver = new ArgumentResolver;
//			$aParameter = $oArgumentResolver->getArguments($this->_oRequest, $aRoutingResult['_controller']);
			$aParameter = $aRoutingResult;

			$sControllerClass = ltrim($aRoutingResult['_controller'][0], '\\');
			$sAction = $aRoutingResult['_controller'][1];
			$aMiddleware = $aRoutingResult['_middleware'] ?? [];

			$sExtension = substr($sControllerClass, 0, strpos($sControllerClass, '\\'));
			$sController = substr($sControllerClass, strpos($sControllerClass, '\Controller\\')+ strlen('\Controller\\'));
			$sController = str_replace('Controller', '', $sController);

		} else {

			if(!empty($sUrl)) {

				//$sUrl = rtrim($sUrl, '/');

				$aUrl = explode("/", $sUrl);
				$sExtension = $aUrl[0];
				array_shift($aUrl);

				// Action ist immer der letzte Eintrag
				$sTemp = array_pop($aUrl);
				if(!empty($sTemp)) {
					$sAction = $this->_convertNotation($sTemp);
				}

				$aController = array();
				foreach($aUrl as $sPart) {
					$aController[] = ucfirst($sPart);
				}
				$sController = implode('_', $aController);
			}

			// System-Klasse aufrufen
			if($sExtension != 'system') {
				$sControllerClass = 'Ext_'.ucfirst($sExtension).'_';
			}

			// Nur wenn spezielle Controller-Klasse angegeben
			if(!empty($sController)) {
				$sControllerClass .= ucfirst($sController).'_';
			}

			$sControllerClass .= 'Controller';

			// Neue Bundle Struktur
			if(
				!empty($aUrl) &&
				!class_exists($sControllerClass)
			) {
				
				$sBundleName = $this->_convertNotation($sExtension);

				$sControllerClass = '\\'.$sBundleName.'\\Controller';

				foreach($aUrl as $sPart){
					$sControllerClass .= '\\'.$this->_convertNotation($sPart);
				}

				$sControllerClass .= 'Controller';

				$sAction .= 'Action';

			}

		}

		if(class_exists($sControllerClass)) {

			// Bundle config
			// TODO Soll das Bundle auf Grund des Pfads vom Controller oder von der Route ermittelt werden?
			// TODO Was ist mit anderen Eigenschaften vom Bundle, z.B. der Pfad?
			$this->oApp->singleton(\Core\Helper\BundleConfig::class, function () use ($sControllerClass) {
                $oBundleHelper = new \Core\Helper\Bundle();
                $sBundle = $oBundleHelper->getBundleFromClassName($sControllerClass);
				return new \Core\Helper\BundleConfig($oBundleHelper->getBundleConfigData($sBundle, false));
            });

			if(is_a($sControllerClass, MVC_Abstract_Controller::class, true)) {
				/** @var MVC_Abstract_Controller $oDispatch */
				$oDispatch = new $sControllerClass($sExtension, $sController, $sAction);
			} else {
				/** @var \Illuminate\Routing\Controller $oDispatch */
				$oDispatch = $this->oApp->make($sControllerClass);
//				$aMiddleware = array_merge($aMiddleware, array_map(function($aMiddleware) {
//					return $aMiddleware['middleware'];
//				}, $oDispatch->getMiddleware()));
			}

			$bMethodExists = method_exists($sControllerClass, $sAction);
			$bMethodExistsMagicCall = method_exists($sControllerClass, '__call');

			if (
				$bMethodExists === true || 
				$bMethodExistsMagicCall === true
			) {

				if(!empty($aRoutingResult)) {
					// TODO anders lösen
					$this->oApp->bind('view.finder', function($app) use($sControllerClass) {
						$oBundleHelper = new \Core\Helper\Bundle();
						$sBundle = $oBundleHelper->getBundleFromClassName($sControllerClass);
						$sBundleDir = $oBundleHelper->getBundleDirectory($sBundle, false);
						return new \Core\View\FileViewFinder($app['files'], [$sBundleDir.'/Resources/views']);
					});
				}

				$oResponse = null;

				if($oDispatch instanceof MVC_Abstract_Controller) {
					$oDispatch->setStartTime($this->fStartTime);
					$oDispatch->setRequest($this->_oRequest);
					$oDispatch->setDatabase($this->_oDb);
					$oDispatch->initInterface();
					try {
						$oDispatch->checkAccess();
					} catch (\ErrorException $e) {

						if (System::d('debugmode') == 2) {
							throw $e;
						}

						if (str_contains($e->getMessage(), 'Unauthorized')) {
							$oResponse = response('Unauthorized', 401);
						} else {
							$oResponse = response('Forbidden', 403);
						}
					}

					if (!$oResponse) {
						// TODO Nicht schön, generell sollten die alten Controller mal alle weg und alles über routes.php laufen
						if (!in_array(\Admin\Http\Middleware\HandleHeadRequests::class, $aMiddleware)) {
							$aMiddleware[] = \Admin\Http\Middleware\HandleHeadRequests::class;
						}

						call_user_func([$oDispatch, 'beforeAction'], $sAction);
					}
				}

				if (!$oResponse) {
					try {

						// Model-Binding in dem try-catch ausführen damit die ModelNotFoundException abgefangen wird
						// TODO Mit einem Objekt arbeiten, nicht mit $aParameter
						// TODO eigene Middleware, ist aber wegen $aParameter nicht so einfach
						if (isset($aParameter['_resolve'])) {
							// Model-Binding anhand der Konfiguration aus der Route ausführen
							$aParameter = \Core\Service\Routing\ModelBinding::resolveModelsByConfig($aParameter['_resolve'], $aParameter);
							// attributes setzen damit man in anderen Middlewares auch mit den Objekten arbeiten kann
							$this->_oRequest->attributes = new ParameterBag($aParameter);
						}

						// Eigentliche Route wird immer in der tiefsten Ebene ausgeführt
						$aMiddleware[] = $this->generateDispatchMiddleware($oDispatch, $sAction, $aParameter);

						$oResponse = $this->runMiddleware($aMiddleware);

					} catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
						// abort() mit Response oder direkt geworfen
						$oResponse = $e->getResponse();
					} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
						// abort()
						if ($oDispatch instanceof MVC_Abstract_Controller) {
							throw new \RuntimeException('abort() does not work with old controllers');
						}
						$oResponse = response($e->getMessage(), $e->getStatusCode());

					} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
						$oResponse = response($e->getMessage(), 404);
					} catch (\Throwable $e) {
						$oResponse = $this->buildControllerExceptionResponse($e);
					}

					if ($oDispatch instanceof MVC_Abstract_Controller) {
						call_user_func([$oDispatch, 'afterAction'], $sAction);
					}
				}

				if($oResponse instanceof \Symfony\Component\HttpFoundation\Response) {

					$oResponse->prepare($this->_oRequest);
					$oResponse->send();

					$this->oApp->terminate();
					
				} else {				
					echo $oDispatch->getOutput();
				}
				
				return;
				
			}

		}

		// Wenn kein passender Controller gefunden werden konnte
		$aHookParameter = array(
			'request' => $this->_oRequest,
			'route_found' => false,
			'url' => $sUrl
		);
		
		System::wd()->executeHook('mvc_controller_no_route_found', $aHookParameter);
		
		if($aHookParameter['route_found'] !== true) {
			header("HTTP/1.0 404 Not Found");
		}
		
	}

	/**
	 * Middleware im Laravel-Stil ausführen
	 *
	 * Damit $next korrekt funktioniert (z.B. vorzeitige Response und Aktionen nachher, ohne before/after-Hooks),
	 * müssen alle Middlewares verschachtelt werden. Der eigentliche Controller ist dann die letzte Middleware.
	 *
	 * @TODO Es wäre sinnvoller, direkt über Laravels runRouteWithinStack zu gehen, aber dafür müsste man die Routen umstellen.
	 *   - Im Endeffekt sind das intern auch wieder die SF-Routen, aber Laravel braucht das eigene Route-Objekt.
	 *
	 * @see \Illuminate\Routing\Router::runRouteWithinStack()
	 * @see \Illuminate\Routing\MiddlewareNameResolver::resolve()
	 * @param array $aMiddleware
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	private function runMiddleware(array $aMiddleware) {

		$this->sortMiddleware($aMiddleware);

		$oIterator = (new ArrayObject($aMiddleware))->getIterator();

		$cCreateTreeNode = function($mMiddleware) use($aMiddleware, $oIterator, &$cCreateTreeNode) {

			// Wrapper wird für den Kontext benötigt, da in der Middleware die nächste Middleware unbekannt ist
			$cNext = function($oRequest) use($oIterator, &$cCreateTreeNode) {
				$this->_oRequest = $oRequest;
				$oIterator->next();
				$mNext = $oIterator->current();
				return $cCreateTreeNode($mNext);
			};

			if(is_callable($mMiddleware)) {
				return $mMiddleware($this->_oRequest, $cNext);
			} else {

				// Middleware Params – \Illuminate\Pipeline\Pipeline::parsePipeString()
				[$sMiddleware, $mParams] = array_pad(explode(':', $mMiddleware, 2), 2, []);
				if(is_string($mParams)) {
					$mParams = explode(',', $mParams);
				}

				$oMiddleware = $this->oApp->make($sMiddleware);

				if(method_exists($oMiddleware, 'handle')) {
					array_unshift($mParams, $this->_oRequest, $cNext);
					return $oMiddleware->handle(...$mParams);
				} else {
					throw new RuntimeException('Middleware '.$mMiddleware.' does not have handle()');
				}

			}

		};

		return $cCreateTreeNode($aMiddleware[0]);

	}

	/**
	 * Rudimentäre Implementierung der Middleware-Sortierung von Laravel
	 *
	 * @param $aMiddleware
	 */
	private function sortMiddleware(&$aMiddleware) {

		$aPriorities = [];
		foreach ($aMiddleware as $mMiddleware) {
			if (is_string($mMiddleware)) {
				$sClass = strstr($mMiddleware, ':', true) ?: $mMiddleware;
				if (method_exists($sClass, 'priority')) {
					$oMiddleware = $this->oApp->make($sClass);
					$aPriorities[$mMiddleware] = $oMiddleware->priority();
				}
			}
		}

		if (!empty($aPriorities)) {
			$cGetPrio = function ($sKey) use ($aPriorities) {
				if (is_string($sKey) && isset($aPriorities[$sKey])) {
					return $aPriorities[$sKey];
				}
				return 5;
			};

			usort($aMiddleware, function ($mMiddleware1, $mMiddleware2) use (&$cGetPrio) {
				$iPrio1 = $cGetPrio($mMiddleware1);
				$iPrio2 = $cGetPrio($mMiddleware2);
				if ($iPrio1 === $iPrio2) {
					return 0;
				}
				return $iPrio1 > $iPrio2;
			});
		}

	}

	/**
	 * Eigentliche Route wird immer in der tiefsten Ebene im Middleware-Stack ausgeführt
	 *
	 * @param \Illuminate\Routing\Controller $oDispatch
	 * @param string $sAction
	 * @param array $aParameter
	 * @return Closure
	 */
	private function generateDispatchMiddleware(\Illuminate\Routing\Controller $oDispatch, $sAction, array $aParameter) {

		return function($request, Closure $next) use($oDispatch, $sAction, $aParameter) {

			if(
				$oDispatch instanceof MVC_Abstract_Controller &&
				!method_exists($oDispatch, $sAction)
			) {
				// __call funktioniert nicht mit ReflectionMethod
				// Migration: Route::any()
				// TODO Die Stellen, die das hier noch benutzen, sollten entfernt werden
				//   - Falls in einer Route eine nicht vorhandene Methode angegeben wurde, geht das demnach auch hier durch
				$oResponse = $oDispatch->{$sAction}();
			} else {
				// Workaround für Single-Action-Controllers
				if($sAction === null) {
					$sAction = '__invoke';
				}

				// TODO anstatt $aParameter evtl $request->attributes als Array benutzen

				$oResponse = $this->oApp->call([$oDispatch, $sAction], $aParameter);
			}

			if(
				// Das darf für alte Controller nicht gemacht werden, da die View-Sache dann nicht mehr funktioniert
				!$oDispatch instanceof MVC_Abstract_Controller &&
				!$oResponse instanceof \Symfony\Component\HttpFoundation\Response
			) {
				if ($oResponse instanceof \Illuminate\Contracts\Support\Responsable) {
					$oResponse = $oResponse->toResponse($this->_oRequest);
				} else {
					// Wenn Controller kein Response-Objekt zurückliefert, soll Middleware mit $next nicht abstürzen
					// Außerdem ermöglicht das die Rückgabe von Arrays usw.
					$oResponse = response($oResponse);
				}
			}

			return $oResponse;

		};

	}

	/**
	 * Macht aus dem Pfad mit "-" einen String mit Camel Caps
	 * 
	 * @param string $sInput
	 * @return string
	 */
	protected function _convertNotation($sInput) {
		$oBundleHelper = new BundleHelper();		
		return $oBundleHelper->convertBundleName($sInput);
		
	}

	/**
	 * Exception bei Debug-Modus ausgeben
	 *
	 * @param Throwable $e
	 * @return \Illuminate\Http\Response|Response|null
	 */
	private function buildControllerExceptionResponse(\Throwable $e) {

		$sResponse = \Symfony\Component\HttpFoundation\Response::$statusTexts[500];
		$aHeaders = [];

		$sError = sprintf('%s: %s in %s on line %d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());

		// Fehler auf jeden Fall loggen
		try {
			\Log::getLogger('default', 'framework')->error($sError, [
				'trace' => $e->getTraceAsString(),
				'request' => $this->_oRequest->request->all(),
				'query' => $this->_oRequest->query->all(),
				'headers' => $this->_oRequest->headers->all(),
				'server' => $this->_oRequest->server->all()]
			);
		} catch (\Throwable $e) {

		}

		// Im Debug-Modus außerdem ausgeben
		if(System::d('debugmode') == 2) {
			$sResponse .= "\n".$sError."\n";
			$sResponse .= $e->getTraceAsString();

			if($this->_oRequest->ajax()) {
				$aHeaders['Content-Type'] = 'text/plain';
			}
		}

		// Target [Illuminate\Contracts\Routing\ResponseFactory] is not instantiable.
		// Da alte Controller die Laravel-App nicht laden.
		if(!$this->oApp->has(\Illuminate\Contracts\Routing\ResponseFactory::class)) {
			http_response_code(500);
			echo $sResponse;
			die;
		}

		return response($sResponse, 500, $aHeaders);

	}

}
