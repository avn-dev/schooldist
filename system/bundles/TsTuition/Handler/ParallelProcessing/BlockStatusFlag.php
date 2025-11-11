<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TsTuition\Service\BlockStatusService;

class BlockStatusFlag extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Klassenplanung: Block Status', 'School');
	}

	public function execute(array $data, $debug = false)
	{
		$block = \Ext_Thebing_School_Tuition_Block::getInstance($data['id']);

		if (!$block->exist() || !$block->isActive()) {
			return;
		}

		(new BlockStatusService($block))->update();
	}
}