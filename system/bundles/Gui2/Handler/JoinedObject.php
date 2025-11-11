<?php

namespace Gui2\Handler;

abstract class JoinedObject {
	
	abstract public function get(\WDBasic $entity): iterable;

	abstract public function delete(\WDBasic $entity, \WDBasic $child);

	abstract public function save(\WDBasic $entity, \WDBasic $child);

	abstract public function validate(\WDBasic $entity, \WDBasic $child): mixed;
	
}
