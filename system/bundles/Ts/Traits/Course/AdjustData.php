<?php

namespace Ts\Traits\Course;

trait AdjustData {

	public function adjustData() {

		$oCourse = $this->getCourse();

		// Prüfungen immer nachträglich korrigieren, da es keine Möglichkeit gibt, das vor validate/save (EVENTS!) zu machen
		if ($oCourse->getType() === 'exam') {
			$this->weeks = 1;
			$this->until = $this->from;
			$this->units = 1;
		}

		// Zeitraum des Programms in from und until setzen
		if($oCourse->isProgram()) {
			$oProgram = $this->getProgram();
			if (
				null !== ($oFrom = $oProgram->getFrom()) &&
				null !== ($oUntil = $oProgram->getUntil())
			) {
				$this->weeks = $oProgram->getWeeks();
				$this->from = $oFrom->toDateString();
				$this->until = $oUntil->toDateString();
			}
		}

	}

}
