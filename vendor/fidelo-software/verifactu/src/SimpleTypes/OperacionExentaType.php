<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum OperacionExentaType: string
{
	/**
	 * EXENTA por Art. 20
	 */
    case E1 = 'E1';

	/**
	 * EXENTA por Art. 21
	 */
    case E2 = 'E2';

	/**
	 * EXENTA por Art. 22
	 */
    case E3 = 'E3';

	/**
	 * EXENTA por Art. 24
	 */
    case E4 = 'E4';

	/**
	 * EXENTA por Art. 25
	 */
    case E5 = 'E5';

	/**
	 * EXENTA otros
	 */
    case E6 = 'E6';
}
