<?php

namespace TsStatistic\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait ColumnConfigTrait {

	// TODO Migrieren auf \Ext_TC_Util::parsePipedString()
	protected function parseConfig(string $config = null) {

		if ($config === null) {
			return;
		}

		$options = explode('|', $config);

		foreach ($options as $option) {
			[$option, $value] = explode(':', $option, 2);
			$option = Str::camel($option);
			$this->{$option} = $value;
		}

	}

	protected function formatConfig(): string {

		$options = collect($this->getConfigurationOptions())->map(function ($config, $option) {
			$option = Str::camel($option);
			return Arr::get($config, 'options.'.$this->{$option});
		})->filter(function ($label) {
			return !empty($label);
		});

		if ($options->isEmpty()) {
			return '';
		}

		return ' ('.$options->join(', ').')';

	}

}