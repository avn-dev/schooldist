<?php

namespace Core\Command\Routing;

use Core\Command\AbstractCommand;
use Core\Service\RoutingService;
use Core\Traits\Console\WithDebug;
use Illuminate\Foundation\Console\RouteListCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Info extends RouteListCommand {
	use WithDebug;

	protected function configure() {

		$this->setName('core:routing:info')
			->addOption('update', 'u', InputOption::VALUE_NONE, 'Update routes before output')
			->setDescription('Show all routes');

	}

	/**
	 * Gibt den Stack als JSON aus
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);

		if($input->getOption('update')) {
			$oCommand = new Update();
			$oCommand->run(new ArrayInput([]), $output);
		}
		
		// Ein Updateobjekt instanziieren
		$oRoutingService = new RoutingService();
		$oRoutingService->loadRouteCollection();
		
		$oRouteCollection = $oRoutingService->getRouteCollection();

		$routes = [];
		foreach($oRouteCollection->all() as $name => $route) {
			/* @var \Symfony\Component\Routing\Route $route */

			$array = [];
			$array['domain'] = null;#\System::d('domain');
			$array['action'] = implode('@', $route->getDefault('_controller'));
			$array['name'] = $name;
			$array['method'] = implode('|', $route->getMethods());
			$array['uri'] = $route->getPath();

			if (null !== $middlewares = $route->getDefault('_middleware')) {
				$array['middleware'] = $middlewares;
			}

			$routes[] = $array;
		}

		$routes = collect($routes);

		$this->output->writeln($this->forCli($routes));

		/*if(!empty($oRouteCollection)) {
			$iNameMaxLength = 0;
			$iPathMaxLength = 0;
			$iMethodMaxLength = 0;
			foreach($oRouteCollection as $sRouteName=>$oRoute) {
				$iNameMaxLength = max($iNameMaxLength, strlen($sRouteName));
				$iPathMaxLength = max($iPathMaxLength, strlen($oRoute->getPath()));
				$iMethodMaxLength = max($iMethodMaxLength, strlen(implode(', ', $oRoute->getMethods())));
			}

			foreach($oRouteCollection as $sRouteName=>$oRoute) {
				
				$aDefault = $oRoute->getDefaults();
				
				if(is_array($aDefault['_controller'])) {
					$sController = implode('::', $aDefault['_controller']);
				} else {
					$sController = $aDefault['_controller'];
				}

				$aMethods = $oRoute->getMethods();
				if(empty(array_diff(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $aMethods))) {
					$aMethods = ['*'];
				}

				$output->writeln(str_pad($sRouteName, $iNameMaxLength+1).str_pad(implode(', ', $aMethods), $iMethodMaxLength+1).str_pad($oRoute->getPath(), $iPathMaxLength+1).$sController);
			}
		}*/

		return Command::SUCCESS;

	}

	protected function formatActionForCli($route)
	{
		['action' => $action, 'name' => $name] = $route;

		if ($action === 'Closure') {
			return $name;
		}

		$actionClass = explode('@', $action)[0];

		if (class_exists($actionClass) && str_starts_with((new \ReflectionClass($actionClass))->getFilename(), base_path('vendor'))) {
			$actionCollection = collect(explode('\\', $action));

			return $name.$actionCollection->take(2)->implode('\\').'   '.$actionCollection->last();
		}

		return $name.'   '.$action;
	}
	
}
