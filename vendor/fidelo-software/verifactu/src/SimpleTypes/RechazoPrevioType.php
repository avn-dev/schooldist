<?php

namespace FideloSoftware\Verifactu\SimpleTypes;

enum RechazoPrevioType: string
{

	/**
	 * No ha habido rechazo previo por la AEAT.
	 */
    case N = 'N';

	/**
	 * Ha habido rechazo previo por la AEAT.
	 */
    case S = 'S';

	/**
	 * Independientemente de si ha habido o no algún rechazo previo por la AEAT, el registro de facturación no existe en la AEAT (registro existente en ese SIF o en algún SIF del obligado tributario y que no se remitió a la AEAT, por ejemplo, al acogerse a Veri*factu desde no Veri*factu). No deberían existir operaciones de alta (N,X), por lo que no se admiten.
	 */
    case X = 'X';
}
