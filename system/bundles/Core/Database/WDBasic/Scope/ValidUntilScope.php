<?php

namespace Core\Database\WDBasic\Scope;

use Core\Database\WDBasic\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Da die Laravel-Traits immer eine Instanz von \Illuminate\Database\Eloquent\Scope brauchen wird hier das Interface benutzt,
 * für die WDBasic wird aber dann applyLegacy() benutzt
 */
class ValidUntilScope implements Scope {

	public function apply(\Illuminate\Database\Eloquent\Builder $builder, Model $model) {}

	public function applyLegacy(Builder $builder, \WDBasic $model) {}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	public function extend($builder) {

		$builder->macro('onlyValid', function ($builder, \DateTime $date = null) {

			$column = $builder->getModel()->qualifyColumn('valid_until');

			$builder->where(function ($query) use ($column, $date) {

				if ($date === null) {
					$query->whereRaw($column.' >= CURDATE()');
				} else {
					$query->whereDate($column, '>=', $date);
				}

				$query->orWhere($column, '0000-00-00');
				$query->orWhereNull($column);
			});

			return $builder;
		});

	}

}
