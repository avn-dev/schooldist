<?php
class Ext_Mosaik_Generator{
	
	protected $_sPicture = '';
	protected $_sPicturePool = '';
	
	protected $_aPictureIndex = array();
	protected $_aColorIndex = array();
	protected $_aCurrentPixelColor = array();
	
	protected $_iColorScale = 15;
	protected $_iQualityLevel = 3;
	
	protected $_iPixelX = 30;
	protected $_iPixelY = 30;
	
	protected $_iIndexQuality = 100;
	
	protected $_aPictureFound = array();
	
	protected $_iMicrotime = 0;
	
	/**
	 * Einfache Funktion zum Replizieren des PHP 5-Verhaltens
	 */
	public function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}
	
	public function executeMicrotime($iDie = 0){
		if($this->_iMicrotime == 0){
			$this->_iMicrotime = $this->microtime_float();
		} else {
			$time = $this->microtime_float() - $this->_iMicrotime;
			$this->_iMicrotime = $this->microtime_float();
			__out('Dauer: '.$time);
		}
		if($iDie){
			die();
		}
	}
	
	
	
	public function setQualityLevel($iLevel = 3){
		$this->_iQualityLevel = $iLevel;
	}
	
	public function setPixelSize($iX = 30,$iY = 30){
		$this->_iPixelX = $iX;
		$this->_iPixelY = $iY;
	}
	
	public function __construct(&$sPicture,&$sPicturePool){
		$this->_sPicture = $sPicture;
		$this->_sPicturePool = $sPicturePool;

		ini_set('max_execution_time',300);
		
		if($sPicture == "" || !is_file($sPicture)){
			die('Kein Bild gefunden!');
		}	
		if($sPicturePool == "" || !is_dir($sPicturePool)){
			die('Kein Bildpool gefunden!');
		}	
		
		DB::setResultType(MYSQL_ASSOC);
		
		$this->defineColorIndex();

	}
	
	public function defineColorIndex(){
		
		// Bilderpool daten holen
		$sSql = " SELECT * FROM `mosaik_index` ";
		$aPictures = DB::getQueryData($sSql);
		
		$this->_aColorIndex = array();
		$this->_aPictureIndex = array();
		
		// Arrays füllen
		foreach($aPictures as $aPicture){
			
			// Farbschritt errechnen
			//$iKey = (int)($aPicture['r'] / $this->_iColorScale) * $this->_iColorScale;
			//$iKey2 = (int)($aPicture['g'] / $this->_iColorScale) * $this->_iColorScale;
			//$iKey3 = (int)($aPicture['b'] / $this->_iColorScale) * $this->_iColorScale;
			
			//$this->_aColorIndex['r'][$iKey][$aPicture['r']][] = $aPicture['path'];
			//$this->_aColorIndex['g'][$iKey2][$aPicture['g']][] = $aPicture['path'];
			//$this->_aColorIndex['b'][$iKey3][$aPicture['b']][] = $aPicture['path'];
			
			$this->_aColorIndex['all']['r'][$aPicture['r']] = $aPicture['path'];
			$this->_aColorIndex['all']['g'][$aPicture['g']] = $aPicture['path'];
			$this->_aColorIndex['all']['b'][$aPicture['b']] = $aPicture['path'];
			
			$this->_aPictureIndex[$aPicture['path']] = $aPicture; 
			
		}
		
	}
	
	public function createIndex(){
		ini_set('max_execution_time',1200);
		$this->importImagesFromFlickrCSV();
		return true;
		
		
		// Bilderdurchschnittsfarbe holen
		echo "Starting .... ";
		echo strftime('%X %x');
		$aPicturesIndex = $this->getPictureIndex();
		echo "<br/>Writing .... ";
		echo strftime('%X %x');

		foreach($aPicturesIndex as $sPicture => $aColors){
			// Wenn das Bild noch nicht aufgenommen wurde
			if(!key_exists($sPicture,$this->_aPictureIndex)){
				// Hex Farb Code ausrechen
				// $hex = $this->rgb2hex($aColors['r'],$aColors['g'],$aColors['b']);
				// Dez Farbcode ausrechnen
				// $iColor = $this->hex2dez($hex);
				// Eintrag in die DB schreiben
				$sSql = " INSERT INTO `mosaik_index` SET `r` = :r, `g` = :g, `b` = :b, `hex` = :hex, `dec` = :dec, `path` = :path ";
				$aSql = array('r'=>$aColors['r'],'g'=>$aColors['g'],'b'=>$aColors['b'],'hex'=>$hex,'dec'=>$iColor,'path'=>$sPicture);
				DB::executePreparedQuery($sSql,$aSql);	
				
			}
		

		}
		echo "<br/> Finished .... ";
		echo strftime('%X %x');
		
	}
		
	public function createPicture(){
			
		$xScale = $this->_iPixelX;
		$yScale = $this->_iPixelY;
		
		if($this->_iQualityLevel == 1){
			$iPixelSize = 1;
		}else if($this->_iQualityLevel == 2){
			$iPixelSize = 2;
		} else {
			$iPixelSize = 5;
		}	
		
		list($xOrginal, $yOrginal) = getimagesize($this->_sPicture);
		
		$x = $xOrginal * $xScale;
		$y = $yOrginal * $yScale;
		
		$rPicture = imagecreatetruecolor($x, $y);

		$img = imagecreatefromjpeg($this->_sPicture); // datei öffnen
		$breite = imagesx($img);
        $hoehe  = imagesy($img);
		        
		$i = 0;
				
		
        for($y = 0; $y < $hoehe;){

			for($x = 0; $x < $breite;) {

				
				if($this->_iQualityLevel == 1){
				
					$index = imagecolorat($img, $x, $y); //farbwert aktueller pixel
					
					// umrechnung in rgb werte und addieren:
					$r = ($index >> 16) & 0xFF;
					$g = ($index >> 8) & 0xFF;
					$b = $index & 0xFF;
				
				} else {
					
					$imgTemp = imagecreatetruecolor($iPixelSize,$iPixelSize);
					imagecopy($imgTemp,$img,0,0,$x,$y,$iPixelSize,$iPixelSize);				
					$aColorTemp = $this->_getMainColor($imgTemp);
					$r = $aColorTemp['r'];
					$g = $aColorTemp['g'];
					$b = $aColorTemp['b'];
					
				} 
				$sPixelData = '';

				$sPixelData = $this->searchPictureForColor($r,$g,$b);

				$destX = $x * $xScale;
				$destY = $y * $yScale;
				
				$iColor = imagecolorallocate($rPicture, $r, $g, $b);
				imagefilledrectangle($rPicture, $destX, $destY, ($destX+$xScale) * $iPixelSize, ($destY+$yScale) * $iPixelSize, $iColor);

				if(!empty($sPixelData)) {
					$rPixel = imagecreatefromjpeg($sPixelData);
					$aImageSize = getimagesize($sPixelData);
					imagecopyresampled($rPicture, $rPixel, $destX, $destY, 0, 0, $xScale*$iPixelSize, $yScale*$iPixelSize, $aImageSize[0], $aImageSize[1]);
				} 
				$i++;
		
				$x = $x + $iPixelSize;
			}
	
			$y = $y + $iPixelSize;
        }

        header('Content-type: image/jpeg');
		imagejpeg($rPicture);
		die();
		
	}
		
	/**
	 * Komplimentär farbe
	 * @param $farbe HEX wert
	 * @return HEX 
	 */
	public function chex(&$farbe)
	{
	
		return $komp=substr(dechex(~hexdec($farbe)),-6); 
		
	}
	public function rgb2hex($r, $g=-1, $b=-1)
	{
	    if (is_array($r) && sizeof($r) == 3)
	        list($r, $g, $b) = $r;
	
	    $r = intval($r); $g = intval($g);
	    $b = intval($b);
	
	    $r = dechex($r<0?0:($r>255?255:$r));
	    $g = dechex($g<0?0:($g>255?255:$g));
	    $b = dechex($b<0?0:($b>255?255:$b));
	
	    $color = (strlen($r) < 2?'0':'').$r;
	    $color .= (strlen($g) < 2?'0':'').$g;
	    $color .= (strlen($b) < 2?'0':'').$b;
	    return $color;
	}
	public function hex2dez(&$hex)
	{
	    return hexdec($hex);
	}
	
	public function decToRGB(&$index){
		$r = ($index >> 16) & 0xFF;
		$g = ($index >> 8) & 0xFF;
		$b = $index & 0xFF;
	}
	
	public static function betweenScale(&$iOrginal,&$iPool){

		if(	
			($iOrginal >= ($iPool-10)) && 
			($iOrginal <= ($iPool+10))
		){
			return true;	
		}
		return false;	
	}
		
	public function searchPictureForColor(&$r, &$g, &$b){
		
		// Wurde schoneinmal für die gleiche kombi ei bild gefunden nutze dieses
		if(isset($this->_aPictureFound[$r.'-'.$g.'-'.$b])){
			return $this->_aPictureFound[$r.'-'.$g.'-'.$b];
		}
		
		$aRPath = array();
		$aGPath = array();
		$aBPath = array();

		for($i = 0; $i <= $this->_iColorScale ; $i++){
			
			// R
			$ii = $r + $i;
			if($ii <= 255){
				if(key_exists($ii,$this->_aColorIndex['all']['r'])){
					$aRPath[] = $this->_aColorIndex['all']['r'][$ii];
				}
			}
			$ii = $r - $i;
			if($ii >= 0){
				if(key_exists($ii,$this->_aColorIndex['all']['r'])){
					$aRPath[] = $this->_aColorIndex['all']['r'][$ii];
				}
			}
			
			// G
			$ii = $g + $i;
			if($ii <= 255){
				if(key_exists($ii,$this->_aColorIndex['all']['g'])){
					$aGPath[] = $this->_aColorIndex['all']['g'][$ii];
				}
			}
			$ii = $g - $i;
			if($ii >= 0){
				if(key_exists($ii,$this->_aColorIndex['all']['g'])){
					$aGPath[] = $this->_aColorIndex['all']['g'][$ii];
				}
			}
			
			// B
			$ii = $b + $i;
			if($ii <= 255){
				if(key_exists($ii,$this->_aColorIndex['all']['b'])){
					$aBPath[] = $this->_aColorIndex['all']['b'][$ii];
				}
			}
			
			$ii = $b - $i;
			if($ii >= 0){
				if(key_exists($ii,$this->_aColorIndex['all']['b'])){
					$aBPath[] = $this->_aColorIndex['all']['b'][$ii];
				}
			}
			
		}

		$aArray = array_intersect($aRPath,$aGPath,$aBPath);
		//shuffle($aArray);

		$this->_aPictureFound[$r.'-'.$g.'-'.$b] = reset($aArray);
		
		return $this->_aPictureFound[$r.'-'.$g.'-'.$b];
		
	}
	
	public function getPictureOfDir($sDir){
		$aPictures = array();
		if ( is_dir ( $sDir ))
		{
		    // öffnen des Verzeichnisses
		    if ( $handle = opendir($sDir) )
		    {
		    	// einlesen der Verzeichnisses
		        while (($file = readdir($handle)) !== false)
		        {
					if($file != '.' && $file != '..' && $file != "csv"){
			        	$aTemp = explode('.',$file);
						if(substr(strtolower($file), -4) == '.jpg' || substr(strtolower($file), -5) == '.jpeg'){
							$aPictures[] = $sDir."/".$file;
						}
						
						if(is_dir($sDir."/".$file)){
							
							$aTemp = $this->getPictureOfDir($sDir."/".$file);
							$aPictures = array_merge($aPictures,$aTemp);
						}
					}
		        }
		    }
		}
		return $aPictures;
	}
	
	public function getPictureIndex(){
		
		
		
		$aPictureIndex = array();
		$sDir = $this->_sPicturePool;

		$aPictures = $this->getPictureOfDir($sDir);
		
		foreach($aPictures as $sPath){
			$aColors = $this->getMainColor($this->_iIndexQuality,$sPath);
		    $aPictureIndex[$sPath] = $aColors;
		}
		unset($aColors);
		unset($aPictures);
		
		return $aPictureIndex;
		
	}
	
	
	function createThumb($img_src, $img_width , $img_height) {
      $im = imagecreatefromjpeg($img_src);
      list($src_width, $src_height) = getimagesize($img_src);
      if($src_width <= 200){
      	return false;
      }
      if($src_width >= $src_height) {
         $new_image_width = $img_width;
         $new_image_height = $src_height * $img_width / $src_width;
      }
      if($src_width < $src_height) {
         $new_image_height = $img_width;
         $new_image_width = $src_width * $img_height / $src_height;
      }
      $new_image = imagecreatetruecolor($new_image_width, $new_image_height);
      imagecopyresized($new_image, $im, 0, 0, 0, 0, $new_image_width,$new_image_height, $src_width, $src_height);
      imagejpeg($new_image, $img_src, 100);
      @chmod($img_src,0777);
   }
	
	/* gibt die durchschnittsfarbe eines bildes als array mit rgb-werten zurück.
	   die varialble quali definiert die abtast-qualität (50% = jedes 2. pixel)
	*/
	public function getMainColor(&$quali,$sPicture = '') {
	    
		$this->createThumb($sPicture,200,150);

		$jpgFile = $this->_sPicture;
        if($sPicture != ""){
        	 $jpgFile = $sPicture;
        }
		
        $img = imagecreatefromjpeg($jpgFile); // datei öffnen
        if($img === FALSE){
        	unlink($jpgFile); // beschädigte bilder löschen
        }
        return $this->_getMainColor($img,$quali);
	}
	
	protected function _getMainColor(&$img,$quali = 0) {
	    
		if($quali <= 0){
			$quali = 100;
		}
		
		$quali = ($quali>100)?100:$quali;
        $quali = ($quali<=0)?1:$quali;
		
		$breite = imagesx($img);
        $hoehe  = imagesy($img);
        $stepsX = round($breite / ($breite * ($quali/100)),0); // schrittweite x berechnen  
        $stepsY = round($breite / ($breite * ($quali/100)),0); // schrittweite y berechnen  
        $anzahlMessungen = 0;
        for($y = 0; $y < $breite; $y+=$stepsY){
        	for($x = 0; $x < $hoehe; $x+=$stepsX) {       		
        		$index = imagecolorat($img,$x,$y); //farbwert aktueller pixel
                // umrechnung in rgb werte und addieren:
                $r += ($index >> 16) & 0xFF;
                $g += ($index >> 8) & 0xFF;
                $b += $index & 0xFF;
                $anzahlMessungen++;
        	}
        }
        // durchschnittliche farbwerte berechnen
        $color['r']   = (int)round($r / $anzahlMessungen, 0);
        $color['g'] = (int)round($g / $anzahlMessungen, 0);
        $color['b']  = (int)round($b / $anzahlMessungen, 0);
        return $color;
	}
	
	public function importImagesFromFlickrCSV(){
		
		//ignore_user_abort(true);
		
		$sFile = \Util::getDocumentRoot().'media/mosaik/csv/ImageInformations.csv';
		
		$handle = fopen ($sFile,"r");              // Datei zum Lesen öffnen
		
		$sPath = \Util::getDocumentRoot().'media/mosaik/flickr/';
		
		@mkdir($sPath);
		@chmod($sPath,0777);
		$i = 0;
		while ( ($data = fgetcsv ($handle, 1000, ";")) !== FALSE ) {
	
			$aTemp = explode('|',trim($data[3]));
			$sPicture =  trim($aTemp[1]);
			$sZiel = $sPath.$data[0];
			if(strpos($sPicture,'http') !== FALSE && !is_file($sZiel)){
			    
				copy($sPicture,$sZiel);

				$this->createThumb($sZiel,200,150);
			}
			$i++;
		}

		fclose ($handle); 
		
	}
}