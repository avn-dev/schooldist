<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum ImpuestoType: string
{
	/**
	 * Impuesto sobre el Valor Añadido (IVA)
	 */
    case T_01 = '01';

	/**
	 * Impuesto sobre la Producción, los Servicios y la Importación (IPSI) de Ceuta y Melilla
	 */
    case T_02 = '02';

	/**
	 * Impuesto General Indirecto Canario (IGIC)
	 */
    case T_03 = '03';

	/**
	 * Otros
	 */
    case T_05 = '05';
}
