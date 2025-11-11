<?php

namespace TsTuition\Handler\ParallelProcessing;

use TsTuition\Service\BlockCancellationService;
use Core\Handler\ParallelProcessing\TypeHandler;

class BlockCancellation extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Unterrichtseinheit: Kursausfall', 'School');
	}

	public function execute(array $data, $debug = false)
	{
		$block = \Ext_Thebing_School_Tuition_Block::getInstance($data['id']);

		if (!$block->exist() || !$block->isActive()) {
			return;
		}

		(new BlockCancellationService($block))->update();
	}
}