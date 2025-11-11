<?php

namespace Core\Service;

use Core\Helper\Routing;
use Core\Service\Routing\ModelBinding;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;

use WDCache;

class RoutingService {

	/**
	 * Neuer Key, damit nach dem Update die Routen neu generiert werden
	 * 
	 * @var string
	 */
	protected $sCacheKey = 'core_routes';
	protected $sCompiledCacheKey = 'core_routes_compiled';

	/**
	 * @var RouteCollection
	 */
	protected $oRouteCollection;
	
	/**
	 * @var string
	 */
	protected $sCompiledRoutes;

	private $aRoutes;
	
	public function __construct() {
		$this->oRouteCollection = new RouteCollection();
	}

	public function buildRoutes() {

		$this->aRoutes = [];

		$this->buildYamlRoutes();
		
		$this->buildLaravelRoutes();

		ksort($this->aRoutes);

		foreach($this->aRoutes as $aRoutes) {
			foreach($aRoutes as $aRoute) {
				$this->addRoute($aRoute['name'], $aRoute['route']);
			}
		}

		WDCache::set($this->sCacheKey, 0, $this->oRouteCollection);
		
		// $compiledRoutes is a plain PHP array that describes all routes in a performant data format
		// you can (and should) cache it, typically by exporting it to a PHP file 
		$this->sCompiledRoutes = (new CompiledUrlMatcherDumper($this->oRouteCollection))->getCompiledRoutes();

		WDCache::set($this->sCompiledCacheKey, 0, $this->sCompiledRoutes);
		
		return true;
	}

	protected function buildYamlRoutes() {
		
		$oFileCollector = new Routing\Yaml\FileCollector();

		$aFiles = $oFileCollector->collectAllFileParts();

		/* @var $oFile File */
		foreach($aFiles as $oFile) {

			$sBundle = $oFile->getBundle();
			
			$aFileRoutes = $oFile->getRoutes();
			
			foreach($aFileRoutes as $sName=>$aFileRoute) {
				$this->getRoutes($sBundle, $sName, $aFileRoute);			
			}

		}

	}

	/**
	 * @see \Core\Helper\Routing\Php\File
	 */
	protected function buildLaravelRoutes() {
		
		$oFileCollector = new Routing\Php\FileCollector();

		$oFileCollector->collectAllFileParts();

	    //$oRouter = \Core\Facade\Routing\Route::getInstance();
        $oRouter = app()->make('router');

		$oRouteCollection = $oRouter->getRoutes();

		/** @var \Illuminate\Routing\Route[] $aRoutes */
		$aRoutes = $oRouteCollection->getRoutes();

		$iPosition = 1;
		foreach($aRoutes as $oRoute) {

			// Routen müssen aus irgendeinem Grund einzigartige Namen haben und das hat vorher dazu geführt, dass jede Route benannt sein musste
			if ($oRoute->getName() === $oRoute->action['bundle'].'.') {
				// preg_match('/([^\/]+)$/', $oRoute->uri(), $aMatches);
				// Hash aus Controller/Methode, damit niemand auf die Idee kommt, diese benannte Route zu verwenden und das deutlich ist
				$oRoute->action['as'] .= substr(md5($oRoute->action['uses']), 0, 16);
			}

			/*
			 * TODO: $oRoute->toSymfonyRoute()
			 */

			$defaults = [
				'_middleware' => $oRoute->middleware()
			];
			
			if(!empty($oRoute->defaults)) {
				$defaults = array_merge($oRoute->defaults);
			}
			
			$this->createRoute($oRoute->action['bundle'], $oRoute->getName(), [ 
				'path' => $oRoute->uri(),
				'controller' => explode('@', $oRoute->action['controller']),
				'schemes' => ['https'],
				'methods' => $oRoute->methods,
				'position' => $iPosition,
//				'host' => '{system_host}',
				'requirements' => $oRoute->wheres,
				'resolve' => $oRoute->bindingFields(),
				'defaults' => $defaults
			]);

			++$iPosition;
		}

	}
	
	/**
	 * @return RouteCollection
	 */
	public function getRouteCollection() {
		return $this->oRouteCollection;
	}
		
	public function loadRouteCollection() {

		$this->oRouteCollection = WDCache::get($this->sCacheKey);

		if($this->oRouteCollection === null) {
			$this->oRouteCollection = new RouteCollection();
			$this->buildRoutes();
		}

	}
	
	public function resetRoutes() {
		WDCache::delete($this->sCompiledCacheKey);
	}
	
	public function loadRoutes() {

		$this->sCompiledRoutes = WDCache::get($this->sCompiledCacheKey);

		if($this->sCompiledRoutes === null) {
			$this->oRouteCollection = new RouteCollection();
			$this->buildRoutes();
		}

	}

	/**
	 * @param string $sName
	 * @return Route
	 */
	public function getRoute($sName) {
		$this->getRouteCollection();
		$oRoute = $this->oRouteCollection->get($sName);
		
		return $oRoute;
	}
	
	/**
	 * @param string $sString
	 * @return string
	 */
	public function buildSlug($sString) {
		// Laravel macht aus ä nur ein einfaches a
		$sString = str_replace(['ä','ö','ü', 'Ä','Ö','Ü'], ['ae','oe','ue', 'Ae','Oe','Ue'], $sString);
		return \Illuminate\Support\Str::slug($sString);
	}
	
	protected function getDynamicRoutes($sBundle, $sName, array $aRoute) {

		$oDispatch = \Factory::getObject($aRoute['controller'][0]);

		$aDynamicRoutes = call_user_func(array($oDispatch, $aRoute['controller'][1]));

		foreach($aDynamicRoutes as $oDynamicRoute) {
			$this->createRoute($sBundle, $oDynamicRoute->getName(), $oDynamicRoute->getArray());
		}

	}
	
	protected function getRoutes($sBundle, $sName, array $aRoute) {

		if(empty($aRoute['position'])) {
			$aRoute['position'] = 0;
		}

		if(isset($aRoute['dynamic'])) {
			$this->getDynamicRoutes($sBundle, $sName, $aRoute);
		} else {
			$this->createRoute($sBundle, $sName, $aRoute);
		}

	}
	
	public function createRoute($sBundle, $sName, array $aRoute) {

		try {
			$aDefaults = [
				'_controller' => $aRoute['controller']
			];

			$oRoute = new Route($aRoute['path'], $aDefaults);

			if(isset($aRoute['requirements'])) {
				$oRoute->setRequirements($aRoute['requirements']);
			}

			if(isset($aRoute['host'])) {

				// Haupt-Domain ersetzen, damit man die in statischen Routen verwenden kann
				$aRoute['host'] = str_replace('{system_host}', \Util::getSystemHost(), $aRoute['host']);

				$oRoute->setHost($aRoute['host']);

			}

			if(isset($aRoute['methods'])) {
				$oRoute->setMethods($aRoute['methods']);
			}

			if(isset($aRoute['schemes'])) {
				$oRoute->setSchemes((array)$aRoute['schemes']);
			}

			if (!empty($aRoute['defaults'])) {
				foreach($aRoute['defaults'] as $sKey => $mValue) {
					$oRoute->setDefault($sKey, $mValue);
				}
			}

			$oCompiledRoute = $oRoute->compile();
			$aVariables = $oCompiledRoute->getVariables();

			$oRoute->setDefault('_variables', $aVariables);

			// Model-Binding
			if(!empty($aVariables)) {
				$sAction = (isset($aRoute['controller'][1])) ? $aRoute['controller'][1] : '__invoke';
				$aResolveBy = (isset($aRoute['resolve'])) ? $aRoute['resolve'] : [];

				$aResolveConfig = ModelBinding::generateConfigForMethod($aRoute['controller'][0], $sAction, $aResolveBy);

				// Mit in die Route schreiben damit wir einen speziellen Flag haben um das Model-Binding auszuführen (damit
				// es nicht immer ausgeführt wird)
				if(!empty($aResolveConfig)) {
					// Nur die Parameter ersetzen die auch in der URL definiert sind
					$oRoute->setDefault('_resolve', array_intersect_key($aResolveConfig, array_flip($aVariables)));
				}
			}

			$oRoute->setOption('bundle', $sBundle);

			if(!isset($aRoute['position'])) {
				$aRoute['position'] = count($this->aRoutes);
			}
			
			// Es kann pro Position mehrere Routen geben
			$this->aRoutes[$aRoute['position']][] = [
				'name' => $sName,
				'route' => $oRoute
			];

		} catch (\Exception $e) {
			// Nur im Debugmodus Exception schmeißen, damit das System nicht komplett kaputt geht
			if(\System::d('debugmode') == 2) {
				throw new \InvalidArgumentException($e->getMessage().' (Route: '.$sName.')');
			}
		}

	}
	
	public function addRoute($sName, $oRoute) {
		$this->oRouteCollection->add($sName, $oRoute);
	}

	public function checkRoute(Request $oRequest, $sPathInfo) {

		$this->loadRoutes();

		$oContext = new RequestContext();
		$oContext->fromRequest($oRequest);

		try {

			$oMatcher = new CompiledUrlMatcher($this->sCompiledRoutes, $oContext);

			$aParameters = $oMatcher->match($sPathInfo);

			return $aParameters;

		} catch (\Exception $e) {
			return null;
		}

	}

	/**
	 * @param string $sName
	 * @param array $aParameters
	 * @param Request $oRequest
	 * @return string
	 */
	public function generateUrl($sName, array $aParameters=[], Request $oRequest = null) {
		
		$this->loadRouteCollection();
		
		$oContext = new RequestContext();
		$oContext->setHost(str_replace('https://', '', \System::d('domain')));
		
		// Url wird auf einen bestehenden Request aufgebaut
		if($oRequest instanceof Request) {
			$oContext->fromRequest($oRequest);
		}

		$oUrlGenerator = new UrlGenerator($this->oRouteCollection, $oContext);

		return $oUrlGenerator->generate($sName, $aParameters);
	}

}
