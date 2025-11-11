<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class Classes extends AbstractPage {

	public function render(array $aData = array()) {
		$sTemplate = $this->generatePageHeading($this->oApp->t('My Classes'));

		return $sTemplate;
	}

	public function getStorageData() {

		// @TODO
		$aList = array(
			'items' => array(
				array(
					'title' => 'Class A',
					'html' => '<h3>Class A<h3><p>Mo, Tu, We, Fr; 10:30 – 13:30</p><p>Room A10</p>',
					'items' => array(
						array(
							'html' => '<h3>John Due</h3><p>33, American</p><p>English, B2</p><p>English intensive</p>'
						)
					)
				),
				array(
					'title' => 'Class B',
					'html' => '<h3>Class B<h3><p>Tu, We, Th, Fr; 10:30 – 13:30</p><p>Room A11</p>',
					'items' => array(
						array(
							'html' => '<h3>John Due</h3><p>33, British</p><p>English, B2</p><p>English intensive</p>'
						)
					)
				)
			)
		);

		return $aList;
	}
}