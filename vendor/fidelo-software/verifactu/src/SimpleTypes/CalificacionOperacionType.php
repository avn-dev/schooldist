<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum CalificacionOperacionType: string
{
	/**
	 * OPERACIÓN SUJETA Y NO EXENTA - SIN INVERSIÓN DEL SUJETO PASIVO.
	 */
    case S1 = 'S1';

	/**
	 * OPERACIÓN SUJETA Y NO EXENTA - CON INVERSIÓN DEL SUJETO PASIVO.
	 */
    case S2 = 'S2';

	/**
	 * OPERACIÓN NO SUJETA ARTÍCULO 7, 14, OTROS.
	 */
    case N1 = 'N1';

	/**
	 * OPERACIÓN NO SUJETA POR REGLAS DE LOCALIZACIÓN.
	 */
    case N2 = 'N2';
}
