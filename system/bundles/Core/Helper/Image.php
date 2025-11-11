<?php

namespace Core\Helper;

class Image {
	
	public static function saveResizeImage($sSource, $sDestination, $iWidth, $iHeight, $sType='png') {
		
		$aSet = [
			'date' => '1970-01-01',
			'x_dynamic' => '1',
			'x' => 0,
			'y_dynamic' => '1',
			'y' => 0,
			'bg_colour' => 'FFFFFF',
			'type' => $sType,
			'data' => [
				0 => [
					'type' => 'images',
					'file' => '',
					'x' => '0',
					'y' => '0',
					'from' => '1',
					'index' => '1',
					'text' => '',
					'user' => '1',
					'w' => $iWidth,
					'h' => $iHeight,
					'resize' => '1',
					'align' => 'C',
					'grayscale' => '0',
					'bg_colour' => 'ffffff',
					'rotate' => '0',
					'flip' => '',
					'position' => '1',
				]
			]
		];
		
		$sSource = str_replace(\Util::getDocumentRoot(), '', $sSource);
		$sSource = str_replace('storage/', '', $sSource);

		$aInfo = [
			1 => 'core_helper_image',
			2 => $sSource
		];

		$oImageBuilder = new \imgBuilder();
		$oImageBuilder->strImagePath = \Util::getDocumentRoot()."storage/";
		$oImageBuilder->strTargetPath = \Util::getDocumentRoot()."storage/tmp/";
		$oImageBuilder->strTargetUrl = "/storage/tmp/";
		$sImage = $oImageBuilder->buildImage($aInfo, false, true, $aSet);

		rename(\Util::getDocumentRoot(false).$sImage, $sDestination);
		
	}
	
}
