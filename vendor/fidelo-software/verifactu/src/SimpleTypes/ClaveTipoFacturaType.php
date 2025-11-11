<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum ClaveTipoFacturaType: string
{
	/**
	 * FACTURA (ART. 6, 7.2 Y 7.3 DEL RD 1619/2012).
	 */
    case F1 = 'F1';

	/**
	 * FACTURA SIMPLIFICADA Y FACTURAS SIN IDENTIFICACIÓN DEL DESTINATARIO ART. 6.1.D) RD 1619/2012.
	 */
    case F2 = 'F2';

	/**
	 * FACTURA RECTIFICATIVA (Art 80.1 y 80.2 y error fundado en derecho).
	 */
    case R1 = 'R1';

	/**
	 * FACTURA RECTIFICATIVA (Art. 80.3).
	 */
    case R2 = 'R2';

	/**
	 * FACTURA RECTIFICATIVA (Art. 80.4).
	 */
    case R3 = 'R3';

	/**
	 * FACTURA RECTIFICATIVA (Resto).
	 */
    case R4 = 'R4';

	/**
	 * FACTURA RECTIFICATIVA EN FACTURAS SIMPLIFICADAS.
	 */
    case R5 = 'R5';

	/**
	 * FACTURA EMITIDA EN SUSTITUCIÓN DE FACTURAS SIMPLIFICADAS FACTURADAS Y DECLARADAS
	 */
	case F3 = 'F3';
}
