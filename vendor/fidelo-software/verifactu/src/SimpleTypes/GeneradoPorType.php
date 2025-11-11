<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum GeneradoPorType: string
{
	/**
	 * Expedidor (obligado a Expedir la factura anulada).
	 */
	case E = 'E';

	/**
	 * Destinatario.
	 */
    case D = 'D';

	/**
	 * Tercero.
	 */
    case T = 'T';
}
