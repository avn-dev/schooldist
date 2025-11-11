<?php

namespace Core\Service;

use Core\Entity\System\Elements;

class SystemElements {

	/**
	 * Baut ein zusammengefasstes Array für die system_elements auf
	 * 
	 * z.b.
	 * 
	 * array:2 [
	 *	"frontend" => array:1 [
	 *	  "factory_allocations" => array:1 [
	 *		"Cms\Service\DynamicRouting" => "Bundle\Service\DynamicRouting"
	 *	  ]
	 *	]
	 *	"backend" => array:2 [
	 *	  "includes" => array:1 [
	 *		 0 => "/var/www/.../html/system/extensions/cms/cms.backend.php"
	 *		]
	 *	  "factory_allocations" => array:1 [
	 *		"Cms\Service\DynamicRouting" => "Bundle\Service\DynamicRouting"
	 *	  ]
	 *	]
	 * ]
	 * 
	 * @return array
	 */
	public function getConfig() {

		$aConfig = \Core\Facade\Cache::get('core_system_elements');

		if($aConfig === null) {
			$aConfig = $this->buildArray();
			\Core\Facade\Cache::put('core_system_elements', (60*60*24*28), $aConfig);
		}

		return $aConfig;
	}

	/**
	 * Baut ein zusammengefasstes Array für die system_elements auf
	 *
	 * @return array
	 */
	private function buildArray() {

		$aElements = Elements::query()->get();
		
		$aConfig = [
			'frontend' => [],
			'backend' => []
		];

		foreach($aElements as $oElement) {
			/* @var \Core\Entity\System\Elements $oElement */
				
			$aInterfaces = [];
			if ($oElement->isBundle()) {
				$aInterfaces[] = 'frontend';
				$aInterfaces[] = 'backend';
			} else {
				if ($oElement->isFrontendElement()) {
					$aInterfaces[] = 'frontend';
				}
				if ($oElement->isBackendElement()) {
					$aInterfaces[] = 'backend';
				}
			}

			foreach($aInterfaces as $sInterface) {
				// prüfen ob es ein Bundle gibt ($oElement->file enthält exakt den Bundlenamen)		
				$this->includeBundle($oElement, $sInterface, $aConfig[$sInterface]);				
				// prüfen ob leagcy Datei existiert - *.backend.php | *.frontend.php				
				$this->includeModule($oElement, $sInterface, $aConfig[$sInterface]);
				// Bundle mit strtolower suchen
				$this->includeLowercaseBundle($oElement, $sInterface, $aConfig[$sInterface]);
			}					
		}

		return $aConfig;		
	}

	/**
	 * Setzt alle Werte aus der Config-Datei des Bundles
	 * 
	 * @param Elements $oElement
	 * @param string $sInterface
	 * @param array $aConfig
	 * @throws \InvalidArgumentException
	 */
	private function includeBundle(Elements $oElement, string $sInterface, array &$aConfig) {
		
		try {
			$aBundleConfig = (new \Core\Helper\Bundle())
				->getBundleConfigData($oElement->file);
		} catch(\Core\Exception\Bundle\BundleException $e) {
			// kein Bundle oder Config-Datei vorhanden
			return false;
		}

		if(isset($aBundleConfig['factory_allocations'])) {

			if(!isset($aConfig['factory_allocations'])) {
				$aConfig['factory_allocations'] = [];
			}

			$aConfig['factory_allocations'] += $aBundleConfig['factory_allocations'];
		}

		if(isset($aBundleConfig['hooks'])) {

			$aNeededHookConfig = ['class'];

			foreach($aBundleConfig['hooks'] as $sHook => $aHookConfig) {

				$aMissingConfigs = array_diff_key(array_flip($aNeededHookConfig), $aHookConfig);
				// prüfen ob die nötigen Einstellungen vorhanden sind
				if(!empty($aMissingConfigs)) {
					throw new \InvalidArgumentException('Config ['.implode(', ', array_flip($aMissingConfigs)).'] is missing for hook "'.$sHook.'" in bundle "'.$oElement->file.'"');
				}

				// Wenn kein Interface angegeben ist gilt es für frontend und backend
				$bHasInterface = (isset($aHookConfig['interface']))
						? in_array($sInterface, (array)$aHookConfig['interface'])
						: true;

				if($bHasInterface) {	
					$aConfig['hooks'][] = [
						'hook' => $sHook,
						'module' => $aHookConfig['class'],
						'element' => $oElement->element
					];
				}
			}

		}

		if(isset($aBundleConfig['providers'])) {
			$aConfig['providers'] = array_unique(
				array_merge((array)$aConfig['providers'], $aBundleConfig['providers'])
			);
		}

	}
	
	/**
	 * Da teilweise Bundles kleingeschrieben eingetragen wurden versuchen wir hier das
	 * Bundle mit strtolower() zu suchen 
	 * 
	 * @param Elements $oElement
	 * @param string $sInterface
	 * @param array $aConfig
	 */
	private function includeLowercaseBundle(Elements $oElement, string $sInterface, array &$aConfig) {
		
		$aAllBundles = \Core\Helper\Bundle::getAllBundles();
		
		$aMapping = [];
		foreach($aAllBundles as $sBundle) {
			$aMapping[strtolower($sBundle)] = $sBundle;
		}

		if(isset($aMapping[$oElement->file])) {	
			
			$oDummy = new Elements();
			$oDummy->file = $aMapping[$oElement->file];
			$oDummy->element = $oElement->element;
			
			$this->includeBundle($oDummy, $sInterface, $aConfig);			
		}		
	}
	
	/**
	 * Prüft ob für ein Module eine *.backend.php oder *.frontend.php Datei gibt und merkt sich den Pfad
	 * 
	 * @param Elements $oElement
	 * @param string $sInterface
	 * @param array $aConfig
	 */
	private function includeModule(Elements $oElement, string $sInterface, array &$aConfig) {
		
		$sFileName = \Util::getDocumentRoot().'system/extensions/'.strtolower($oElement->file).'/'.strtolower($oElement->file).'.'.$sInterface.'.php';

		if (
			file_exists($sFileName) && 
			is_readable($sFileName)
		) {
			$aConfig['includes'][] = $sFileName;
		}	
		
	}
		
}

