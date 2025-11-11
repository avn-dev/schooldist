<?php


/*
 * -- webDynamics pdf classes --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/wdpdf/wdpdf.php
 *
 * 
 */


class wdPDF_FPDI extends wdPDF_TCPDF {

	/**
	 * The standard fonts
	 */
	protected $_aFontsChecklist = array(
		'courier', 'helvetica', 'arial', 'times', 'symbol', 'zapfdingbats'
	);

}
