<?php

namespace TsAccounting\Service\eInvoice\Spain;

use FideloSoftware\Verifactu\ComplexTypes\CabeceraType;
use FideloSoftware\Verifactu\ComplexTypes\DesgloseType;
use FideloSoftware\Verifactu\ComplexTypes\DetalleType;
use FideloSoftware\Verifactu\ComplexTypes\IDFacturaExpedidaBajaType;
use FideloSoftware\Verifactu\ComplexTypes\IDFacturaExpedidaType;
use FideloSoftware\Verifactu\ComplexTypes\PersonaFisicaJuridicaESType;
use FideloSoftware\Verifactu\ComplexTypes\PersonaFisicaJuridicaType;
use FideloSoftware\Verifactu\ComplexTypes\RegFactuSistemaFacturacionType;
use FideloSoftware\Verifactu\ComplexTypes\RegistroAlta;
use FideloSoftware\Verifactu\ComplexTypes\RegistroAnulacion;
use FideloSoftware\Verifactu\ComplexTypes\RegistroFacturacionAltaType\EncadenamientoAType;
use FideloSoftware\Verifactu\ComplexTypes\RegistroFacturacionAnulacionType\EncadenamientoAType as EncadenamientoATypeAnulation;
use FideloSoftware\Verifactu\ComplexTypes\RegistroFacturaType;
use FideloSoftware\Verifactu\ComplexTypes\SistemaInformaticoType;
use FideloSoftware\Verifactu\Helper;
use FideloSoftware\Verifactu\SimpleTypes\CalificacionOperacionType;
use FideloSoftware\Verifactu\SimpleTypes\ClaveTipoFacturaType;
use FideloSoftware\Verifactu\SimpleTypes\IdOperacionesTrascendenciaTributariaType;
use FideloSoftware\Verifactu\SimpleTypes\SiNoType;
use FideloSoftware\Verifactu\SimpleTypes\VersionType;
use TsAccounting\Entity\InvoiceRegistrationLog;

class Verifactu {

	private static bool $test = true;

	public static function createDataForCancelInvoice(\Ext_Thebing_Inquiry_Document_Version $version): RegFactuSistemaFacturacionType
	{
		$invoiceData = new RegFactuSistemaFacturacionType();
		$invoiceData->Cabecera = new CabeceraType();
		$accountingCompany = \TsAccounting\Entity\Company::searchByCombination($version->getInquiry()->getSchool(), $version->getInquiry()->getInbox());
		if (is_null($accountingCompany)) {
			throw new \Exception('No company for inquiry '.$version->getInquiry()->id.' found.');
		}
		$invoiceData->Cabecera->ObligadoEmision = new PersonaFisicaJuridicaESType();
		$invoiceData->Cabecera->ObligadoEmision->NIF = $accountingCompany->transmission_tax_number;
		$invoiceData->Cabecera->ObligadoEmision->NombreRazon = $accountingCompany->name;

		$registroFactura = new RegistroFacturaType();
		$registroAnulacion = new RegistroAnulacion();
		$registroFactura->RegistroAnulacion = $registroAnulacion;
		$invoiceData->RegistroFactura[] = $registroFactura;

		$registroAnulacion->IDVersion = VersionType::V1;
		$registroAnulacion->IDFactura = new IDFacturaExpedidaBajaType();
		$registroAnulacion->IDFactura->IDEmisorFacturaAnulada = $invoiceData->Cabecera->ObligadoEmision->NIF;
		$registroAnulacion->IDFactura->NumSerieFacturaAnulada = $version->getDocument()->document_number;
		$registroAnulacion->IDFactura->FechaExpedicionFacturaAnulada = Helper::formatDateTime($version->date);

		$registroAnulacion->Encadenamiento = new EncadenamientoATypeAnulation();
		$registroAnulacion->Encadenamiento->PrimerRegistro = 'S';

		$registroAnulacion->Huella = Helper::getFingerprint($registroAnulacion);
		return $invoiceData;
	}

	public static function cancelInvoice(\Ext_Thebing_Inquiry_Document_Version $version): array
	{
		try {
			return self::sendInvoiceRegistration($version, self::createDataForCancelInvoice($version));
		} catch (\Throwable $e) {
			return [
				'success' => false,
				'retry' => true,
				'message' => $e->getMessage()
			];
		}
	}

	public static function createDataForRegisterInvoice(\Ext_Thebing_Inquiry_Document_Version $version): RegFactuSistemaFacturacionType
	{
		if ($version->getDocument()->isDraft()) {
			throw new \Exception('Document '.$version->getDocument()->document_number.' is a draft.');
		}
		$address = $version->getAddress();
		$addressData = (new \Ext_Thebing_Document_Address($version->getInquiry()))
			->getAddressData($address, new \Tc\Service\Language\Frontend('es'));

		if (empty($addressData)) {
			throw new \Exception('Missing address data for inquiry '. $version->getInquiry()->id);
		}

		$invoiceData = new RegFactuSistemaFacturacionType();
		$invoiceData->Cabecera = new CabeceraType();
		$accountingCompany = \TsAccounting\Entity\Company::searchByCombination($version->getInquiry()->getSchool(), $version->getInquiry()->getInbox());
		if (is_null($accountingCompany)) {
			throw new \Exception('No company found for inquiry '. $version->getInquiry()->id);
		}

		$invoiceData->Cabecera->ObligadoEmision = new PersonaFisicaJuridicaESType();
		$invoiceData->Cabecera->ObligadoEmision->NIF = $accountingCompany->transmission_tax_number;
		$invoiceData->Cabecera->ObligadoEmision->NombreRazon = $accountingCompany->name;

		$registroAlta = new RegistroAlta();
		$registroFactura = new RegistroFacturaType();
		$registroFactura->RegistroAlta = $registroAlta;
		$invoiceData->RegistroFactura[] = $registroFactura;

		$registroAlta->IDVersion = VersionType::V1;
		$registroAlta->IDFactura = new IDFacturaExpedidaType();
		$registroAlta->IDFactura->IDEmisorFactura = $invoiceData->Cabecera->ObligadoEmision->NIF;
		$registroAlta->IDFactura->NumSerieFactura = $version->getDocument()->document_number;
		$registroAlta->IDFactura->FechaExpedicionFactura = Helper::formatDateTime($version->date);

		$registroAlta->NombreRazonEmisor = $invoiceData->Cabecera->ObligadoEmision->NombreRazon;
		if (false) {
			// Vereinfachte Rechnung (zb Quittung ohne Empfänger)
			$registroAlta->TipoFactura = ClaveTipoFacturaType::F2;
		} elseif (false) {
			// Korrektur F2
			$registroAlta->TipoFactura = ClaveTipoFacturaType::R2;
		} elseif (false) {
			// Korrektur F1
			$registroAlta->TipoFactura = ClaveTipoFacturaType::R1;
		} else{
			$registroAlta->TipoFactura = ClaveTipoFacturaType::F1;
		}
		$registroAlta->DescripcionOperacion = 'Factura de venta';
		$destinatario = new PersonaFisicaJuridicaType();
		$destinatario->NIF = $addressData['document_tax_code'];
		$destinatario->NombreRazon = $addressData['document_company'] ??
			$addressData['document_surname'] . ', ' . $addressData['document_firstname'];
		$registroAlta->Destinatarios = [$destinatario];


		foreach ($version->getItemObjects() as $item) {
			$desglose = new DesgloseType();
			$desglose->DetalleDesglose = new DetalleType();
			$desglose->DetalleDesglose->ClaveRegimen = IdOperacionesTrascendenciaTributariaType::T_01;
			$desglose->DetalleDesglose->CalificacionOperacion = CalificacionOperacionType::S1;
			// Tax Rate
			$desglose->DetalleDesglose->TipoImpositivo = $item->tax;
			// Taxable Base or Non-Taxable Amount
			$desglose->DetalleDesglose->BaseImponibleOimporteNoSujeto = $item->getAmount('netto');
			$desglose->DetalleDesglose->CuotaRepercutida = $item->getOnlyTaxAmount();
			$registroAlta->Desglose = [$desglose];
		}

		$registroAlta->CuotaTotal = $version->getOnlyTaxAmount();
		$registroAlta->ImporteTotal = $version->getAmount();
		$registroAlta->Encadenamiento = new EncadenamientoAType();
		$registroAlta->Encadenamiento->PrimerRegistro = 'S';
		$registroAlta->SistemaInformatico = new SistemaInformaticoType();
		$registroAlta->SistemaInformatico->NombreRazon = 'Fidelo Software';
		$registroAlta->SistemaInformatico->NIF = $invoiceData->Cabecera->ObligadoEmision->NIF;
		$registroAlta->SistemaInformatico->NombreSistemaInformatico = 'fidelo-software/verifactu';
		$registroAlta->SistemaInformatico->IdSistemaInformatico = '01';
		$registroAlta->SistemaInformatico->Version = '1.0.0';
		$registroAlta->SistemaInformatico->NumeroInstalacion = $version->getInquiry()->getSchool()->getName();
		$registroAlta->SistemaInformatico->TipoUsoPosibleSoloVerifactu = SiNoType::S;
		$registroAlta->SistemaInformatico->TipoUsoPosibleMultiOT = SiNoType::N;
		$registroAlta->SistemaInformatico->IndicadorMultiplesOT = SiNoType::N;
		$registroAlta->Huella = Helper::getFingerprint($registroAlta);
		return $invoiceData;
	}

	public static function registerInvoice(\Ext_Thebing_Inquiry_Document_Version $version): array
	{
		try {
			return self::sendInvoiceRegistration($version, self::createDataForRegisterInvoice($version));
		} catch (\Throwable $e) {
			return [
				'success' => false,
				'retry' => true,
				'message' => $e->getMessage()
			];
		}
	}

	public static function sendInvoiceRegistration(\Ext_Thebing_Inquiry_Document_Version $version, RegFactuSistemaFacturacionType $invoiceData): array
	{
		$verification_url = '';
		if ($invoiceData->RegistroFactura[0] instanceof RegistroAlta) {
			$verification_url = self::buildVerificationUrl($invoiceData->RegistroFactura[0]);
		}
		$allAccepted = false;
		$verifactuCommSuccess = false;
		$error = '';
		try {
			$officeResult = (new \Licence\Service\Office\Api())->verifactuCall(
				'RegFactuSistemaFacturacion',
				json_encode($invoiceData),
				ExternalApp\Verifactu::getCertificate(),
				ExternalApp\Verifactu::getCertificatePassword(),
				self::$test
			);
			if (
				$officeResult->isSuccessful() // Office hat erfolgreich geantwortet.
			) {
				if ($officeResult->get('success')) { // Verifactu hat erfolgreich geantwortet
					$verifactuResult = $officeResult->get('result');
					// Evaluiere Ergebnis von Verifactu
					$verifactuCommSuccess = true;
					if (
						$verifactuResult &&
						!empty($verifactuResult['responseLines'])
					) {
						// Ergebnis von Verifactu erhalten
						$allAccepted = !in_array(
							false,
							array_map(fn($item) => (bool)$item['accepted'], $verifactuResult['responseLines']),
							true
						);
						if ($allAccepted) {
							$document = $version->getDocument();
							$document->tax_registered = 1;
							$document->save();
						}
					}
				} else $error = 'Verifactu Call Error, check response field';
			} else $error = 'Office Error: '.$officeResult->get('message');
		} catch (\Exception $e) {
			$error = 'Error registering invoice: ' . $e->getMessage();
		}

		$log = new InvoiceRegistrationLog();
		$log->type = 'verifactu';
		$log->document_id = (int)$version->getDocument()->id;
		$log->version_id = $version->id;
		$log->document_number = $version->getDocument()->document_number;
		$log->document_type = $version->getDocument()->type;
		// Operationstyp.
		$log->operation = 'RegFactuSistemaFacturacion';
		// Ob es Testumgebung war.
		$log->test = (int)self::$test;
		// URL um Rechnung beim Amt zu prüfen.
		$log->verification_url = $verification_url;
		// Fehler.
		$log->errors = json_encode($error);
		// Teilweise oder vollständig abgewiesen, muss geprüft werden.
		$log->rejected = !$allAccepted ? 1 : 0;
		// XML Request an Verifactu.
		$log->body = $verifactuResult['response']['request'] ?? '';
		// XML Response von Verifactu.
		$log->response = $verifactuResult['response']['response'] ?? '';
		// Ob erfolgreich mit Verifactu kommuniziert wurde. Bei 0 muss geprüft werden.
		$log->success = $verifactuCommSuccess ? 1 : 0;
		$log->save();

		return [
			'success' => $verifactuCommSuccess,
			'retry' => !$verifactuCommSuccess,
			'message' => $error,
		];
	}

	public static function getQRCode(\Ext_Thebing_Inquiry_Document_Version $version): ?string
	{
		if (
			\TcExternalApps\Service\AppService::hasApp(ExternalApp\Verifactu::APP_NAME) &&
			!$version->getDocument()->isDraft() &&
			$version->getDocument()->isInvoice()
		) {
			return self::getQRImage(self::buildVerificationUrlFromVersion($version));
		}
		return null;
	}

	public static function buildVerificationUrlFromVersion(\Ext_Thebing_Inquiry_Document_Version $version): string
	{
		$invoiceData = self::createDataForRegisterInvoice($version);
		if (!empty($invoiceData->RegistroFactura[0])) {
			return self::buildVerificationUrl($invoiceData->RegistroFactura[0]->RegistroAlta ?? $invoiceData->RegistroFactura[0]->RegistroAnulacion);
		}
		throw new \Exception('Building of verification url failed, no factura.');
	}

	public static function buildVerificationUrl(RegistroAlta|RegistroAnulacion $invoice): string
	{
		$url = self::$test ? 'https://prewww2.aeat.es/' : 'https://www2.agenciatributaria.gob.es/';
		return $url . 'wlpl/TIKE-CONT/ValidarQR?nif=' . rawurlencode($invoice->IDFactura->IDEmisorFactura) . '&numserie=' . rawurlencode($invoice->IDFactura->NumSerieFactura) .
			'&fecha=' . rawurlencode($invoice->IDFactura->FechaExpedicionFactura) . '&importe=' . rawurlencode($invoice->ImporteTotal);
	}

	public static function getQRImage(string $url): string
	{
		$text = "VERI*FACTU";

		$renderer = new \BaconQrCode\Renderer\ImageRenderer(
			new \BaconQrCode\Renderer\RendererStyle\RendererStyle(30, 0),
			new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
		);
		$writer = new \BaconQrCode\Writer($renderer);

		$qrImage = str_replace('</svg>','<text x="0" y="51" font-family="Arial" font-size="10" fill="black">'.$text.'</text>', $writer->writeString($url));
		$qrImage = base64_encode($qrImage);

		$image = new \Ext_Gui2_Html_Image();

		$image->src = 'data:image/svg;base64,@'. $qrImage;
		$htmlText = '<div style="width:30px;">'.$image->generateHTML().'<div style="text-align:left;width:30px;font-size: 3px;">'.$text.'</div></div>';
		return $htmlText;
	}
}