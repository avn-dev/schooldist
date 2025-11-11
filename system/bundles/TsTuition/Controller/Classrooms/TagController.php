<?php

namespace TsTuition\Controller\Classrooms;

class TagController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'thebing_tuition_resource_classrooms';

	public function getTags() {
		
		$tags = \DB::getQueryRows("SELECT `id` `value`, `tag` `text` FROM `ts_classrooms_tags` WHERE `active` = 1 ORDER BY `tag` ASC");
		
		return response()->json($tags);		
	}
	
}
