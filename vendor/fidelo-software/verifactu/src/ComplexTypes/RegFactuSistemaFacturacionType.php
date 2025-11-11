<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

class RegFactuSistemaFacturacionType
{
	public ?CabeceraType $Cabecera = null;

	/**
	 * @var RegistroFacturaType[]|null
	 */
	public ?array $RegistroFactura = null;
}


