<?php

/**
 * The documentor of extensions
 */
class Ext_Documentor_Documentor
{
	/**
	 * The ouput array
	 */
	private $_aOutput = array();


	/**
	 * Temporary content buffer
	 */
	private $_sTmpContent = '';


	/**
	 * The original file content
	 */
	private $_sContent = '';


	/**
	 * Temporary class buffer
	 */
	private $_sTmpClass = '';


	/**
	 * The constructor
	 * 
	 * @param string : The name of the directory of an extension
	 */
	public function __construct($sExtension)
	{
		$this->_getFileNames($sExtension);
	}


	/**
	 * Returns the values of properties
	 * 
	 * @param string : The alias of a property
	 * @return mixed : The value of a propetry
	 */
	public function __get($sName)
	{
		switch($sName)
		{
			case 'output':
			{
				return $this->_aOutput;
				break;
			}
		}
	}


	/**
	 * Reads all file names of selected directory and subdirectories
	 * 
	 * @param string : The directory name
	 * @return array : The array with file names
	 */
	private function _getFileNames($sDir)
	{
		$aFileNames = array();

		if(is_dir(EXT_DIR.$sDir))
		{
			if($rHandle = opendir(EXT_DIR.$sDir))
			{
				while(($sFile = readdir($rHandle)) !== false)
				{
					if($sFile != '.' && $sFile != '..' && $sFile != '.svn')
					{
						if(substr($sFile, strrpos($sFile, '.')) == '.php')
						{
							$this->_sContent = $this->_sTmpContent = file_get_contents(EXT_DIR.$sDir.'/'.$sFile);

							$this->_aOutput[$sDir.'/'.$sFile]['classes'] = $this->_getClasses();
							$this->_aOutput[$sDir.'/'.$sFile]['functions'] = $this->_getFunctions();
						}
						else if(is_dir(EXT_DIR.$sDir.'/'.$sFile))
						{
							$aFileNames[$sFile] = $this->_getFileNames($sDir.'/'.$sFile);
						}

						if(empty($aFileNames[$sFile]))
						{
							unset($aFileNames[$sFile]);
						}
					}
				}
			}
		}

		return $aFileNames;
	}


	/**
	 * Filters all classes
	 * 
	 * @return array : The array with classes
	 */
	private function _getClasses()
	{
		preg_match_all("/(\/\*\*.+?\*\/)?+[\s]*((final|abstract)?[\s]*class[\s]+)([a-z0-9_]+)[\s]*(extends|implements)?[\s]*([a-z0-9_]+)?[\s]*\{/is", $this->_sContent, $aClasses);

		foreach((array)$aClasses[4] as $iKey => $sValue)
		{
			$this->_sTmpClass = $this->_getClassString(trim($aClasses[2][$iKey].$sValue));

			$aClasses['properties'][$iKey]	= $this->_getProperties();
			$aClasses['methods'][$iKey]		= $this->_getMethods();
			$aClasses['comments'][$iKey]	= nl2br(trim(preg_replace("/[\s]*(\/\*\*|\*\/|\*)[\s]*/is", "\n", $aClasses[1][$iKey])));
			$aClasses['classes'][$iKey]		= $aClasses[2][$iKey].' '.$aClasses[4][$iKey].' '.$aClasses[5][$iKey].' '.$aClasses[6][$iKey];

			// Remove the class from content
			$this->_sContent = str_replace($aClasses[1][$iKey], '', $this->_sContent);
		}

		unset($aClasses[0], $aClasses[1], $aClasses[2], $aClasses[3], $aClasses[4], $aClasses[5], $aClasses[6]);

		return $aClasses;
	}


	/**
	 * Filters all properties of a class
	 * 
	 * @return array : The array with properties
	 */
	private function _getProperties()
	{
		$sContent = $this->_sTmpClass;

		// Get the properties
		preg_match_all('/(\/\*\*.+?\*\/)?[\s]*(static |public |protected |var |private ){1,5}[\s]*(\$[_a-z0-9]*?)([\s]*=[\s]*.+?)?[\s]*;[\s]*\n/is', $sContent, $aProperties);

		// Unset the properties
		foreach((array)$aProperties[0] as $iKey => $sValue)
		{
			$this->_sTmpClass = str_replace($sValue, '', $this->_sTmpClass);
		}

		$aReturn = array();
		foreach((array)$aProperties[3] as $iKey => $sValue)
		{
			$sComment = trim(preg_replace("/[\s]*(\/\*\*|\*\/|\*)[\s]*/is", "\n", $aProperties[1][$iKey]));
			$aReturn[] = array(
				'comment'	=> nl2br(str_replace("\n\n", "\n", $sComment)),
				'property'	=> $aProperties[2][$iKey] . $sValue . $aProperties[4][$iKey]
			);
		}

		return $aReturn;
	}


	/**
	 * Filters all methods of a class
	 * 
	 * @return array : The array with methods
	 */
	private function _getMethods()
	{
		$sContent = $this->_sTmpClass;

		// Get the methods
		preg_match_all("/(\/\*\*.+?\*\/)?[\s]*(static |public |protected |final |private |abstract ){0,5}[\s]*function[\s]+([a-z0-9_]+)[\s]*\((.*?)\)[\s]*(\{|;)/is", $sContent, $aMethods);

		$aReturn = array();
		foreach($aMethods[3] as $iKey => $sValue)
		{
			$sComment = trim(preg_replace("/[\s]*(\/\*\*|\*\/|\*)[\s]*/is", "\n", $aMethods[1][$iKey]));
			$aReturn[] = array(
				'comment'	=> nl2br(str_replace("\n\n", "\n", $sComment)),
				'method'	=> $aMethods[2][$iKey] . $sValue . '(' . $aMethods[4][$iKey] . ')'
			);
		}

		return $aReturn;
	}


	/**
	 * Filters all functions outer classes
	 * 
	 * @return array : The array with functions
	 */
	private function _getFunctions()
	{
		$sContent = $this->_sContent;

		// Get the functions
		preg_match_all("/(\/\*\*.+?\*\/)?[\s]*function[\s]+([a-z0-9_]+)[\s]*\((.*?)\)[\s]*\{/is", $sContent, $aFunctions);

		$aReturn = array();
		foreach($aFunctions[3] as $iKey => $sValue)
		{
			$sComment = trim(preg_replace("/[\s]*(\/\*\*|\*\/|\*)[\s]*/is", "\n", $aFunctions[1][$iKey]));
			$aReturn[] = array(
				'comment'	=> nl2br(str_replace("\n\n", "\n", $sComment)),
				'function'	=> $aFunctions[2][$iKey] . $sValue . '(' . $aFunctions[4][$iKey] . ')'
			);
		}

		return $aReturn;
	}


	/**
	 * Filters the complete class as a string
	 * 
	 * @param string : The name of class
	 * @return string : The complete class as a string
	 */
	private function _getClassString($sClassName)
	{
		// Clear the comments and strings in '' and ""
		$this->_sTmpContent = str_replace(array('""', "''"), array('__', '__'), $this->_sTmpContent);
		$this->_sTmpContent = str_replace('"', "'", $this->_sTmpContent);
		$this->_sTmpContent = preg_replace("/('.*?[^\\\]')/ies", 'str_pad(\'\', strlen(\'$0\'), \'_\')', $this->_sTmpContent);
		$this->_sTmpContent = preg_replace("/(\/\/.+?\n)/ies", 'str_pad(\'\', strlen(\'$0\'), \'~\')', $this->_sTmpContent);
		$this->_sTmpContent = preg_replace("/(\#.+?\n)/ies", 'str_pad(\'\', strlen(\'$0\'), \'~\')', $this->_sTmpContent);
		$this->_sTmpContent = preg_replace("/(\/\*.+?\*\/)/ies", 'str_pad(\'\', strlen(\'$0\'), \'~\')', $this->_sTmpContent);

		// Get the begin of the class
		$sContent = trim(mb_substr($this->_sTmpContent, mb_strpos($this->_sTmpContent, $sClassName)));

		// Get the end of class
		$iCounter = -1;
		for($i = 0; $i < mb_strlen($sContent); $i++)
		{
			if($sContent[$i] == '{' && $iCounter == -1)
			{
				$iCounter = 0;
			}
			if($sContent[$i] == '{' && $iCounter != -1)
			{
				$iCounter++;
			}
			if($sContent[$i] == '}' && $iCounter != -1)
			{
				$iCounter--;
			}
			if($sContent[$i] == '}' && $iCounter == 0)
			{
				break;
			}
		}

		// Get the class as string
		$sContent = trim(mb_substr($this->_sContent, mb_strpos($this->_sContent, $sClassName), $i + 1));

		// Remove the class from content
		$this->_sContent = str_replace($sContent, '', $this->_sContent);

		return $sContent;
	}
}

?>