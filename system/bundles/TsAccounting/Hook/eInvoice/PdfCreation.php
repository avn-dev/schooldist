<?php

namespace TsAccounting\Hook\eInvoice;

use TcExternalApps\Service\AppService;
use TsAccounting\Service\eInvoice\Spain\ExternalApp\Verifactu as VerifactuExternalApp;
use TsAccounting\Service\eInvoice\Spain\Verifactu;

/**
 * Hook für Print des Verifactu Qr Codes in der Pdf
 *
 */
class PdfCreation extends \Core\Service\Hook\AbstractHook
{
	/**
	 * @param \Ext_Thebing_Pdf_Fpdi $pdf
	 * @param \Ext_Thebing_Inquiry_Document_Version $version
	 * @return void
	 */
	public function run(\Ext_Thebing_Pdf_Fpdi $pdf, \Ext_Thebing_Inquiry_Document_Version $version)
	{
		if (
			AppService::hasApp(VerifactuExternalApp::APP_NAME)
		) {
			$qrcodeVerifactu = Verifactu::getQRCode($version);
			if ($qrcodeVerifactu) {
				$pdf->setPage(1);
				$pdf->SetY(10);
				$pdf->SetX(0);
				$pdf->writeHTML($qrcodeVerifactu);
			}
		}
	}
}