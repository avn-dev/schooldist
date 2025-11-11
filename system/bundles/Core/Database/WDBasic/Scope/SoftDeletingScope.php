<?php

namespace Core\Database\WDBasic\Scope;

use Core\Database\WDBasic\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Da die Laravel-Traits immer eine Instanz von \Illuminate\Database\Eloquent\Scope brauchen wird hier das Interface benutzt,
 * für die WDBasic wird aber dann applyLegacy() benutzt
 */
class SoftDeletingScope implements Scope {

	public function apply(\Illuminate\Database\Eloquent\Builder $builder, Model $model) {
		// TODO: Implement apply() method.
	}

	public function applyLegacy(Builder $builder, \WDBasic $model) {
		$builder->where($model->qualifyColumn('active'), 1);
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	public function extend($builder) {

		$builder->macro('withTrashed', function ($builder, $withTrashed = true) {
			/* @var $builder Builder */
			if (! $withTrashed) {
				return $builder->withoutTrashed();
			}

			return $builder->withoutGlobalScope(SoftDeletingScope::class);
		});

		$builder->macro('withoutTrashed', function ($builder) {
			/* @var $builder Builder */
			$model = $builder->getModel();
			$builder->withoutGlobalScope(SoftDeletingScope::class)->where($model->qualifyColumn('active'), 1);

			return $builder;
		});

		$builder->macro('onlyTrashed', function ($builder) {
			/* @var $builder Builder */
			$model = $builder->getModel();
			$builder->withoutGlobalScope(SoftDeletingScope::class)->where($model->qualifyColumn('active'), 0);

			return $builder;
		});

	}

}
