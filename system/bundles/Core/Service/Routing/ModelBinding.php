<?php

namespace Core\Service\Routing;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelBinding {

	/**
	 * Liefert alle Parameter eine Methode die eine WDBasic-Instanz erwarten
	 *
	 * @param mixed $class
	 * @param string $method
	 * @return \ReflectionParameter[]
	 */
	public static function getModelParameters($class, string $method): array {

		if(method_exists($class, $method)) {
			return array_filter((new \ReflectionMethod($class, $method))->getParameters(), function (\ReflectionParameter $parameter) {
				$type = $parameter->getType();
				return ($type && is_a($type->getName(), \WDBasic::class, true));
			});
		}

		return [];
	}

	/**
	 * Generiert die Konfiguration für das Model-Binding einer Methode. Über $resolveBy kann die Standardspalte (Primary-Key
	 * der WDBasic) überschrieben werden
	 *
	 * $resolveBy = [
	 * 		'oPost' => 'slug'
	 * ]
	 *
	 * @param $class
	 * @param string $method
	 * @param array $resolveBy
	 * @return array
	 */
	public static function generateConfigForMethod($class, string $method, array $resolveBy = []) {

		// Alle Parameter der Controller-Action die eine WDBasic-Instanzen erwarten
		$modelParameters = self::getModelParameters($class, $method);
		$config = [];

		foreach($modelParameters as $parameter) {
			$variable = $parameter->getName();
			$wdbasicClass = $parameter->getType()->getName();

			$resolve = [];
			$resolve['class'] = $wdbasicClass;
			// Wenn anhand einer anderen Spalte gesucht werden soll
			if(isset($resolveBy[$variable])) {
				$resolve['by'] = $resolveBy[$variable];
			}
			$config[$variable] = $resolve;
		}

		return $config;
	}

	/**
	 * Models in einem Parameter-Array anhand einer Konfiguration ersetzen (siehe generateConfigForMethod()).
	 *
	 * @param array $resolveConfig
	 * @param array $parameters
	 * @return array
	 * @throws ModelNotFoundException
	 */
	public static function resolveModelsByConfig(array $resolveConfig, array $parameters) {

		$intersectParameters = array_intersect_key($resolveConfig, $parameters);

		foreach($intersectParameters as $variable => $modelConfig) {
			// Optionale Parameter beachten
			if ($parameters[$variable] !== null) {
				// Parameter durch Model ersetzen
				$parameters[$variable] = self::resolveModel($modelConfig['class'], $parameters[$variable], $modelConfig['by'] ?? null);
			}
		}

		return $parameters;
	}

	/**
	 * Model-Parameter einer Methode ersetzen. Über $resolveBy kann die Standardspalte (Primary-Key der WDBasic)
	 * überschrieben werden.
	 *
	 * $resolveBy = [
	 * 		'oPost' => 'slug'
	 * ]
	 *
	 * @param $class
	 * @param string $method
	 * @param array $parameters
	 * @param array $resolveBy
	 * @return array
	 */
	public static function resolveModelsForCall($class, string $method, array $parameters, array $resolveBy = []) {

		$modelParameters = self::getModelParameters($class, $method);

		foreach($modelParameters as $parameter) {
			$variable = $parameter->getName();
			$modelClass = $parameter->getType()->getName();

			if(isset($parameters[$variable])) {
				// Parameter durch Model ersetzen
				$parameters[$variable] = self::resolveModel($modelClass, $parameters[$variable], $resolveBy[$variable] ?? null);
			}
		}

		return $parameters;
	}

	/**
	 * Model-Instanze holen
	 *
	 * @param string $modelClass
	 * @param mixed $value
	 * @param string|null $column
	 * @return \WDBasic
	 */
	private static function resolveModel(string $modelClass, $value, string $column = null) {

		/* @var \Core\Database\WDBasic\Builder $query */
		$query = $modelClass::query();

		if($column !== null) {
			// Wenn eine andere Spalte angegeben wurde (z.B. 'slug' => 'hello-world')
			$model = $query->where($column, $value)->first();
		} else {
			// Nach Primary-Key suchen
			$model = $query->find($value);
		}

		if($model === null) {
			dd($modelClass, $value, $column);
			throw new ModelNotFoundException('Model not found!');
		}

		return $model;
	}

}
