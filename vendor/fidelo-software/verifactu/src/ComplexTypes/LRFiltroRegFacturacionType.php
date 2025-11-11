<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

class LRFiltroRegFacturacionType
{
	public ?PeriodoImputacionType $PeriodoImputacionType = null;

	public ?string $NumSerieFactura = null;

	public ?ContraparteConsultaType $Contraparte = null;

	public ?FechaExpedicionConsultaType $FechaExpedicionConsulta = null;

	public ?SistemaInformaticoType $SistemaInformaticoType = null;

	public ?string $RefExterna = null;

	public ?IDFacturaExpedidaBCType $ClavePaginacion = null;
}

