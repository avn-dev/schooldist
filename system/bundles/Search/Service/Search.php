<?php

namespace Search\Service;

class Search {

	var $s_text;
	var $s_word_list;
	var $s_plus_list;
	var $s_minus_list;
	var $s_wordreject;
	var $site;

	var $buf_head;
	var $buf_opt;

	var $head_opt_list;

	var $opt_start;
	var $opt_end;
	var $opt_criticalhit;
	var $opt_list_colorchange;
	var $opt_list_object;

	var $obj_list = [];

	/**
	 * @var Site
	 */
	protected $oSite;
				
	function __construct(\Cms\Entity\Site $oSite) {

		global $search_page_num, $s_ext_list, $page_limit;

		$this->s_wordreject = Array(
			"ich", "du", "er", "sie", "es", "wir", "ihr", "ihn", "uns",
			"die", "der", "das",
			"zu", "zur", "bis", "von", "und", "oder",
			"im", "in", "um", "aus", "unter"
		);

		$this->page_num = (($search_page_num)?$search_page_num:0);
		$this->page_limit = $page_limit;

		$this->oSite = $oSite;

		$this->sTmpTableName = 'search_index_'.\Util::generateRandomString(10);

		$sSql = "CREATE TEMPORARY TABLE #table SELECT `pageId` as `page_id`, `path` as `url`, `title`, `description` as `desc`, `content` as `text` FROM search_index WHERE `site_id` = :site_id";
		$aSql = [
			'table' => $this->sTmpTableName,
			'site_id' => (int)$this->oSite->id
		];
		\DB::executePreparedQuery($sSql, $aSql);

		$this->limit_opt_list = array(250,"Zu viele Treffer");

	}
	
	function find_word_list_sql( $text, array $word_list ) {
		$word_count = 0;

		for( $i=0; $i<count($word_list); $i++ ) {
			if( $word_list[$i][0] != "%" ) {
				// word has to be at beginning of text
				$tmpl = substr( strtolower($word_list[$i]), 0, (strlen($word_list[$i])-1) );
				if( !strncmp( $text, $tmpl, strlen( $tmpl ) ) ) {
					$word_count++;
				}
				continue;
			}
			if( $word_list[$i][(strlen($word_list[$i])-1)] != "%" ) {
				// word has to be at end of text
				$tmpl = substr( strtolower($word_list[$i]), 1 );
				$tmp  = substr( $text, (strlen($text)-strlen($tmpl)) );
				if( !strcmp( $tmp, $tmpl ) ) {
					$word_count++;
				}
				continue;
			}

			$tmpl = substr( strtolower($word_list[$i]), 0, (strlen($word_list[$i])-1) );
			$tmpl = substr( $tmpl, 1 );

			$word_count += substr_count( $text, $tmpl );
		}

		return $word_count;
	}
	
	function get_word_list_sql($word) {

		$suche_words = Array();

		$suche_words[]=$word." %";
		$suche_words[]=$word.",%";
		$suche_words[]=$word.".%";
		$suche_words[]=$word."-%";
		$suche_words[]=$word."\\n%";

		$suche_words[]="%\\n".$word." %";
		$suche_words[]="%\\n".$word.",%";
		$suche_words[]="%\\n".$word.".%";
		$suche_words[]="%\\n".$word."-%";
		$suche_words[]="%\\n".$word."\\n%";
		$suche_words[]="%\\n".$word."\\r\\n%";

		$suche_words[]="%\\\"".$word."\\\"%";

		$suche_words[]="%\\\"".$word." %";
		$suche_words[]="%\\\"".$word.",%";
		$suche_words[]="%\\\"".$word.".%";
		$suche_words[]="%\\\"".$word."-%";
		$suche_words[]="%\\\"".$word."\\n%";
		$suche_words[]="%\\\"".$word."\\r\\n%";

		$suche_words[]="% ".$word."\\\"%";
		$suche_words[]="%-".$word."\\\"%";
		$suche_words[]="%\\n".$word."\\\"%";

		$suche_words[]="%-".$word."-%";

		$suche_words[]="%-".$word." %";
		$suche_words[]="%-".$word.",%";
		$suche_words[]="%-".$word.".%";
		$suche_words[]="%-".$word."\\n%";
		$suche_words[]="%-".$word."\\r\\n%";

		$suche_words[]="% ".$word."-%";
		$suche_words[]="%\\n".$word."-%";

		$suche_words[]="% ".$word;
		$suche_words[]="%-".$word;
		$suche_words[]="%\\n".$word;

		$suche_words[]="% ".$word." %";
		$suche_words[]="% ".$word.",%";
		$suche_words[]="% ".$word.".%";
		$suche_words[]="% ".$word."\\n%";
		$suche_words[]="% ".$word."\\r\\n%";

		return $suche_words;
	}
	  
	function run( $s_text ) {
		global $site_data, $user_data, $s_dir_query, $s_ext_list;

		// old time ~26sec
		// new time ~10sec (260% faster!)

		$this->ps_hiscore = 0.00;
		$this->ps_islimit = 0;

		$s_text = trim( stripslashes( $s_text ) );
		$this->s_text = $s_text;
		$s_text = str_replace( "'", "\'", strtolower( $s_text ) );

		$s_ps_mod_list = Array();

		if( !strlen( $s_text ) ) return;

		$s_plus_list = Array();
		$s_minus_list = Array();

		// Parse s_text to find "-Pairs, Single Words, + and -
		preg_match_all( "/(\").*?(\")/i", $s_text, $rval_a );
		$rval2_a = Array();
		for( $i=0; $i<count( $rval_a[0] ); $i++ ) {
			$s_pos = strpos( $s_text, $rval_a[0][$i] );
			$s_plus = 0;
			$s_minus = 0;
			if( ($s_pos > 0) && ( $s_text[$s_pos-1] == "+" ) ) {
				$s_plus++;
				$s_text = substr( $s_text, 0, $s_pos-1 ).substr( $s_text, $s_pos );
			} else if( ($s_pos > 0) && ( $s_text[$s_pos-1] == "-" ) ) {
				$s_minus++;
				$s_text = substr( $s_text, 0, $s_pos-1 ).substr( $s_text, $s_pos );
			}
			$rval_a[0][$i] = str_replace( "\"", "", $rval_a[0][$i] );
			$s_text = trim( str_replace( "\"".$rval_a[0][$i]."\"", "", $s_text ) );
			if( $s_plus ) {
				$s_plus_list[] = $rval_a[0][$i];
				$rval2_a[] = $rval_a[0][$i];
			} else if( $s_minus ) {
				$s_minus_list[] = $rval_a[0][$i];
			} else {
				$rval2_a[] = $rval_a[0][$i];
			}
		}

		if( strlen( $s_text ) ) $s_word_list = explode( " ", $s_text );
		$s_word_list_new = Array();
		for( $i=0; $i<count( $s_word_list ); $i++ ) {
			if( $s_word_list[$i][0] == "+" ) {
				$s_word_list[$i] = substr( $s_word_list[$i], 1 );
				$s_plus_list[] = $s_word_list[$i];
				$s_word_list_new[] = $s_word_list[$i];
			} else if( $s_word_list[$i][0] == "-" ) {
				$s_word_list[$i] = substr( $s_word_list[$i], 1 );
				$s_minus_list[] = $s_word_list[$i];
			} else {
				$s_word_list_new[] = $s_word_list[$i];
			}
		}
		$s_word_list = $s_word_list_new;
		for( $i=0; $i<count( $rval2_a ); $i++ ) {
		  $s_word_list[] = $rval2_a[$i];
		}

		$this->s_word_list = $s_word_list;
		$this->s_plus_list = $s_plus_list;
		$this->s_minus_list = $s_minus_list;

		$s_word_query = $s_pm_query = $s_pp_query = Array();

		if( count( $s_word_list ) ) {
			if( count( $s_word_list ) > 1 && count( array_diff( $s_word_list, $this->s_wordreject ) ) ) {
			  $aa_tmp = array_diff( $s_word_list, $this->s_wordreject );
			} else {
			  $aa_tmp = $s_word_list;
			}
			$s_word_query[0] = "`title` like( '%".implode( "%') or `title` like( '%", $aa_tmp )."%')";
			$s_word_query[1] = "`desc` like( '%".implode( "%') or `desc` like( '%", $aa_tmp )."%')";
			$s_word_query[2] = "`text` like( '%".implode( "%') or `text` like( '%", $aa_tmp )."%')";
		}

		if( count( $s_minus_list ) ) {
		  $s_pm_query[0] = "`title` not like ( '%".implode( "%') and `title` not like ( '%", $s_minus_list )."%' )";
		  $s_pm_query[1] = "`desc` not like ( '%".implode( "%') and `desc` not like ( '%", $s_minus_list )."%' )";
		  $s_pm_query[2] = "`text` not like ( '%".implode( "%') and `text` not like ( '%", $s_minus_list )."%' )";
		}

		if( count( $s_plus_list ) ) {
		  for( $p=0; $p<count($s_plus_list); $p++ ) {
			$s_pp_query[] = "( `title` like ('%".$s_plus_list[$p]."%') or `desc` like ('%".$s_plus_list[$p]."%') or `text` like ('%".$s_plus_list[$p]."%') )";
		  }
		}
	
	    if( count( $s_pp_query ) ) $s_query[] = implode( " and ", $s_pp_query );
	    if( count( $s_pm_query ) ) $s_query[] = implode( " and ", $s_pm_query );
	    if( count( $s_word_query ) ) $s_query[] = " ( ".implode( " or ", $s_word_query )." ) ";
	
	    $q_enabled = "";
	
		// memtrace_init();
	    $sSql = "
			SELECT 
				`page_id`,
				`url`, 
				`title`, 
				`desc`, 
				`text`, 
				CHAR_LENGTH(text) as `chars` 
			FROM 
				#table 
			WHERE 
				(".implode( " and ", $s_query ).") ".$q_enabled." ".stripslashes( $s_dir_query )." 
			LIMIT 
				0, ".$this->limit_opt_list[0];

		$aSql = [
			'table' => $this->sTmpTableName
		];
		
		if(!$res = \DB::executePreparedQuery($sSql, $aSql)) {
			die(\DB::fetchLastErrorMessage());
		}

	    if( \DB::numRows( $res ) >= $this->limit_opt_list[0] ) {
	    	$this->ps_islimit = 1;
	    }

	    while( $ps = \DB::getRow( $res ) ) {

			$ps_org["title"] = $ps["title"];
			$ps_org["desc"] = $ps["desc"];
			$ps_org["text"] = $ps["text"];
	
			$ps["title"] = strtolower( $ps["title"] );
			$ps["desc"] = strtolower( $ps["desc"] );
			$ps["text"] = strtolower( $ps["text"] );
	
			$ndx = count( $this->obj_list ?? [] );
	
			$ps["ps_score"] = 0.0;
			$ps["ps_criticalhit"] = 0.0;
	
			$s_word_count = Array();
			$s_word_any = 0;
			
			for( $i=0; $i<count( $s_word_list ); $i++ ) {
				$s_word_count[$i] = Array();
	        
	        	$s_word_count[$i]["word"] = $s_word_list[$i];
	
	        	$tmp_list = $this->get_word_list_sql( $s_word_list[$i] );

	        	$s_word_count[$i]["count_wtitle"] = $this->find_word_list_sql( $ps["title"], $tmp_list );
	        	$s_word_count[$i]["count_whead"]  = $this->find_word_list_sql( $ps["desc"], $tmp_list );
	        	$s_word_count[$i]["count_wtext"]  = $this->find_word_list_sql( $ps["text"], $tmp_list );
	
	        	$s_word_count[$i]["wcount"] = ($s_word_count[$i]["count_wtitle"]+$s_word_count[$i]["count_whead"]+$s_word_count[$i]["count_wtext"]);
				
				if($s_word_count[$i]["wcount"] > 0) {
					$s_word_any++;
				}
				
	      	}

	      	if( $s_word_any == count($s_word_list) ) {
	        	$s_word_any = 1;
	      	} else {
	        	$s_word_any = 0;
	      	}
	
	      	$s_text_count = [];
	      	$s_text_count["text"] = implode( " ", $this->s_word_list );
			
			$aWordSearchList = $this->get_word_list_sql($s_text_count["text"]);
			
	      	$s_text_count["count_wtitle"] = $this->find_word_list_sql( $ps["title"], $aWordSearchList);
	      	$s_text_count["count_whead"]  = $this->find_word_list_sql( $ps["desc"], $aWordSearchList);
	      	$s_text_count["count_wtext"]  = $this->find_word_list_sql( $ps["text"], $aWordSearchList);
	      	$s_text_count["wcount"] = ($s_text_count["count_wtitle"]+$s_text_count["count_whead"]+$s_text_count["count_wtext"]);
	
	      	// set critical flag
	      	if( count( explode( " ", $this->s_text ) )>1 ) {
	        	if( $s_word_any ) 
	          		$ps["ps_criticalhit"] += 2.50;
	        	if( $s_text_count["wcount"] > 0 ) 
	          		$ps["ps_criticalhit"] += ($s_text_count["wcount"]*5.00);
	      	}
	
	      	// set score
	      	$tmp_count = 0;
	      	$tmp_wcount = 0;
	
			if(intval($ps["chars"]) > 0) {
				$length_factor = 2150 / intval($ps["chars"]);
			} else {
				$length_factor = 1;
			}
	
	      	for( $i=0; $i<count( $s_word_count ); $i++ ) {
	        	if( count( $s_word_count ) > 1 && in_array( $s_word_count[$i]["word"], $this->s_wordreject ) ) continue;
	
	        	$tmp_score =  ($s_word_count[$i]["count_wtext"] * 0.10)*$length_factor;
	        	$tmp_score += ($s_word_count[$i]["count_whead"] * 0.50);
	        	$tmp_score += ($s_word_count[$i]["count_wtitle"]* 1.00);
	
	        	$tmp_wcount += $s_word_count[$i]["wcount"];
	
	        	$ps["ps_score"] += $tmp_score;
	      	}

	      	// mult critical
	      	if( $ps["ps_criticalhit"] > 0 ) {
				$ps["ps_score"] *= $ps["ps_criticalhit"];
			}

	      	// skip entries without score > 0 if we got normal search text
	      	if( ($ps["ps_score"] == 0) && strlen($s_text_count["text"]) ) {
				continue;
			}
	
	      	if( $ps["ps_score"] > $this->ps_hiscore ) {
				$this->ps_hiscore = $ps["ps_score"];
			}
	
	      	$ps["ps_s_word_count"] = $s_word_count;
	      	$ps["ps_s_text_count"] = $s_text_count;
	
	      	$ps["title"] = $ps_org["title"];
	      	$ps["desc"] = $ps_org["desc"];
	      	
	      	if($ps["desc"] == "") {
	      		// get position of first word
	      		$intPos = strpos($ps_org["text"], $s_word_list[0]);

	      		$intLength = 210;
	      		
	      		if($intPos !== false) {
		      		
		      		$intStart = $intPos - 100;
		      		if($intStart < 0) {
		      			$intStart = 0;
		      		}
		      		$intStart = strpos($ps_org["text"], " ", $intStart);
		      		
		      		$intOffset = ($intStart + $intLength);
		      		if($intOffset > strlen($ps_org["text"])) {
		      			$intLength = strlen($ps_org["text"]) - $intStart;
		      			$intOffset = ($intStart + $intLength);
		      		}
		      		$intEnd = strpos($ps_org["text"], " ", $intOffset);
		      		
	      		} else {
	      			$intStart = 0;
					if(strlen($ps_org["text"]) > $intLength) {
						$intEnd = strpos($ps_org["text"], " ", $intLength);
					} else {
						$intEnd = strlen($ps_org["text"]);
					}
	      		}
	      		$strDesc = substr($ps_org["text"], $intStart, ($intEnd - $intStart));
	      		$ps["desc"] = trim($strDesc);
	      	}
	      	
			// $ps["text"] = $ps_org["text"];
	      	unset( $ps["text"] );
			// before: 40908 kB after: 30944 kB percentage: safes ~24% kB
	
	      	$this->obj_list[$ndx] = $ps;

		}
	}

	function orderby( $str ) {

		if(
			empty($this->obj_list) ||
			!count( $this->obj_list ) 
		) {
			return;
		}

		switch( $str ) {
			case "ps_score":
				usort( $this->obj_list, [$this, 'orderby_score'] );
				break;
		}
	}
	
    // hilfsfunktion
    function orderby_score( $a, $b ) {
		// TODO: critical hits have to be ordered to first positions
		if( ($a["ps_score"] == $b["ps_score"]) ) return 0;
		if( ($a["ps_score"] > $b["ps_score"]) ) return -1;
		if( ($a["ps_score"] < $b["ps_score"]) ) return 1;
    }

}
