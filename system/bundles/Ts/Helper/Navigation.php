<?php

namespace Ts\Helper;

class Navigation extends \Admin\Helper\Navigation
{
	public function getCacheKey()
	{
		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		// Bei All-Schools ist $oSchool->id = 0
		return parent::getCacheKey().'_'.$oSchool->id;
	}
}