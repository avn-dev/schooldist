<?php


class Ext_Thebing_Tuition_Color extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'kolumbus_tuition_colors';

	// Format
	protected $_aFormat = array(
		'title' => array(
			'required' => true
		),
		'code' => array(
			'required' => true
		),
	);

	public function  validate($bThrowExceptions = false)
	{
		$mError = parent::validate($bThrowExceptions);

		if(true===$mError)
		{
			$aErrors		= array();
			$sColorCode		= $this->code;
			$aRgb			= imgBuilder::_htmlHexToBinArray($sColorCode);
			$iBrightness	= sqrt($aRgb[0]*$aRgb[0]*0.241+$aRgb[1]*$aRgb[1]*0.691+$aRgb[2]*$aRgb[2]*0.068);
		
			if($iBrightness <= 128)
			{
				$aErrors['code'] = L10N::t('Bitte wählen Sie eine helle Farbe', 'Thebing » Tuition » Resources » Colors');
			}


			if(empty($aErrors))
			{
				return true;
			}
			else
			{
				return $aErrors;
			}

		}
		else
		{
			return $mError;
		}
	}

	public static function getColorsForSchool($iSchoolId, $bPrepareForSelect=false)
	{
		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`active`	= 1 AND
				`school_id` = :school_id
		";

		$aSql = array(
			'table'			=> 'kolumbus_tuition_colors',
			'school_id'		=> (int)$iSchoolId,
		);

		$aReturn	= array();
		$aResult	= DB::getPreparedQueryData($sSql, $aSql);
		if(!$bPrepareForSelect)
		{
			$aReturn = $aResult;
		}
		else
		{
			foreach($aResult as $aData)
			{
				$aReturn[$aData['id']] = $aData['title'];
			}
		}

		return $aReturn;
	}

}