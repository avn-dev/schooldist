<?php

namespace Cms\Hook;

class MetaData {
	
	public function run(array &$metaData, array $page_data) {

		// Platzhalter aus dynamischen Routen
		if(!empty($page_data['routing']['parameters'])) {
			foreach($page_data['routing']['parameters'] as $parameterKey=>$parameter) {
				if(is_array($parameter)) {
					$metaData['DESCRIPTION'] = str_replace('{'.$parameterKey.'}', $parameter['label'], $metaData['DESCRIPTION']??'');
					$metaData['keywords'] = str_replace('{'.$parameterKey.'}', $parameter['label'], $metaData['keywords']??'');
				}
			}
		}

	}
	
}
