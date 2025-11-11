<?php

namespace Tc\Interfaces\Events;

interface EntityDispatcher {

	/**
	 * Liefert die Entität, welches von dem Event betroffen ist
	 *
	 * @return \WDBasic
	 */
	public function getEntity(): \WDBasic;

	public function getEntitySubscriptionNotification();

}
