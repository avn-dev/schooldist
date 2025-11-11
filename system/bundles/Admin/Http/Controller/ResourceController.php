<?php

namespace Admin\Http\Controller;

use Admin\Service\ColorPaletteGenerator;
use Core\Helper\Color;

class ResourceController extends \Core\Controller\Vendor\ResourceAbstractController {
	
	protected $sPath = null;//

	/**
	 * @todo Überprüfen, ob das sicher genug ist
	 * @var string
	 */
	protected $_sAccessRight = null;//'control';

	public function outputLegacyFile($sFile) {

		// Damit die Header nicht direkt gesendet werden -> wg. Sessions
		ob_start();
		
		$_SERVER['PHP_SELF'] = '/admin/'.$sFile;
		
		$sResource = \Util::getDocumentRoot().'system/legacy/admin/'.$sFile;

		$this->bExecutePHP = true;
		
		$this->_printFile($sResource);
		die();

	}

	public function outputResource($sType, $sFile) {

		if ($sType === 'interface') {
			$this->sPath = "system/bundles/Admin/Resources/assets/";
			if (
				!str_ends_with($sFile, '/auth.js') &&
				!str_ends_with($sFile, '/tailwind.css') &&
				!$this->_oAccess->checkValidAccess()
			) {
				echo "Unauthorized";
				header("HTTP/1.0 401 Unauthorized");
				die();
			}
		} else if ($sType === 'custom') {
			return $this->outputCustom();
		} else {
			$this->sPath = "system/bundles/Admin/Resources/public/".$sType."/";
		}

		// Der String zur resource im Vendor Verzeichnis
		$sResource = \Util::getDocumentRoot() . $this->sPath . $sFile;

		// Wenn die Datei existiert, dann wird sie ausgegeben, sonst wird ein
		// 404 ausgegeben
		if (file_exists($sResource)) {
			$this->_printFile($sResource);
		} else {
			echo "404 Not Found";
			header("HTTP/1.0 404 Not Found");
		}
		// Beenden, damit nichts Weiteres ausgegeben wird (MVC_Abstract_Controller gibt sonst einen leeren JSON-String zurück)
		exit();
	}

	public function outputCustom()
	{
		$baseColor = (new \Admin\Helper\Design)->getSystemColor();

		$file = \Util::getDocumentRoot() . 'system/bundles/Admin/Resources/assets/css/custom.css';

		$content = (file_exists($file)) ? file_get_contents($file) : '';

		// TODO Caching
		if (!empty($content)) {

			$color = \Admin\Dto\Color::fromHex($baseColor);

			$colorPalette = (new ColorPaletteGenerator(color: $color, shades: [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]))
				->generate();

			\System::wd()->executeHook('admin_color_palette', $colorPalette);

			$base = $colorPalette->getBase();
			$palette = $colorPalette->getPalette();

			$contrastLightShade = $colorPalette->getContrastShade('#FFFFFF', Color::CONTRAST_RATIO_ELEMENT, true);
			$contrastLightShadeText = $colorPalette->getContrastShade('#FFFFFF', Color::CONTRAST_RATIO_TEXT, true);
			$contrastDarkShade = $colorPalette->getContrastShade('#000000', Color::CONTRAST_RATIO_ELEMENT, true);
			$contrastDarkShadeText = $colorPalette->getContrastShade('#000000', Color::CONTRAST_RATIO_TEXT, true);

			$rgb = fn (array $rgb) => implode(' ', $rgb);

			$content = str_replace('"%primaryColor%"', $baseColor, $content);
			$content = str_replace('"%primaryColorLightness%"', round(Color::getLightness($baseColor), 2), $content);
			$content = str_replace('"%primaryBase%"', $base, $content);
			$content = str_replace('"%primaryShades%"', implode(',', array_keys($palette)), $content);
			// Light-Mode
			$content = str_replace('"%primaryContrastLight%"', $contrastLightShade->getBase(), $content);
			$content = str_replace('"%primaryContrastLightRgb%"', 'rgb('.$rgb($contrastLightShade->getColor()->getRgb()).')', $content);
			$content = str_replace('"%primaryContrastLightTextRgb%"', 'rgb('.$rgb($colorPalette->getContrastShade($contrastLightShade, Color::CONTRAST_RATIO_TEXT)->getColor()->getRgb()).')', $content);
			$content = str_replace('"%primaryContrastTextLight%"', $contrastLightShadeText->getBase(), $content);
			$content = str_replace('"%primaryContrastTextLightRgb%"', 'rgb('.$rgb($contrastLightShadeText->getColor()->getRgb()).')', $content);
			// Dark-Mode
			$content = str_replace('"%primaryContrastDark%"', $contrastDarkShade->getBase(), $content);
			$content = str_replace('"%primaryContrastDarkRgb%"', 'rgb('.$rgb($contrastDarkShade->getColor()->getRgb()).')', $content);
			$content = str_replace('"%primaryContrastDarkTextRgb%"', 'rgb('.$rgb($colorPalette->getContrastShade($contrastDarkShade, Color::CONTRAST_RATIO_TEXT)->getColor()->getRgb()).')', $content);
			$content = str_replace('"%primaryContrastTextDark%"', $contrastDarkShadeText->getBase(), $content);
			$content = str_replace('"%primaryContrastTextDarkRgb%"', 'rgb('.$rgb($contrastDarkShadeText->getColor()->getRgb()).')', $content);

			foreach ($palette as $shade) {
				$content = str_replace('"%primary'.$shade->getBase().'%"', $rgb($shade->getColor()->getRgb()), $content);
				$content = str_replace('"%primary'.$shade->getBase().'Rgb%"', 'rgb('.$rgb($shade->getColor()->getRgb()).')', $content);
				$content = str_replace('"%primaryContrast'.$shade->getBase().'%"', $colorPalette->getContrastShade($shade, Color::CONTRAST_RATIO_ELEMENT)->getBase(), $content);
				$content = str_replace('"%primaryContrast'.$shade->getBase().'Rgb%"', 'rgb('.$rgb($colorPalette->getContrastShade($shade, Color::CONTRAST_RATIO_ELEMENT)->getColor()->getRgb()).')', $content);
				$content = str_replace('"%primaryContrastText'.$shade->getBase().'%"', $colorPalette->getContrastShade($shade, Color::CONTRAST_RATIO_TEXT)->getBase(), $content);
				$content = str_replace('"%primaryContrastText'.$shade->getBase().'Rgb%"', 'rgb('.$rgb($colorPalette->getContrastShade($shade, Color::CONTRAST_RATIO_TEXT)->getColor()->getRgb()).')', $content);
			}
		}

		$response = response($content);
		$response->header('Content-Type', 'text/css');
		$response->header('Expires', '0');
		$response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
		$response->header('Pragma', 'no-cache');

		return $response;
	}
}
