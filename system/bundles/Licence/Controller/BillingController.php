<?php

namespace Licence\Controller;

class BillingController extends \MVC_Abstract_Controller {

	/**
	 * Zugriffsrecht 
	 */
	protected $_sAccessRight = 'licence_invoices';
	
	public function indexAction() {

		$oDateRange = new \Core\DTO\DateRange(
			(new \DateTime())->modify('last year')->modify('first day of January'), 
			(new \DateTime())->modify('last day of this month')
		);
		
		$oApi = new \Licence\Service\Office\Api();
		
		$aBillings = $oApi->requestBillings($oDateRange->from, $oDateRange->until);

		if(isset($aBillings['billings'])) {
			array_walk(
				$aBillings['billings'],
				function(&$invoice) {
					$payments = explode('{|}', $invoice['payments']);
					$invoice['payments'] = [];
					foreach($payments as $paymentIndex=>&$payment) {
						$paymentInfo = explode('[|]', $payment);
						list($invoice['payments'][$paymentIndex]['created'], $invoice['payments'][$paymentIndex]['amount'], $invoice['payments'][$paymentIndex]['description']) = $paymentInfo;
					} 
				}
			);
		}
		
		$aTransfer['date_range'] = $oDateRange;
		$aTransfer['date_format'] = new \Ext_Gui2_View_Format_Date();
		$aTransfer['datetime_format'] = new \Ext_Gui2_View_Format_Date_DateTime();
		$aTransfer['amount_format'] = new \Ext_Gui2_View_Format_Float();
		$aTransfer['total'] = (isset($aBillings['total'])) ? $aBillings['total'] : 0;
		$aTransfer['billings'] = (isset($aBillings['billings'])) ? $aBillings['billings'] : [];
		$aTransfer['translation_path'] = 'Licence invoices';
		
		return response()->view('billing/list', $aTransfer);
	}
	
	public function pdfAction($id) {

		$oApi = new \Licence\Service\Office\Api();
		
		$aData = $oApi->requestBillingPdf($id);
		
		if(
			!empty($aData['pdf_name']) &&
			!empty($aData['base64'])
		) {
			$sBase64 = base64_decode($aData['base64']);
			
			return response($sBase64, 200)
					->header('Content-type', 'application/pdf')
					->header('Content-Disposition', 'inline; filename="'.$aData['pdf_name'].'")');
		}
		
		return response('File not found', 404);
	}
}

