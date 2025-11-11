<?php

namespace Gui2\Element;

class Checkbox extends AbstractElement {
	
	protected $type = 'checkbox';
	protected $label;
	protected $name;
	protected $value;
	protected $id;
	protected $checked;

	/**
	 * 
	 * @param string $label
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @param bool $checked
	 */
	public function __construct($label, $name, $value='', $id=null, $checked=false) {
		$this->label = $label;
		$this->name = $name;
		$this->value = $value;
		$this->id = $id;
		$this->checked = $checked;
	}
	
	protected function generate(): \Ext_Gui2_Html_Abstract {
		
		$div = new \Ext_Gui2_Html_Div;
		$div->class = $this->type;
		
		$input = new \Ext_Gui2_Html_Input();
		$input->type = $this->type;
		$input->name = $this->name;
		$input->value = $this->value;
		
		if($this->checked) {
			$input->checked = 'checked';
		}
		
		if($this->id) {
			$input->id = $this->id;
		}
		
		$label = new \Ext_Gui2_Html_Label();
		$label->setElement($input);
		$label->setElement($this->label);
		
		$div->setElement($label);
		
		return $div;
	}

}
