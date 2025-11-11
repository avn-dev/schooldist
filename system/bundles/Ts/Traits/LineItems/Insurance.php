<?php

namespace Ts\Traits\LineItems;

trait Insurance {
	
	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {

		$oInsurance = $this->getInsurance();

		$oSmarty->assign('insurance', $oInsurance->getName($oLanguage->getLanguage()));
		$oSmarty->assign('from', \Ext_Thebing_Format::LocalDate($this->from, $this->getSchool()->id));
		$oSmarty->assign('until', \Ext_Thebing_Format::LocalDate($this->getUntil(), $this->getSchool()->id));

	}

}
