<?php

namespace Cms\Model\DynamicRouting;

class Node implements \IteratorAggregate {
	
	private $placeholders = [];
	
	public function setPlaceholder($placeholder, $key, $slug, $label) {
		$this->placeholders[$placeholder] = [
			'key' => $key, 
			'slug' => $slug, 
			'label' => $label
		];
	}
	
	public function removePlaceholder($placeholder) {
		if(isset($this->placeholders[$placeholder])) {
			unset($this->placeholders[$placeholder]);
		}
	}
	
	public function getIterator() {
        return new \ArrayIterator($this->placeholders);
    }
	
	public function checkTemplate($link) {
		
		$matches = [];
		
		// Werte für alle Platzhalter da?
		preg_match_all('/\{(.*?)\}/', $link, $matches);
		
		if(
			count(array_diff($matches[1], array_keys($this->placeholders))) === 0
		) {
			return true;
		}

		return false;
	}
	
}
