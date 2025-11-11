<?php

namespace Ts\Traits;

trait EntityCommunication {
	
	/**
	 * $relations = [
	 *	[
	 *		'relation' => CLASSNAME, 
	 *		'relation_id' => 1
	 *	]
	 * ];
	 */
	abstract public function setAdditionalMessageRelations(array &$relations);
	
}
