<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum ClaveTipoRectificativaType: string
{
	/**
	 * SUSTITUTIVA
	 */
    case S = 'S';

	/**
	 * INCREMENTAL
	 */
    case I = 'I';
}
