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
 * @property string $error_data
 * @property int $execution_count
 */
class ErrorStack extends \WDBasic {
	
	protected $_sTable = 'core_parallel_processing_stack_error';
	protected $_sTableAlias = 'cppse';
	
}
