<?php

namespace Tc\Entity;

use Core\Traits\WdBasic\MetableTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tc\Interfaces\Events\Settings;

abstract class AbstractManagedEntity extends \Ext_TC_Basic
{
	use MetableTrait;

	public function __set($name, $value)
	{
		if (str_starts_with($name, 'meta_')) {
			$this->setMeta(Str::after($name, 'meta_'), $value);
		} else {
			parent::__set($name, $value);
		}
	}

	public function __get($name)
	{
		if (str_starts_with($name, 'meta_')) {
			return $this->getMeta(Str::after($name, 'meta_'));
		}

		return parent::__get($name);
	}

}