<?php

namespace TsStatistic\Service;

use Carbon\Carbon;
use Spatie\Period\Period;

class NightCalculcator {

	private Carbon $from;

	private Carbon $until;

	public function __construct(Carbon $from, Carbon $until) {
		$this->from = $from;
		$this->until = $until;
	}

	public function calculate(Carbon $from, Carbon $until, bool $invert = false): int {

		$until = $until->copy()->setTime(23, 59, 59);

		$period1 = Period::make($this->from, $this->until);
		$period2 = Period::make($from, $until);

		$periodOverlap = $period1->overlap($period2);
		if ($periodOverlap === null) {
			return 0;
		}

		$amount = $periodOverlap->length(); // Tage
		$nightCorrection = -1; // Siehe unten

		// Wenn der Betrag negativ ist, müssen die Nächte auch negativ sein, damit diese sich aufheben (Diffs)
		if ($invert) {
			$amount *= -1;
			$nightCorrection = 1; // Hier muss dann eine Nacht ergänzt statt abgezogen werden
		}

		// Wenn nicht der komplette Zeitraum reinfällt, würde je nach Anzahl der Zeiträume pro Zeitraum immer eine Nacht fehlen.
		// overlapSingle zählt Tage, nicht Nächte, daher muss beim Enddatum der letzte Tag, der zu viel wäre, abgezogen werden.
		// Je nachdem, ob der Betrag positiv oder negativ ist, muss eine Subtraktion oder Addition erfolgen ($nightCorrection).
		// Beispiel: Acc 5W+1EN 15.08.2020–20.09.2020 = 36 Nächte
		//   15.08.2020–31.08.2020: 17 Nächte (statt 16), 01.09.2020–30.09.2020: 19 Nächte (statt 20) = 36 Nächte
		//   15.08.2020–20.08.2020: 6 Nächte (statt 5), 21.08.2020–31.08.2020: 11 Nächte (statt 10), 01.09.2020–30.09.2020: 19 Nächte (statt 20) = 36 Nächte
		if ($period1->contains($until)) {
			$amount += $nightCorrection;
		}

		return $amount;

	}

}