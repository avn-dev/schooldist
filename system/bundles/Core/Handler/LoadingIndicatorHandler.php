<?php

namespace Core\Handler;

abstract class LoadingIndicatorHandler {

	/**
	 * @param array $aIds
	 * @return array
	 */
	abstract public function getStatus(array $aIds);

	/**
	 * Liefert das Mapping von Status zu Icon-Datei
	 *
	 * @return array
	 */
	public function getIcons() {

		return array(
			'ready' => 'fa-check',
			'pending' => 'fa-spinner fa-pulse',
			'fail' => 'fa-exclamation-triangle'
		);

	}

}