<?php

class Ext_TS_Pdf_Template extends Ext_TC_Pdf_Template {
	
	public static function getPdfPlaceholderObject($sType) {

		switch($sType) {
			case 'document_attendance':
				return ['class' => 'Ext_Thebing_Tuition_Attendance_Document'];
			case 'document_invoice_customer':
			case 'document_invoice_agency':
			case 'document_invoice_storno':
			case 'document_invoice_credit':
			case 'document_loa':
			case 'document_studentrecord_additional_pdf':
			case 'document_studentrecord_visum_pdf':
				return ['class' => 'Ext_Thebing_Inquiry_Document_Version'];
			case 'document_course':
				return ['class' => 'Ext_Thebing_Tuition_Course'];
			case 'document_teacher':
				return ['class' => \Ext_Thebing_Teacher::class];
			#case 'document_job_opportunity':
				#return ['class'	=> \TsCompany\Service\Placeholder\JobOpportunity\StudentAllocation::class];
		}
		
	}
	
}
