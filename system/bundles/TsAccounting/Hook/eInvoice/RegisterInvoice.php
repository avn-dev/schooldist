<?php

namespace TsAccounting\Hook\eInvoice;

use Core\Entity\ParallelProcessing\Stack;
use TcExternalApps\Service\AppService;
use TsAccounting\Handler\ParallelProcessing\RegisterInvoice as PPRegisterInvoice;
use TsAccounting\Service\eInvoice\Spain\ExternalApp\Verifactu as VerifactuExternalApp;
use TsAccounting\Service\eInvoice\Spain\Verifactu;

/**
 * Registriert eine Rechnung in Verifactu übers Office
 *
 */
class RegisterInvoice extends \Core\Service\Hook\AbstractHook
{
	public function run(\Ext_Thebing_Inquiry_Document $document)
	{
		if (
			\Ext_Thebing_Client::immutableInvoicesForced() &&
			$document->isInvoice() &&
			AppService::hasApp(VerifactuExternalApp::APP_NAME) &&
			!$document->tax_registered
		) {
			$verifactuResult = Verifactu::registerInvoice($document->getLastVersion());
			if (
				!$verifactuResult ||
				$verifactuResult['retry']
			) {
				// Nicht erfolgreich, in pp übergeben
				$ppData = [
					'type' => 'verifactu',
					'document_id' => $document->id
				];
				Stack::getRepository()->writeToStack('ts-accounting/' . PPRegisterInvoice::TASK_NAME, $ppData, 5);
			}
		}

		if (
			\Ext_Thebing_Client::immutableInvoicesForced() &&
			$document->isInvoice() &&
			!$document->office_registered
		) {
			$hash = $document->getLastVersion()->getFileHash();
			$resultOfficeRegister = (new \Licence\Service\Office\Api())->registerInvoice(\Carbon\Carbon::createFromTimestamp($document->created), $hash, $document->document_number);
			if (
				!$resultOfficeRegister->isSuccessful() ||
				!$resultOfficeRegister->get('success')
			) {
				$ppData = [
					'type' => 'office',
					'document_id' => $document->id
				];
				Stack::getRepository()->writeToStack('ts-accounting/' . \TsAccounting\Handler\ParallelProcessing\RegisterInvoice::TASK_NAME, $ppData, 5);
			} else {
				$document->office_registered = 1;
				$document->save();
			}
		}
	}
}