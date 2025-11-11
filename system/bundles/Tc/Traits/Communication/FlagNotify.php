<?php

namespace Tc\Traits\Communication;

/**
 * @deprecated
 */
trait FlagNotify {

	abstract public function setCommunicationFlags(array $saveFlags, array $email, \Ext_TC_Communication_Message $message);

}
