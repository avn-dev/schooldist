<?php

namespace Cms\Helper\Collector;

class TimeCollector extends \DebugBar\DataCollector\TimeDataCollector {
	
	public function setMeasureStart($name, $fStart, $label = null, $collector = null) {

        $this->startedMeasures[$name] = array(
            'label' => $label ?: $name,
            'start' => $fStart,
            'collector' => $collector
        );

    }
	
}
