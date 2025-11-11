<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace TsTuition\Gui2\Format\Classroom;

/**
 * Description of Tags
 *
 * @author Mark Koopmann
 */
class Tags extends \Ext_Gui2_View_Format_Abstract
{

	public function format($value, &$column = null, &$resultData = null)
	{

		// Formatierung für Fastselect
		if ($column === null) {
			$value = implode('{#}', $value);
		} else {

			$value = explode('{#}', $value);
			$value = implode(', ', $value);

		}

		return $value;
	}

	public function convert($mValue, &$oColumn = null, &$aResultData = null)
	{

		$values = explode('{#}', $mValue);

		foreach ($values as $key=>&$value) {

			$value = trim($value);

			// Leere Tags verhindern
			if (empty($value)) {
				unset($values[$key]);
				continue;
			}

			// Numerische Werte repräsentieren bereits gespeicherte Tags
			if(is_numeric($value)) {
				continue;
			}

			$tag = \TsTuition\Entity\Classroom\Tag::getRepository()->findOneBy(['tag' => $value]);
			if (empty($tag)) {
				$tag = \TsTuition\Entity\Classroom\Tag::getInstance();
			}

			$tag->tag = $value;
			$tag->save();
			$value = $tag->id;
		}

		return $values;
	}

}