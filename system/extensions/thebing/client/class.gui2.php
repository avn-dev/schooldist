<?php
/* 
 *
 * Font-Konverter (zugelassene Formate: ttf, otf)
 * 
 * a)Dateiname umwandeln (lowercase)
 * b)generiere Fonts metriken  (ttf2ufm -a -F font.tff)
 * c)makefont ausführen  (php -q makefont.php font.tff myfont.ufm)
 * d)editiere und kopiere die resultierende Datei. Für eingebettete Schriftarten: Kopiere die resultierende TCPDF.
 * e)Benennen php Font-Dateien Varianten mit dem folgenden Schema:*[Basic-font-name-in-Kleinschreibung]. Php für normale Schrift
 */
class Ext_Thebing_Client_Gui2 extends Ext_Thebing_Gui2_Data{
	
	/**
	 *
	 * @global <type> $system_data
	 * @param <type> $_VARS 
	 */
	public function switchAjaxRequest($_VARS) {
		global $system_data;
		
	}
	
}
