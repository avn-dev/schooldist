<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace TsStudentSso\Service;

/**
 * Description of Saml
 *
 * @author Mark Koopmann
 */
class Saml {
	
	public function getCertPath($includePath=true) {
		
		$path = 'ts/student_sso/cert/';
		
		if($includePath === false) {
			return $path;
		}
		
		$path = storage_path($path);
		
		\Util::checkDir($path);
		
		return $path;		
	}
	
}
