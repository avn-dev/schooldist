<?php

namespace TsScreen\Service\Elements;

abstract class AbstractElement {

	/**
	 * @var \TsScreen\Entity\Schedule
	 */
	protected $schedule;
	
	/**
	 * @var \Tc\Service\Language\Frontend
	 */
	protected $translator;

	protected $data = [];
	
	final public function __construct(\TsScreen\Entity\Schedule $schedule) {
		
		$this->schedule = $schedule;
//		$this->templateEngine = new \Core\Service\Templating();
		
		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		$language = $school->getInterfaceLanguage();
		
		$this->translator = new \Tc\Service\Language\Frontend($language);
		
//		$this->templateEngine->setLanguage($translator);
		
		$this->prepare();
		
	}
	
	final protected function assign($key, $value) {
		$this->data[$key] = $value;
	}

	abstract public function prepare();
	
	final public function generate() {
		
//		$path = explode('\\', get_class($this));
//		$type = strtolower(array_pop($path));
//		
//		$content = $this->templateEngine->fetch('@TsScreen/'.$type.'.tpl');
		
		return $this->data;
	}
	
}
