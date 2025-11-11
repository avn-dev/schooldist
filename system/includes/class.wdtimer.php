<?php

final class WDTimer
{
	/**
	 * The instance object
	 *
	 * @var object
	 */
	private static $_oInstance;


	/**
	 * The access times data
	 * 
	 * @var array
	 */
	private $_aTimes = array();


	/**
	 * Overwrite the magic methods
	 */
	private function __construct() {}
	private function __clone() {}


	/**
	 * Get the own instance
	 *
	 * @return WDTimer
	 */
	public static function getInstance()
	{
		if(empty(self::$_oInstance))
		{
			self::$_oInstance = new self;
		}

		return self::$_oInstance;
	}


	/**
	 * Start the logging on given point
	 * 
	 * @param mixed $mPoint
	 */
	public function start($mPoint)
	{
		$aData = getrusage();

		// Init
		$this->_aTimes[$mPoint] = array();

		// Set start time
		$this->_aTimes[$mPoint]['start'] = microtime(true);

		// Set user start time
		$this->_aTimes[$mPoint]['start_user_time'] = $aData['ru_utime.tv_sec'] * 1e6 + $aData['ru_utime.tv_usec'];

		// Set system start time
		$this->_aTimes[$mPoint]['start_system_time'] = $aData['ru_stime.tv_sec'] * 1e6 + $aData['ru_stime.tv_usec'];

	}


	/**
	 * Stop the logging on given point
	 * 
	 * @param mixed $mPoint
	 * @param string $sComment
	 */
	public function stop($mPoint, $bOutput=true, $sComment = '')
	{
		$aData = getrusage();

		// Set end time
		$this->_aTimes[$mPoint]['end'] = microtime(true);

		// Set user end time
		$this->_aTimes[$mPoint]['end_user_time'] = $aData['ru_utime.tv_sec'] * 1e6 + $aData['ru_utime.tv_usec'];

		// Set system end time
		$this->_aTimes[$mPoint]['end_system_time'] = $aData['ru_stime.tv_sec'] * 1e6 + $aData['ru_stime.tv_usec'];

		// Add comments
		$this->_aTimes[$mPoint]['comment'] .= $sComment;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Total times

		$this->_aTimes[$mPoint]['total'] += $this->_aTimes[$mPoint]['end'] - $this->_aTimes[$mPoint]['start'];

		$this->_aTimes[$mPoint]['total_user'] += ($this->_aTimes[$mPoint]['end_user_time'] - $this->_aTimes[$mPoint]['start_user_time']) / 1e6;

		$this->_aTimes[$mPoint]['total_system'] += ($this->_aTimes[$mPoint]['end_system_time'] - $this->_aTimes[$mPoint]['start_system_time']) / 1e6;

		// Ausgabe
		if($bOutput) {
			$this->output($mPoint, debug_backtrace());
		}

		// Werte wieder zurücksetzen
		$this->start($mPoint);

	}

	public function output($mPoint, $aDebug=false) {

		// Get the file name and line of output
		if($aDebug == false){
			$aDebug	= debug_backtrace();
		}

		$dLine	= '<span style="color:#FF6600;">'.$aDebug[0]['line'].'</span>';
		$sFile	= '<span style="color:#FF6600;">'.str_replace(\Util::getDocumentRoot(), '', $aDebug[0]['file']).'</span>';

		echo '<span style="position:relative; z-index:1000000;">';
		echo "\n<pre>----------------------------------------------------------------------------------------------------";
		echo "\nWDTimer <b>".$mPoint."</b> on line ".$dLine." in ".$sFile."\n\n";
		if(!empty($this->_aTimes[$mPoint]['comment'])) {
			echo "Comment:     ".$this->_aTimes[$mPoint]['comment']."\n";
		}
		echo "Total time:  ".$this->_aTimes[$mPoint]['total']."\n";
		echo "User time:   ".$this->_aTimes[$mPoint]['total_user']."\n";
		echo "System time: ".$this->_aTimes[$mPoint]['total_system']."\n";
		echo "----------------------------------------------------------------------------------------------------\n";
		echo "</span></pre>";

	}

}