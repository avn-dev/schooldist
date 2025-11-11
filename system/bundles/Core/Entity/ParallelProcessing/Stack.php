<?php

namespace Core\Entity\ParallelProcessing;

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $type
 * @property string $hash
 * @property int $priority
 * @property string $data
 * @property int $user_id
 * @property int $execution_count
 *
 * @method static StackRepository getRepository()
 */
class Stack extends \WDBasic {
	
	protected $_sTable = 'core_parallel_processing_stack';
	protected $_sTableAlias = 'cpps';
	
}
