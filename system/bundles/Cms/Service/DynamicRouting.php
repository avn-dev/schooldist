<?php

namespace Cms\Service;

class DynamicRouting {

	const CACHE_GROUP = 'cms_dynamic_routing';
	
	public static function getDynamicRoutingPlaceholders() {
		
		$aPlaceholder = [
		];
		
		return $aPlaceholder;
	}

	public function checkLink($sLink) {
		
		$aPermaLinks = $this->getIndex();
		
		if(isset($aPermaLinks[$sLink])) {
			return $aPermaLinks[$sLink];
		}
		
		return false;
	}

	public function getLink($sKey) {
		
		$aPermaLinks = $this->getLinks();

		if(isset($aPermaLinks[$sKey])) {
			return $aPermaLinks[$sKey]['link'];
		}
		
		return false;
	}

	public function getTitle($sKey) {
		
		$aPermaLinks = $this->getLinks();
		
		if(isset($aPermaLinks[$sKey])) {
			return $aPermaLinks[$sKey]['title'];
		}
		
		return false;
	}

	public function getName($sKey) {
		
		$aPermaLinks = $this->getLinks();
		
		if(isset($aPermaLinks[$sKey])) {
			return $aPermaLinks[$sKey]['name'];
		}
		
		return false;
	}

	public function buildLinks() {

		$aPermaLinks = [];

		// Diese Methode muss immer abgeleitet werden.

		\WDCache::deleteGroup(\Cms\Service\DynamicRouting::CACHE_GROUP);
		
		return $aPermaLinks;
	}

	public function getLinks() {

		$sCacheKey = 'DynamicRouting::getLinks';

		$aPermaLinks = \WDCache::get($sCacheKey);
		
		if($aPermaLinks === null) {

			$aPermaLinks = $this->buildLinks();
			
			\WDCache::set($sCacheKey, (60*60*24), $aPermaLinks, false, self::CACHE_GROUP);

		}

		return $aPermaLinks;
	}

	public function getIndex() {

		$sCacheKey = 'DynamicRouting::getIndex';

		$aIndex = \WDCache::get($sCacheKey);
		
		if($aIndex === null) {

			$aPermaLinks = $this->getLinks();
			
			foreach($aPermaLinks as $aPermaLink) {
				$aIndex[$aPermaLink['link']] = $aPermaLink;
			}

			\WDCache::set($sCacheKey, (60*60*24), $aIndex, false, self::CACHE_GROUP);

		}

		return $aIndex;
	}
	
	protected function addLink(array $dynamicRoutings, array &$permalinks, \Cms\Model\DynamicRouting\Node $node) {
		
		foreach($dynamicRoutings as $dynamicRoute) {

			$link = $dynamicRoute->permalink_template;
			$title = $dynamicRoute->title_template;
			
			$key = $parameters = [];
			
			$check = $node->checkTemplate($link);

			if(!$check) {
				continue;
			}
			
			foreach($node as $placeholder=>$properties) {
				
				if(strpos($link, '{'.$placeholder.'}') !== false) {
					$key[] = $properties['slug'];
					$link = str_replace('{'.$placeholder.'}', $properties['slug'], $link);
				}

				if(strpos($title, '{'.$placeholder.'}') !== false) {
					$title = str_replace('{'.$placeholder.'}', $properties['label'], $title);
				}

				$parameters[$placeholder] = $properties;

			}
			
			$key = implode('.', $key);
			
			$permalinks[$key] = [
				'key' => $key,
				'link' => $link,
				'title' => $title,
				'dynamic_routing' => (int)$dynamicRoute->id,
				'language' => $dynamicRoute->language_iso,
				'page_id' => (int)$dynamicRoute->page_id,
				'parameters' => $parameters
			];

		}
		
	}
	
	/**
	 * Hier können Felder definiert werden, die man pro erzeugter Route pflegen und dann auf den generierten Seiten ausgeben kann
	 * @return array
	 */
	public function getContentFields(\Cms\Entity\DynamicRouting $dynamicRouting):array {
		
		return [
//			[
//				'key'=>'top',
//				'type'=>'html',
//				'label'=>'Text'
//			]
		];
		
	}
	
}