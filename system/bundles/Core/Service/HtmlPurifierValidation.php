<?php

namespace Core\Service;

use HTMLPurifier_AttrDef_URI;

class HtmlPurifierValidation extends HTMLPurifier_AttrDef_URI
{
	public function validate($uri, $config, $context)
	{
		if (preg_match('/^data:image\/svg(\+xml)?;base64,([^\"]*)$/', $uri)) {
			return true;
		}

		return parent::validate($uri, $config, $context);
	}

}