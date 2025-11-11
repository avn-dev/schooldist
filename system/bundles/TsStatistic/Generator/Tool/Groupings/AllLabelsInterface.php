<?php

namespace TsStatistic\Generator\Tool\Groupings;

/**
 * Gruppierungen mit diesem Interface können alle Labels (leere Spalten) anzeigen.
 */
interface AllLabelsInterface {

	public function getAllLabels(): array;

}
