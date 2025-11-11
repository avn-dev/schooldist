<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum PersonaFisicaJuridicaIDTypeType: string
{
	/**
	 * NIF-IVA.
	 */
    case T_02 = '02';

	/**
	 * Pasaporte.
	 */
    case T_03 = '03';

	/**
	 * IDEnPaisResidencia.
	 */
    case T_04 = '04';

	/**
	 * Certificado Residencia.
	 */
    case T_05 = '05';

	/**
	 * Otro documento Probatorio.
	 */
    case T_06 = '06';

	/**
	 * No Censado.
	 */
    case T_07 = '07';
}
