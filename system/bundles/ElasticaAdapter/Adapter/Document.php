<?php

namespace ElasticaAdapter\Adapter;

class Document extends \Elastica\Document
{
	public function set(string $key, $value): \Elastica\Document
	{
		if (
			!is_string($value) ||
			$value != '0000-00-00'
		) {
			parent::set($key, $value);
		}

		return $this;
	}

}
