<?php

namespace Tc\Traits\Placeholder;

interface ReplaceInterface {

	public function setPlaceholder(array $placeholder);

	public function replace(\Ext_TC_Basic $object, \Ext_TC_Basic $parent = null): string;

	public function isModifierAware(): bool;

}
