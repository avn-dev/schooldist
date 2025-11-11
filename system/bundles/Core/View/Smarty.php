<?php

namespace Core\View;

class Smarty implements \Illuminate\Contracts\View\Engine {
	 
	public function get($path, array $data = []) {
		
		$oView = array_shift($data);
		$oApp = array_shift($data);

		$oSmarty = new \SmartyWrapper();

		$oSmarty->assign($data);

		// Übersetzungspfad setzen für alle L10N-Aufrufe ohne 2. Parameter
		if(
			isset($data['translation_path']) ||
			isset($data['sTranslationPath'])
		) {
			$oSmarty->setTranslationPath($data['translation_path'] ?? $data['sTranslationPath']);
		}

		$sContent = $oSmarty->fetch($path);
		
        return $sContent;

    }

}
