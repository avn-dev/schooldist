<?php

namespace Ts\Gui2\Selection\Special;

abstract class MultipleSchoolsAbstract extends \Ext_Gui2_View_Selection_Abstract {
	
	abstract function getSchoolOptions(\Ext_Thebing_School $school):array;
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		$schools = $oWDBasic->getJoinTableObjects('schools');
		$options = [];

		$addPrefix = false;
		if(count($schools) > 1) {
			$addPrefix = true;
		}		

		foreach($schools as $school) {
			$prefix = '';
			if($addPrefix) {
				$prefix = $school->short.' - ';
			}
			$options += array_map(function($course) use($prefix) { return $prefix.$course;}, $this->getSchoolOptions($school));	
		}
		
		asort($options);

		return $options;		
	}		
	
	
}
