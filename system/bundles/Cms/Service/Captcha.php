<?php

namespace Cms\Service;

class Captcha {

	function generateRandomCode($iLen = 4) {
		srand ((double)microtime()*1000000);
		$sRandCode = "";
	    for ($i = 0; $i < $iLen; $i++) {
	        // this numbers refer to numbers of the ascii table (small-caps)
	        $sRandCode .= chr(rand(97, 122));
	    }
		return $sRandCode;
	}

	function outputImage($sRandCode, $iWidth = 0, $iHeight = 0, $aiBgColor = 0, $aiBorderColor = 0)
	{
		srand ((double)microtime()*1000000);
		$iLen = strlen($sRandCode);
		if($iLen < 2) {
			return;
		}

		if($iWidth == 0) {
			$iWidth = 15 * $iLen;
		}

		if($iHeight == 0) {
			$iHeight = 20;
		}

		if(!is_array($aiBgColor)){
			$aiBgColor = array(238,239,239);
		}

		if(!is_array($aiBorderColor)){
			$aiBorderColor = array(208,208,208);
		}

		$rImage = imagecreatetruecolor($iWidth, $iHeight);

		$rBackgCol =	imagecolorallocate($rImage, $aiBgColor[0],$aiBgColor[1],$aiBgColor[2]);
		$rBorderCol =	imagecolorallocate($rImage, $aiBorderColor[0],$aiBorderColor[1],$aiBorderColor[2]);

		imagefilledrectangle($rImage, 0, 0, $iWidth, $iHeight, $rBackgCol);
		imagerectangle($rImage, 0, 0, $iWidth-1, $iHeight-1, $rBorderCol);

		//TODO: make a configuration for font-include?
		$sFontPath = \Util::getDocumentRoot().'media/font/captcha/';

		$iFontSize = intval($iHeight/2);
		$iLetterSpace = intval(($iWidth - 4) / $iLen);
		for($i=0; $i<$iLen; $i++) {

			// get font
			$sFont = $sFontPath.rand(1,7).'.ttf';
			if(!is_file(($sFont))) {
				$sFont = $sFontPath.'default.ttf';
			}

			// little but nut to little angle
			$angle = rand(0,20)-10;

			while($angle > - 2 && $angle < 2)
				$angle = rand(0,20)-10;

			// fresh colors but not to light
			$iRed=999;
			$iGreen=999;
			$iBlue=999;
			while($iRed+$iGreen+$iBlue >400 || $iRed+$iGreen+$iBlue < 200)
			{
				$iRed = 	250 - 25 * rand(0, 10);
				$iGreen =	250 - 25 * rand(0, 10);
				$iBlue = 	250 - 25 * rand(0, 10);
			}
			$rTextCol =	imagecolorallocate($rImage, $iRed, $iGreen, $iBlue);

			$aiBox =	imagettfbbox($iFontSize, $angle, $sFont, $sRandCode[$i]);

			$x = intval($iLetterSpace * ($i + 0.5) - (($aiBox[4]) / 2));
			$y = rand(-intval(($iHeight - 4 - $aiBox[5])/8), intval(($iHeight - 4 - $aiBox[5])/8)) + intval(($iHeight - 4 - $aiBox[5]) / 2);

			imagettftext($rImage, $iFontSize, $angle, $x+2, $y, $rTextCol, $sFont, $sRandCode[$i]);
		}

		header("Content-type: image/png");
		imagepng($rImage);
		imagedestroy ($rImage);
		
	}

	/**
	   @@param int $iWidth width in pixels
	 * @@param int $iHeight height in pixels
	 * @@param int $iLen number of chars of the captcha
	 * @@param array(int) $aiBgColor rgb-values of the background-color
	 * @@param array(int) $aiBorderColor rgb-values of the border-color
	 * @@param int $iNumber number of captcha in page (needed only if more than one captcha is used)
	 * @@param string $sParam extra text to put into img-tag
	 */
	function getCaptchaImageTag($iWidth, $iHeight, $iLen, $mixBgColor=0, $mixBorderColor=0, $iNumber=1, $sParam="") {
		// derzeit ist _nur_ die random-code-variante aktiv!
		// die appli kann mehr!

		if(intval($iWidth) < 60)	$iWidth = 60;
		if(intval($iHeight) < 20)	$iHeight = 60;
		if(intval($iLen) < 4)		$iLen = 4;

		if(!is_array($mixBgColor)) {
			$mixBgColor = array(
					hexdec(substr($mixBgColor,0,2)),
					hexdec(substr($mixBgColor,2,2)),
					hexdec(substr($mixBgColor,4,2)));
		}
		if(!is_array($mixBorderColor)) {
			$mixBorderColor = array(
					hexdec(substr($mixBorderColor,0,2)),
					hexdec(substr($mixBorderColor,2,2)),
					hexdec(substr($mixBorderColor,4,2)));
		}

		$_SESSION['captcha_width'][$iNumber] = 			$iWidth;
		$_SESSION['captcha_height'][$iNumber] = 		$iHeight;
		$_SESSION['captcha_code_len'][$iNumber] = 		$iLen;
		$_SESSION['captcha_bgcolor'][$iNumber] = 		$mixBgColor;
		$_SESSION['captcha_bordercolor'][$iNumber] = 	$mixBorderColor;

		$sImgTag = '<img src="/system/applications/captcha.php?captcha_number=' .
					intval($iNumber) . '&random='.rand(0,999999).
					'" width="'.$iWidth.'" height="'.$iHeight.'" id="captcha_' . intval($iNumber) . '" '.$sParam.' />';

		return $sImgTag;

	}

	function getCaptchaRefreshScript($iNumber=1){
		return "getElementById('captcha_" . intval($iNumber) . "').src = getElementById('captcha_" . intval($iNumber) . "').src + '&rand='+Math.random()";
	}

	function checkCaptcha($sCode, $iNumber=1) {

		if($iNumber) {
			$strSessionCode = $_SESSION['captcha_code'][$iNumber];
		} else {
			$strSessionCode = $_SESSION['captcha_code'];
		}

		if(strtoupper($sCode) == strtoupper($strSessionCode) && strlen($sCode) >= 4) {
			return true;
		}

		return false;
	}

}