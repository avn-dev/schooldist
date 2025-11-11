<?php

namespace TcFrontend\Traits;

trait WidgetCombinationTrait {

	public function isUsingIframe(): bool {
		return (bool)$this->getCombination()->items_use_iframe;
	}

	public function isUsingBundle(): bool {
		return $this->getCombination()->items_use_css_bundle;
	}

}
