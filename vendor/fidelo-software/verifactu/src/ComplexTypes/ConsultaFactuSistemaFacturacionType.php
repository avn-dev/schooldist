<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

class ConsultaFactuSistemaFacturacionType
{
	public ?CabeceraType $Cabecera = null;

	public ?LRFiltroRegFacturacionType $FiltroConsulta = null;

	public ?DatosAdicionalesRespuestaType $DatosAdicionalesRespuesta = null;
}
