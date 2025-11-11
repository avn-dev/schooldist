<?php

class Ext_Thebing_Tuition_Pdf_Report {

    /**
     * The report object
     * @var Ext_Thebing_Tuition_Report
     */
	protected $_oReport;

	// The PDF object
	protected $_oPDF;

	/**
	 * The constructor
	 *
	 * @param object $oReport
	 * @param object $oSchool
	 */
	public function __construct($oReport, $oSchool) {
		
		$this->_oReport = $oReport;

		$this->_oPDF = new Ext_Thebing_Pdf_Layout($this->_oReport->layout, $oSchool->getId());

		$this->_oPDF->aCustomData['oTemplateType'] = $this->_oReport->getJoinedObject('layout');

		$backgroundPdf = $this->_oReport->getSchoolSetting($oSchool->getId(), 'background_pdf', 0);

		$this->_oPDF->background_first		= $backgroundPdf;
		$this->_oPDF->background_following	= $backgroundPdf;
		$this->_oPDF->title					= $this->_oReport->title;
	}

	/**
	 * Get the PDF object
	 *
	 * @return Ext_Thebing_Pdf_Layout
	 */
	public function getPDF()
	{
		return $this->_oPDF;
	}
}
