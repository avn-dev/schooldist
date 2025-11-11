<?php

namespace Tc\Database\WDBasic\Scope;

use Core\Database\WDBasic\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TODO das ist alles noch nicht ganz ausgereift
 *
 * Da die Laravel-Traits immer eine Instanz von \Illuminate\Database\Eloquent\Scope brauchen wird hier das Interface benutzt,
 * für die WDBasic wird aber dann applyLegacy() benutzt
 */
class FlexFieldScope implements Scope {

	private static $joinCounter = 1;

	public function apply(\Illuminate\Database\Eloquent\Builder $builder, Model $model) {}
	public function applyLegacy(Builder $builder, \WDBasic $model) {}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	public function extend($builder) {

		/* @var \Ext_TC_Basic $model */
		$model = $builder->getModel();

		// INNER JOIN auf die Values der Flex-Felder
		$joinFlexFields = function ($builder, $flexFieldId) use ($model) {

			$joinAlias = 'flex_'.self::$joinCounter;

			$builder->leftJoin('tc_flex_sections_fields_values as '.$joinAlias, function ($join) use($joinAlias, $flexFieldId, $model) {
				$join->on($joinAlias.'.item_id', '=', $model->qualifyColumn($model->getPrimaryColumn()))
				  	->where($joinAlias.'.field_id', '=', $flexFieldId);

				if (!empty($entityType = $model->getEntityFlexType())) {
					$join->where($joinAlias.'.entity_type', '=', $entityType);
				}
			});
			$builder->groupBy($model->qualifyColumn($model->getPrimaryColumn()));

			self::$joinCounter++;

			return $joinAlias;
		};

		$builder->macro('whereFlexField', function ($builder, $flexFieldId, $operator = null, $value = null, $boolean = 'and') use ($joinFlexFields) {

			if ($flexFieldId instanceof \Closure && is_null($operator)) {

				$flexFieldId($subBuilder = $builder->getModel()->newQuery());
				$subQuery = $subBuilder->getQuery();

				if (!empty($subQuery->joins)) {
					$builder->getQuery()->joins = array_merge((array)$builder->getQuery()->joins, $subQuery->joins);
				}

				$builder->getQuery()->addNestedWhereQuery($subQuery, $boolean);
				$this->addBinding($subQuery->getRawBindings()['join'], 'join');

				return $builder;
			} else {
				$joinAlias = $joinFlexFields($builder, $flexFieldId);
				return $builder->where($joinAlias.'.value', $operator, $value, $boolean);
			}

		});

		$builder->macro('orWhereFlexField', function ($builder, $flexFieldId, $operator = null, $value = null) use ($joinFlexFields) {
			$joinAlias = $joinFlexFields($builder, $flexFieldId);
			return $builder->orWhere($joinAlias.'.value', $operator, $value);
		});

		$builder->macro('whereFlexFieldIn', function ($builder, $flexFieldId, $values, $boolean = 'and', $not = false) use ($joinFlexFields) {
			$joinAlias = $joinFlexFields($builder, $flexFieldId);
			return $builder->whereIn($joinAlias.'.value', $values, $boolean, $not);
		});

		$builder->macro('orWhereFlexFieldIn', function ($builder, $flexFieldId, $values) use ($joinFlexFields) {
			$joinAlias = $joinFlexFields($builder, $flexFieldId);
			return $builder->orWhereIn($joinAlias.'.value', $values);
		});
	}

}
