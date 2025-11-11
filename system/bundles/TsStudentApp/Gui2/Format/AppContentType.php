<?php

namespace TsStudentApp\Gui2\Format;

use Tc\Service\LanguageAbstract;
use TsStudentApp\Enums\AppContentType as Type;

class AppContentType extends \Ext_Gui2_View_Format_Abstract
{
	public function __construct(private ?LanguageAbstract $l10n = null) {}
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$l10n = $this->l10n ?: $this->oGui->getLanguageObject();
		return Type::from($mValue)->getLabelText($l10n);
	}
}