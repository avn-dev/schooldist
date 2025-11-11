<?php

class Ext_Thebing_Gui2_Format_Image extends Ext_Gui2_View_Format_Abstract {

	protected $_sMediaDirectory;
	protected $_sTitle;
	protected $_iWidth;
	protected $_iHeight;

	public function __construct($sMediaDirectory, $sTitle, $iWidth = 120, $iHeight = 120, $iResize=1) {

		if(strpos($sMediaDirectory, '/') === 0) {
			$sMediaDirectory = substr($sMediaDirectory, 1);
		}
		
		$this->_sMediaDirectory = $sMediaDirectory;
		$this->_sTitle = $sTitle;
		$this->_iWidth = $iWidth;
		$this->_iHeight = $iHeight;
		$this->iResize = $iResize;

	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		/** @todo: db_column title,alt **/
		/** @todo: image resize mit php **/

		if(!empty($mValue)) {

			$sFile = \Util::getDocumentRoot().'storage/'.$this->_sMediaDirectory.'/'.$mValue;

			if(file_exists($sFile) ) {

				$sFilePath	= $this->_sMediaDirectory.'/'.$mValue;

				$sImage = $this->buildImage($mValue);

				$sOnClick = 'onclick="window.open(\'/storage/download/'.$sFilePath.'\'); return false"';
				$sRetVal = '<img style="cursor:pointer;" ' . $sOnClick . ' src="'.$sImage.'" alt="' . $this->_sTitle . '" title="' . $this->_sTitle . '"/>';

				return $sRetVal;
			}
		}

		return null;
	}

	public function buildImage($sImage) {

		$aSet = [
			'date' => $this->_iWidth.'-'.$this->_iHeight.'-'.$this->iResize,
			'x' => $this->_iWidth,
			'y' => $this->_iHeight,
			'bg_colour' => 'FFFFFF',
			'type' => 'jpg',
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
					'w' => $this->_iWidth,
					'h' => $this->_iHeight,
					'resize' => $this->iResize,
					'align' => 'C',
					'grayscale' => '0',
					'bg_colour' => 'ffffff',
					'rotate' => '0',
					'flip' => '',
					'position' => '1',
				]
			]
		];

		$sFilePath	= $this->_sMediaDirectory.'/'.$sImage;

		$aInfo = [
			1 => 'gui2_format_image',
			2 => $sFilePath
		];

		$oImageBuilder = new \imgBuilder();
		$oImageBuilder->strImagePath = \Util::getDocumentRoot()."storage/";
		$oImageBuilder->strTargetPath = \Util::getDocumentRoot()."storage/tmp/";
		$oImageBuilder->strTargetUrl = "/storage/tmp/";
		$sImage = $oImageBuilder->buildImage($aInfo, false, true, $aSet);

		return $sImage;
	}

}
