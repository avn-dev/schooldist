<?php

namespace Gui2\Element;

/**
 * @see \Gui2\Element\Expandable
 */
class Box extends AbstractElement {

	/**
	 * @var string
	 */
	protected $title = null;

	protected $collapsed = null;

	protected $headlineTitle = true;

	protected $toolsElements = [];

	/**
	 * @param string $sId
	 */
	public function __construct($title=null, $collapsed=null) {
		$this->title = $title;
		$this->collapsed = $collapsed;
	}

	/**
	 * @param bool $headlineTitle
	 * @return $this
	 */
	public function setHeadlineTitle(bool $headlineTitle) {
		$this->headlineTitle = $headlineTitle;
		return $this;
	}

	public function addToolsElement($element) {
		$this->toolsElements[] = $element;
	}

	/**
	 * @return Ext_Gui2_Html_Div
	 */
	protected function generate(): \Ext_Gui2_Html_Div {

		$oBox = new \Ext_Gui2_Html_Div();
		$oBox->class = "box box-default";

		if($this->collapsed === true) {
			$oBox->class = "collapsed-box";
		}

		$oBox->setElement('<div class="box-separator"></div>');

		if($this->title !== null) {
			$oBoxHeader = new \Ext_Gui2_Html_Div();
			$oBoxHeader->class = 'box-header with-border';
			if($this->collapsed !== null) {
				$oBoxHeader->setDataAttribute('widget', 'collapse');
			}

			if($this->headlineTitle) {
				$oBoxHeader->setElement('<h3 class="box-title">'.$this->title.'</h3>');
			} else {
				$oBoxHeader->setElement($this->title);
			}
			
			if($this->collapsed === true) {
				$this->addToolsElement('<button type="button" data-widget="collapse"><i class="fa fa-plus"></i></button>');
			} elseif($this->collapsed === false) {
				$this->addToolsElement('<button type="button" data-widget="collapse"><i class="fa fa-minus"></i></button>');
			}


			if(!empty($this->toolsElements)) {
				$toolsContainer = new \Ext_Gui2_Html_Div();
				$toolsContainer->class = 'box-tools pull-right';
				foreach($this->toolsElements as $toolsElement) {
					$toolsContainer->setElement($toolsElement);
				}
				$oBoxHeader->setElement($toolsContainer);
			}

			$oBox->setElement($oBoxHeader);
		}
		
		if(!empty($this->aElements)) {
			$oBoxBody = new \Ext_Gui2_Html_Div();
			$oBoxBody->class = 'box-body';

			if($this->collapsed === true) {
				$oBoxBody->style = 'display:none;';
			}

			foreach($this->aElements as $iKey => $oElement) {
				$oBoxBody->setElement($oElement);
			}

			$oBox->setElement($oBoxBody);
		}
		
		return $oBox;
	}

}
