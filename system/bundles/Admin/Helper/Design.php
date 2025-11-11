<?php

namespace Admin\Helper;

use Core\Helper\Color;

class Design {
	
	public function getLogos() {
		
		$aLogos = [];
		$aLogos['framework_logo_small'] = '/admin/assets/media/fidelo_signet_white.svg';
		$aLogos['dark:framework_logo_small'] = '/admin/assets/media/fidelo_signet_white.svg';
		$aLogos['framework_logo'] = '/admin/assets/media/fidelo_software_white.svg';
		$aLogos['dark:framework_logo'] = '/admin/assets/media/fidelo_software_white.svg';
		$aLogos['login_logo'] = '/admin/assets/media/fidelo_software_blue.svg';
		$aLogos['dark:login_logo'] = '/admin/assets/media/fidelo_software_white.svg';
		$aLogos['start_headline_color'] = '#ffffff';
		$aLogos['dark:start_headline_color'] = '#ffffff';
		//$aLogos['support_logo'] = '/admin/assets/media/fidelo_software_signet_blue.svg';
		$aLogos['system_logo'] = null;

		if(is_file(\Util::getDocumentRoot().'storage/system/logo.png')) {
			$aLogos['system_logo'] = '/storage/system/logo.png';
		}

		\System::wd()->executeHook('framework_logos', $aLogos);

		return $aLogos;
	}

	public function formatLogo($sLogo, $sDirectory=null) {

		$aSet = [
			'date' => '1970-01-01',
			'x_dynamic' => '1',
			'x' => 0,
			'y_dynamic' => '0',
			'y' => 50,
			'bg_transparent' => '1',
			'type' => 'png',
			'data' => [
				0 => [
					'type' => 'images',
					'file' => '',
					'x' => '0',
					'y' => '0',
					'from' => '1',
					'index' => '1',
					'user' => '1',
					'w' => 300,
					'h' => 50,
					'resize' => 1,
					'align' => 'C',
					'position' => '1',
				]
			]
		];

		$aInfo = [
			1 => 'ta_format_logo',
			2 => $sLogo
		];

		$oImageBuilder = new \imgBuilder();
		
		if($sDirectory !== null) {
			$oImageBuilder->strImagePath = \Util::getDocumentRoot(false).$sDirectory;
		}

		$sImage = $oImageBuilder->buildImage($aInfo, false, true, $aSet);

		return $sImage;
	}
	
	public function getSystemColor() {
		
		$systemColor = \System::d('system_color', '#4e6ac6');

		\System::wd()->executeHook('system_color', $systemColor);

		// Zum testen
		$check = ['#EF5E50', '#20BC93', '#4E6AC6']; // Schule, Agentur, Core
		#$check = [...$check, ...['#FFFFFF', '#000000', '#B3DAF3', '#090ECD', '#356D75', '#2B040D', '#4D4C51', '#bb0000', '#EB6F24', '#b287ff', '#c3d841', '#79b530', $systemColor]];
		#return $check[random_int(0, count($check)-1)];
		#return Color::random();
		#return '#003466';
		#return '#000000';

		return $systemColor;
	}
	
}