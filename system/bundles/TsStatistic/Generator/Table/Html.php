<?php

namespace TsStatistic\Generator\Table;

use TcStatistic\Model\Table\Cell;

class Html extends \TcStatistic\Generator\Table\Html {

	/**
	 * @inheritdoc
	 */
	protected function formatCellValue(Cell $oCell, array &$aClasses, array &$aStyles) {

		$mValue = parent::formatCellValue($oCell, $aClasses, $aStyles);

		// Wenn null und null soll nicht formatiert werden: Abbruch
		if(
			$mValue === null &&
			!$oCell->getNullValueFormatting()
		) {
			return $oCell->getNullValueReplace();
		}

		switch($oCell->getFormat()) {
			case 'number_int':
				$aStyles['text-align'] = 'right';
				$mValue = \Ext_Thebing_Format::Int($mValue);

				break;

			case 'number_float':
			case 'number_percent':
			case 'number_percent_color':
				$aStyles['text-align'] = 'right';
				$mValue = \Ext_Thebing_Format::Number($mValue);

				if(
					$oCell->getFormat() === 'number_percent' ||
					$oCell->getFormat() === 'number_percent_color'
				) {

					if($oCell->getFormat() === 'number_percent_color') {
						if($mValue > 0) {
							$aStyles['color'] = 'green';

						} elseif($mValue < 0) {
							$aStyles['color'] = 'red';
						}
					}

					$mValue .= '&#8239;%'; // &thinsp;
				}

				break;
			case 'number_amount':
				$iCurrencyId = null;
				if($oCell->hasCurrency()) {
					$iCurrencyId = $oCell->getCurrency();
				}

				$aStyles['text-align'] = 'right';
				$aStyles['white-space'] = 'nowrap'; // Verhinden, dass Währungssymbol umgebrochen wird
				$mValue = \Ext_Thebing_Format::Number($mValue, $iCurrencyId);

				break;
			case 'date':

				// Analog zum Excel-Export hier auch eine Exception erzeugen
				if(
					$oCell->hasValue() &&
					!$oCell->getValue() instanceof \DateTime
				) {
					throw new \UnexpectedValueException('Given date cell is not a DateTime object');
				}

				$oFormat = new \Ext_Thebing_Gui2_Format_Date();
				$mValue = $oFormat->format($oCell->getValue());

				break;
		}

		return $mValue;

	}

}
