<?php

class Ext_TS_Accounting_Bookingstack_Generator_Factory {

	public function getGenerator(\WDBasic $entity, array $ignoreErrors = []): Ext_TS_Accounting_Bookingstack_Generator {

		if($entity instanceof Ext_Thebing_Inquiry_Payment) {
			$generator = new Ext_TS_Accounting_Bookingstack_Generator_Payment($entity, $ignoreErrors);
		} else if($entity instanceof Ext_Thebing_Inquiry_Document) {
			$generator = new Ext_TS_Accounting_Bookingstack_Generator_Document($entity, $ignoreErrors);
		} else {
			throw new \InvalidArgumentException(sprintf('No generator available for entity "%s(%s)"', get_class($entity), $entity->getId()));
		}

		return $generator;
	}

}
