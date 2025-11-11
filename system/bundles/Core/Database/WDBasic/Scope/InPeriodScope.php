<?php

namespace Core\Database\WDBasic\Scope;

use Core\Database\WDBasic\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scope für Datensätze mit:
 *   startColumn >= $startDate
 *   [AND endColumn <= $endDate]
 */
class InPeriodScope implements Scope {
	/** @var string Spalte mit Startdatum */
	protected string $startColumn;

	/** @var string Spalte mit Enddatum */
	protected string $endColumn;

	/**
	 * @param string $startColumn Spaltenname Startdatum
	 * @param string $endColumn   Spaltenname Enddatum
	 */
	public function __construct(string $startColumn, string $endColumn) {
		$this->startColumn = $startColumn;
		$this->endColumn = $endColumn;
	}

	/**
	 * Standard-Eloquent-Anwendung (nicht genutzt).
	 */
	public function apply(\Illuminate\Database\Eloquent\Builder $builder, Model $model): void { }

	/**
	 * Anwendung über WDBasic-Builder (nicht genutzt).
	 */
	public function applyLegacy(Builder $builder, \WDBasic $model): void { }

	/**
	 * Registriert globale Macros für Datumsfilter.
	 */
	public function extend(Builder $builder): void {
		$defaultStartColumn = $this->startColumn;
		$defaultEndColumn = $this->endColumn;

		// Filtert nach Zeitraum (Start Pflicht, Ende optional)
		Builder::macro('inPeriod', function (
			Builder $builder,
			\DateTimeInterface $startDate,
			?\DateTimeInterface $endDate = null,
			?string $startColumn = null,
			?string $endColumn = null
		) use ($defaultStartColumn, $defaultEndColumn) {
			$startCol = $startColumn ?? $defaultStartColumn;
			$endCol = $endColumn ?? $defaultEndColumn;

			$model = $builder->getModel();
			$qualifiedStart = $model->qualifyColumn($startCol);
			$qualifiedEnd = $model->qualifyColumn($endCol);

			$builder->where(function ($query) use ($qualifiedStart, $qualifiedEnd, $startDate, $endDate) {
				if ($endDate === null) {
					$query
						->whereDate($qualifiedStart, '<=', $startDate)
						->whereDate($qualifiedEnd, '>=', $startDate);
				} else {
					$query
						->whereDate($qualifiedStart, '<=', $endDate)
						->whereDate($qualifiedEnd, '>=', $startDate);
				}
			});

			return $builder;
		});
	}
}
