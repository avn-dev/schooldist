<?

class WDMath
{
	/**
	 * Calculate the result
	 * 
	 * @param string $sString
	 * @return float
	 */
	public static function result($sString)
	{
		$i = 0;

		// Remove whitespaces
		$sString = str_replace(' ', '', $sString);

		// Split the calculation on brackets and percent signs
		self::_split($sString);

		eval('$i = (float)' . $sString . ';');

		return $i;
	}


	/**
	 * Calculate the percents
	 * 
	 * @param float $iNumber
	 * @param float $iNumberPercent
	 * @param float $iReturnPercent
	 * @return float
	 * 
	 * @example 238 / 119 * 100 = 200 => (200 is 100% from 238 when 238 like 119%)
	 */
	public static function percent($iNumber, $iNumberPercent, $iReturnPercent)
	{
		return (float)bcmul(bcdiv($iNumber, $iNumberPercent, 6), $iReturnPercent, 6);
	}


	/**
	 * Calculate simple calculation
	 * 
	 * @param pointer $sSimple
	 */
	private static function _calculate(&$sSimple)
	{
		$i = 0;

		// Simple calculation
		eval('$i = (float)' . $sSimple . ';');

		$sSimple = $i;
	}


	/**
	 * Replace the percent
	 * 
	 * @param pointer $sString
	 */
	private static function _replacePercent(&$sString)
	{
		// Repeat, as long as percent signs exist, find the position of '%%'
		while(($iPos = strpos($sString, '%%')) !== false)
		{
			$sStart = $iPercent = '';

			// Go back in single steps
			for(--$iPos; $iPos >= 0; $iPos--)
			{
				if(is_numeric($sString[$iPos]) || $sString[$iPos] == '.')
				{
					// Cut backward the number of percent
					$iPercent .= $sString[$iPos];
				}
				else
				{
					$bAdd = false;

					// Cut backward the rest of calculation at begin of string
					// without the operation sign before percent number
					while(--$iPos >= 0)
					{
						if(is_numeric($sString[$iPos]) || $sString[$iPos] == '.')
						{
							$bAdd = true;
						}
						if($bAdd && $sString[$iPos] != '(')
						{
							$sStart .= $sString[$iPos];
						}
					}

					break;
				}
			}

			$iTemp = 0;

			// Reverse the rest of calculation from begin of string
			$sStart = strrev($sStart);

			// Get the result of begin of calculation
			eval('$iTemp = ' . $sStart . ';');

			// Reverse the number of percent
			$iPercent = strrev($iPercent);

			// Find the position of the number of percent with percent sign
			$iPos = strpos($sString, $iPercent . '%%');

			// Get the begin of calculation string
			$s1 = substr($sString, 0, $iPos);

			$s2 = '';

			// Get the percent calculation
			eval('$s2 = (float)(' . $iTemp . ' / 100 * ' . $iPercent . ');');

			if($s1[0] == '(')
			{
				eval('$s2 = (float)(' . substr($s1, 1) . $s2 . ');');
				$s1 = '(';
			}
			else
			{
				eval('$s2 = (float)(' . $s1 . $s2 . ');');
				$s1 = '';
			}

			// Get the end of calculation string
			$s3 = substr($sString, ($iPos + strlen($iPercent . '%%')));

			// Write new simplified calculation string
			$sString = $s1 . $s2 . $s3;
		}

		$i = 0;

		// Calculate simplified calculation
		eval('$i = (float)' . $sString . ';');

		$sString = $i;
	}


	/**
	 * Split the calculation
	 * 
	 * @param pointer $sString
	 */
	private static function _split(&$sString)
	{
		// No brackets in the calculation
		if(strpos($sString, '(') === false)
		{
			// No percent signs in the calculation
			if(strpos($sString, '%%') === false)
			{
				return;
			}

			// Percent signs exist
			self::_replacePercent($sString);

			return;
		}

		// Repeat, as long as brackets exist, find the position of closing bracket ')'
		while(($iPos2 = strpos($sString, ')')) !== false)
		{
			// Go back to the open bracket '('
			for($i = $iPos2; $i >= 0; $i--)
			{
				if($sString[$i] == '(')
				{
					// Cut the content of the bracket
					$sBracket = substr($sString, $i, $iPos2 - $i + 1);

					if(strpos($sBracket, '%%') !== false)
					{
						// Percent signs exist
						self::_replacePercent($sBracket);
					}
					else
					{
						// Simple calculation
						self::_calculate($sBracket);
					}

					// Replace the bracket in the total calculation with result of calculation in the bracket
					$sString = substr($sString, 0, $i) . $sBracket . substr($sString, $iPos2 + 1);

					break;
				}
			}
		}

		// For the last calculation without brackets
		self::_split($sString);
	}
}