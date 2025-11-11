<?php

namespace Core\Traits\Console;

use Symfony\Component\Console\Output\OutputInterface;

trait WithDebug
{
	protected $bDebugmode = false;

	protected function _setDebug(OutputInterface $output) {

		if($output->isVerbose()) {
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
			ini_set('display_errors', '1');
		}

		if($output->isDebug()) {
			global $system_data;
			$system_data['debugmode'] = 2;
			$this->bDebugmode = true;
		}

	}
}