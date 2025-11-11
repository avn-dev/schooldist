<?php

namespace Gui2\Service\Dumper;

use Gui2\Service\Dumper\ArrayDumper;
use Symfony\Component\Yaml\Dumper as SymfonyDumper;

class YamlDumper {
	
	public function dumpGuiObject(\Ext_Gui2 $oGui) {
		
		$aArray = (new ArrayDumper())
			->dumpGuiObject($oGui);
		
		return (new SymfonyDumper())->dump($aArray, 100);
		
	}
	
}