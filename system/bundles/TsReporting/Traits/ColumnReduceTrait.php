<?php

namespace TsReporting\Traits;

//use TsReporting\Generator\Scopes\Booking\ItemScope;

/**
 * Sollte eine Column durch veränderte Gruppierung mehr Rows liefern (z.B. Items), summiert dieser Trait die Ergebnisse
 */
trait ColumnReduceTrait
{
	public function reduce(array &$carry, array $item): void
	{
		// TODO NEGATE_FACTOR darf nicht auf Items selbst angewendet werden, muss aber bspw. auf Wochen angewendet werden
//		$carry['result'] += $item['result'] * ($item[ItemScope::NEGATE_FACTOR] ?? 1);
		$carry['result'] += $item['result'];
		if (!str_contains($carry['label'], $item['label'])) {
			$carry['label'] .= ', '.$item['label'];
		}
	}
}