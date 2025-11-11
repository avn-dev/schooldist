<?php

namespace Gui2\Service;

class CustomiseDialog {
	
	/**
	 * @var \Ext_Gui2_Dialog 
	 */
	private $dialog;

	/**
	 * @var \Ext_Gui2
	 */
	private $gui2;

	public function __construct(\Ext_Gui2_Dialog $dialog, \Ext_Gui2 $gui2) {
		$this->dialog = $dialog;
		$this->gui2 = $gui2;
	}
	
	public function getCustomiseDialog() {

		$customiseDialog = $this->gui2->createDialog($this->gui2->t('Dialog anpassen'), $this->gui2->t('Dialog anpassen'));

		foreach($this->dialog->aElements as $tab) {
			$customiseTab = $customiseDialog->createTab($tab->sTitle);
			
			foreach($tab->aElements as $element) {
				if($element instanceof \Ext_Gui2) {
					$customiseTab->setElement($customiseDialog->createNotification('Dieser Tab kann nicht angepasst werden!'));
					break;
				} elseif($element instanceof \Ext_Gui2_Html_Abstract) {
					$customiseTab->setElement((new \Ext_Gui2_Html_Div)->setElement((string)'Html '.$element->getLabel()));
				} else {
					$customiseTab->setElement((new \Ext_Gui2_Html_Div)->setElement((string)'Element '.get_class($element)));
				}
			}

			$customiseDialog->setElement($customiseTab);
		}

		return $customiseDialog;
	}

}
