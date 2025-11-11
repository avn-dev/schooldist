<?php

namespace Pdf\Service;

use \TCPDF_STATIC;
use \TCPDF_FONT_DATA;

define('K_PATH_FONTS', \Util::getDocumentRoot().'system/bundles/Pdf/Resources/fonts/');

class Fpdi extends \setasign\Fpdi\Tcpdf\Fpdi {

	public string $writingModeVertical = '';
	
	public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
		
		parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
		
		$this->tcpdflink = false;

		$this->setSRGBmode(true);
		
	}
	
	public function extractVisibleText($raw) {
		// Entfernt bekannte TCPDF-Sondertags
		$text = preg_replace('/<cssarray>.*?<\/cssarray>/si', '', $raw);
		$text = preg_replace('/<marker[^>]*\/>/si', '', $text);
		// Entfernt sonstige HTML-Tags
		$text = strip_tags($text);
		return html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	/**
	 * Allows to preserve some HTML formatting (limited support).<br />
	 * IMPORTANT: The HTML must be well formatted - try to clean-up it using an application like HTML-Tidy before submitting.
	 * Supported tags are: a, b, blockquote, br, dd, del, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, small, span, strong, sub, sup, table, tcpdf, td, th, thead, tr, tt, u, ul
	 * NOTE: all the HTML attributes must be enclosed in double-quote.
	 * @param string $html text to display
	 * @param boolean $ln if true add a new line after text (default = true)
	 * @param boolean $fill Indicates if the background must be painted (true) or transparent (false).
	 * @param boolean $reseth if true reset the last cell height (default false).
	 * @param boolean $cell if true add the current left (or right for RTL) padding to each Write (default false).
	 * @param string $align Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
	 * @public
	 */
	public function writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='') {
		$gvars = $this->getGraphicVars();
		// store current values
		$prev_cell_margin = $this->cell_margin;
		$prev_cell_padding = $this->cell_padding;
		$prevPage = $this->page;
		$prevlMargin = $this->lMargin;
		$prevrMargin = $this->rMargin;
		$curfontname = $this->FontFamily;
		$curfontstyle = $this->FontStyle;
		$curfontsize = $this->FontSizePt;
		$curfontascent = $this->getFontAscent($curfontname, $curfontstyle, $curfontsize);
		$curfontdescent = $this->getFontDescent($curfontname, $curfontstyle, $curfontsize);
		$curfontstretcing = $this->font_stretching;
		$curfonttracking = $this->font_spacing;
		$this->newline = true;
		$newline = true;
		$startlinepage = $this->page;
		$minstartliney = $this->y;
		$maxbottomliney = 0;
		$startlinex = $this->x;
		$startliney = $this->y;
		$yshift = 0;
		$loop = 0;
		$curpos = 0;
		$this_method_vars = array();
		$undo = false;
		$fontaligned = false;
		$reverse_dir = false; // true when the text direction is reversed
		$this->premode = false;
		if ($this->inxobj) {
			// we are inside an XObject template
			$pask = count($this->xobjects[$this->xobjid]['annotations']);
		} elseif (isset($this->PageAnnots[$this->page])) {
			$pask = count($this->PageAnnots[$this->page]);
		} else {
			$pask = 0;
		}
		if ($this->inxobj) {
			// we are inside an XObject template
			$startlinepos = strlen($this->xobjects[$this->xobjid]['outdata']);
		} elseif (!$this->InFooter) {
			if (isset($this->footerlen[$this->page])) {
				$this->footerpos[$this->page] = $this->pagelen[$this->page] - $this->footerlen[$this->page];
			} else {
				$this->footerpos[$this->page] = $this->pagelen[$this->page];
			}
			$startlinepos = $this->footerpos[$this->page];
		} else {
			// we are inside the footer
			$startlinepos = $this->pagelen[$this->page];
		}
		$lalign = $align;
		$plalign = $align;
		if ($this->rtl) {
			$w = $this->x - $this->lMargin;
		} else {
			$w = $this->w - $this->rMargin - $this->x;
		}
		$w -= ($this->cell_padding['L'] + $this->cell_padding['R']);
		if ($cell) {
			if ($this->rtl) {
				$this->x -= $this->cell_padding['R'];
				$this->lMargin += $this->cell_padding['L'];
			} else {
				$this->x += $this->cell_padding['L'];
				$this->rMargin += $this->cell_padding['R'];
			}
		}
		if ($this->customlistindent >= 0) {
			$this->listindent = $this->customlistindent;
		} else {
			$this->listindent = $this->GetStringWidth('000000');
		}
		$this->listindentlevel = 0;
		// save previous states
		$prev_cell_height_ratio = $this->cell_height_ratio;
		$prev_listnum = $this->listnum;
		$prev_listordered = $this->listordered;
		$prev_listcount = $this->listcount;
		$prev_lispacer = $this->lispacer;
		$this->listnum = 0;
		$this->listordered = array();
		$this->listcount = array();
		$this->lispacer = '';
		if ((TCPDF_STATIC::empty_string($this->lasth)) OR ($reseth)) {
			// reset row height
			$this->resetLastH();
		}
		$dom = $this->getHtmlDomArray($html);
		$maxel = count($dom);
		$key = 0;
		while ($key < $maxel) {
			if ($dom[$key]['tag'] AND $dom[$key]['opening'] AND $dom[$key]['hide']) {
				// store the node key
				$hidden_node_key = $key;
				if ($dom[$key]['self']) {
					// skip just this self-closing tag
					++$key;
				} else {
					// skip this and all children tags
					while (($key < $maxel) AND (!$dom[$key]['tag'] OR $dom[$key]['opening'] OR ($dom[$key]['parent'] != $hidden_node_key))) {
						// skip hidden objects
						++$key;
					}
					++$key;
				}
			}
			if ($key == $maxel) break;
			if ($dom[$key]['tag'] AND $dom[$key]['opening'] AND !empty($dom[$key]['attribute']['id'])) {
				$this->setDestination($dom[$key]['attribute']['id']);
			}
			if ($dom[$key]['tag'] AND isset($dom[$key]['attribute']['pagebreak'])) {
				// check for pagebreak
				if (($dom[$key]['attribute']['pagebreak'] == 'true') OR ($dom[$key]['attribute']['pagebreak'] == 'left') OR ($dom[$key]['attribute']['pagebreak'] == 'right')) {
					// add a page (or trig AcceptPageBreak() for multicolumn mode)
					$this->checkPageBreak($this->PageBreakTrigger + 1);
					$this->htmlvspace = ($this->PageBreakTrigger + 1);
				}
				if ((($dom[$key]['attribute']['pagebreak'] == 'left') AND (((!$this->rtl) AND (($this->page % 2) == 0)) OR (($this->rtl) AND (($this->page % 2) != 0))))
					OR (($dom[$key]['attribute']['pagebreak'] == 'right') AND (((!$this->rtl) AND (($this->page % 2) != 0)) OR (($this->rtl) AND (($this->page % 2) == 0))))) {
					// add a page (or trig AcceptPageBreak() for multicolumn mode)
					$this->checkPageBreak($this->PageBreakTrigger + 1);
					$this->htmlvspace = ($this->PageBreakTrigger + 1);
				}
			}
			if ($dom[$key]['tag'] AND $dom[$key]['opening'] AND isset($dom[$key]['attribute']['nobr']) AND ($dom[$key]['attribute']['nobr'] == 'true')) {
				if (isset($dom[($dom[$key]['parent'])]['attribute']['nobr']) AND ($dom[($dom[$key]['parent'])]['attribute']['nobr'] == 'true')) {
					$dom[$key]['attribute']['nobr'] = false;
				} else {
					// store current object
					$this->startTransaction();
					// save this method vars
					$this_method_vars['html'] = $html;
					$this_method_vars['ln'] = $ln;
					$this_method_vars['fill'] = $fill;
					$this_method_vars['reseth'] = $reseth;
					$this_method_vars['cell'] = $cell;
					$this_method_vars['align'] = $align;
					$this_method_vars['gvars'] = $gvars;
					$this_method_vars['prevPage'] = $prevPage;
					$this_method_vars['prev_cell_margin'] = $prev_cell_margin;
					$this_method_vars['prev_cell_padding'] = $prev_cell_padding;
					$this_method_vars['prevlMargin'] = $prevlMargin;
					$this_method_vars['prevrMargin'] = $prevrMargin;
					$this_method_vars['curfontname'] = $curfontname;
					$this_method_vars['curfontstyle'] = $curfontstyle;
					$this_method_vars['curfontsize'] = $curfontsize;
					$this_method_vars['curfontascent'] = $curfontascent;
					$this_method_vars['curfontdescent'] = $curfontdescent;
					$this_method_vars['curfontstretcing'] = $curfontstretcing;
					$this_method_vars['curfonttracking'] = $curfonttracking;
					$this_method_vars['minstartliney'] = $minstartliney;
					$this_method_vars['maxbottomliney'] = $maxbottomliney;
					$this_method_vars['yshift'] = $yshift;
					$this_method_vars['startlinepage'] = $startlinepage;
					$this_method_vars['startlinepos'] = $startlinepos;
					$this_method_vars['startlinex'] = $startlinex;
					$this_method_vars['startliney'] = $startliney;
					$this_method_vars['newline'] = $newline;
					$this_method_vars['loop'] = $loop;
					$this_method_vars['curpos'] = $curpos;
					$this_method_vars['pask'] = $pask;
					$this_method_vars['lalign'] = $lalign;
					$this_method_vars['plalign'] = $plalign;
					$this_method_vars['w'] = $w;
					$this_method_vars['prev_cell_height_ratio'] = $prev_cell_height_ratio;
					$this_method_vars['prev_listnum'] = $prev_listnum;
					$this_method_vars['prev_listordered'] = $prev_listordered;
					$this_method_vars['prev_listcount'] = $prev_listcount;
					$this_method_vars['prev_lispacer'] = $prev_lispacer;
					$this_method_vars['fontaligned'] = $fontaligned;
					$this_method_vars['key'] = $key;
					$this_method_vars['dom'] = $dom;
				}
			}
			// print THEAD block
			if (($dom[$key]['value'] == 'tr') AND isset($dom[$key]['thead']) AND $dom[$key]['thead']) {
				if (isset($dom[$key]['parent']) AND isset($dom[$dom[$key]['parent']]['thead']) AND !TCPDF_STATIC::empty_string($dom[$dom[$key]['parent']]['thead'])) {
					$this->inthead = true;
					// print table header (thead)
					$this->writeHTML($this->thead, false, false, false, false, '');
					// check if we are on a new page or on a new column
					if (($this->y < $this->start_transaction_y) OR ($this->checkPageBreak($this->lasth, '', false))) {
						// we are on a new page or on a new column and the total object height is less than the available vertical space.
						// restore previous object
						$this->rollbackTransaction(true);
						// restore previous values
						foreach ($this_method_vars as $vkey => $vval) {
							$$vkey = $vval;
						}
						// disable table header
						$tmp_thead = $this->thead;
						$this->thead = '';
						// add a page (or trig AcceptPageBreak() for multicolumn mode)
						$pre_y = $this->y;
						if ((!$this->checkPageBreak($this->PageBreakTrigger + 1)) AND ($this->y < $pre_y)) {
							// fix for multicolumn mode
							$startliney = $this->y;
						}
						$this->start_transaction_page = $this->page;
						$this->start_transaction_y = $this->y;
						// restore table header
						$this->thead = $tmp_thead;
						// fix table border properties
						if (isset($dom[$dom[$key]['parent']]['attribute']['cellspacing'])) {
							$tmp_cellspacing = $this->getHTMLUnitToUnits($dom[$dom[$key]['parent']]['attribute']['cellspacing'], 1, 'px');
						} elseif (isset($dom[$dom[$key]['parent']]['border-spacing'])) {
							$tmp_cellspacing = $dom[$dom[$key]['parent']]['border-spacing']['V'];
						} else {
							$tmp_cellspacing = 0;
						}
						$dom[$dom[$key]['parent']]['borderposition']['page'] = $this->page;
						$dom[$dom[$key]['parent']]['borderposition']['column'] = $this->current_column;
						$dom[$dom[$key]['parent']]['borderposition']['y'] = $this->y + $tmp_cellspacing;
						$xoffset = ($this->x - $dom[$dom[$key]['parent']]['borderposition']['x']);
						$dom[$dom[$key]['parent']]['borderposition']['x'] += $xoffset;
						$dom[$dom[$key]['parent']]['borderposition']['xmax'] += $xoffset;
						// print table header (thead)
						$this->writeHTML($this->thead, false, false, false, false, '');
					}
				}
				// move $key index forward to skip THEAD block
				while ( ($key < $maxel) AND (!(
					($dom[$key]['tag'] AND $dom[$key]['opening'] AND ($dom[$key]['value'] == 'tr') AND (!isset($dom[$key]['thead']) OR !$dom[$key]['thead']))
					OR ($dom[$key]['tag'] AND (!$dom[$key]['opening']) AND ($dom[$key]['value'] == 'table'))) )) {
					++$key;
				}
			}
			if ($dom[$key]['tag'] OR ($key == 0)) {
				if ((($dom[$key]['value'] == 'table') OR ($dom[$key]['value'] == 'tr')) AND (isset($dom[$key]['align']))) {
					$dom[$key]['align'] = ($this->rtl) ? 'R' : 'L';
				}
				// vertically align image in line
				if ((!$this->newline) AND ($dom[$key]['value'] == 'img') AND (isset($dom[$key]['height'])) AND ($dom[$key]['height'] > 0)) {
					// get image height
					$imgh = $this->getHTMLUnitToUnits($dom[$key]['height'], ($dom[$key]['fontsize'] / $this->k), 'px');
					$autolinebreak = false;
					if (!empty($dom[$key]['width'])) {
						$imgw = $this->getHTMLUnitToUnits($dom[$key]['width'], ($dom[$key]['fontsize'] / $this->k), 'px', false);
						if (($imgw <= ($this->w - $this->lMargin - $this->rMargin - $this->cell_padding['L'] - $this->cell_padding['R']))
							AND ((($this->rtl) AND (($this->x - $imgw) < ($this->lMargin + $this->cell_padding['L'])))
							OR ((!$this->rtl) AND (($this->x + $imgw) > ($this->w - $this->rMargin - $this->cell_padding['R']))))) {
							// add automatic line break
							$autolinebreak = true;
							$this->Ln('', $cell);
							if ((!$dom[($key-1)]['tag']) AND ($dom[($key-1)]['value'] == ' ')) {
								// go back to evaluate this line break
								--$key;
							}
						}
					}
					if (!$autolinebreak) {
						if ($this->inPageBody()) {
							$pre_y = $this->y;
							// check for page break
							if ((!$this->checkPageBreak($imgh)) AND ($this->y < $pre_y)) {
								// fix for multicolumn mode
								$startliney = $this->y;
							}
						}
						if ($this->page > $startlinepage) {
							// fix line splitted over two pages
							if (isset($this->footerlen[$startlinepage])) {
								$curpos = $this->pagelen[$startlinepage] - $this->footerlen[$startlinepage];
							}
							// line to be moved one page forward
							$pagebuff = $this->getPageBuffer($startlinepage);
							$linebeg = substr($pagebuff, $startlinepos, ($curpos - $startlinepos));
							$tstart = substr($pagebuff, 0, $startlinepos);
							$tend = substr($this->getPageBuffer($startlinepage), $curpos);
							// remove line from previous page
							$this->setPageBuffer($startlinepage, $tstart.''.$tend);
							$pagebuff = $this->getPageBuffer($this->page);
							$tstart = substr($pagebuff, 0, $this->cntmrk[$this->page]);
							$tend = substr($pagebuff, $this->cntmrk[$this->page]);
							// add line start to current page
							$yshift = ($minstartliney - $this->y);
							if ($fontaligned) {
								$yshift += ($curfontsize / $this->k);
							}
							$try = sprintf('1 0 0 1 0 %F cm', ($yshift * $this->k));
							$this->setPageBuffer($this->page, $tstart."\nq\n".$try."\n".$linebeg."\nQ\n".$tend);
							// shift the annotations and links
							if (isset($this->PageAnnots[$this->page])) {
								$next_pask = count($this->PageAnnots[$this->page]);
							} else {
								$next_pask = 0;
							}
							if (isset($this->PageAnnots[$startlinepage])) {
								foreach ($this->PageAnnots[$startlinepage] as $pak => $pac) {
									if ($pak >= $pask) {
										$this->PageAnnots[$this->page][] = $pac;
										unset($this->PageAnnots[$startlinepage][$pak]);
										$npak = count($this->PageAnnots[$this->page]) - 1;
										$this->PageAnnots[$this->page][$npak]['y'] -= $yshift;
									}
								}
							}
							$pask = $next_pask;
							$startlinepos = $this->cntmrk[$this->page];
							$startlinepage = $this->page;
							$startliney = $this->y;
							$this->newline = false;
						}
						$this->y += ($this->getCellHeight($curfontsize / $this->k) - ($curfontdescent * $this->cell_height_ratio) - $imgh);
						$minstartliney = min($this->y, $minstartliney);
						$maxbottomliney = ($startliney + $this->getCellHeight($curfontsize / $this->k));
					}
				} elseif (isset($dom[$key]['fontname']) OR isset($dom[$key]['fontstyle']) OR isset($dom[$key]['fontsize']) OR isset($dom[$key]['line-height'])) {
					// account for different font size
					$pfontname = $curfontname;
					$pfontstyle = $curfontstyle;
					$pfontsize = $curfontsize;
					$fontname = (isset($dom[$key]['fontname']) ? $dom[$key]['fontname'] : $curfontname);
					$fontstyle = (isset($dom[$key]['fontstyle']) ? $dom[$key]['fontstyle'] : $curfontstyle);
					$fontsize = (isset($dom[$key]['fontsize']) ? $dom[$key]['fontsize'] : $curfontsize);
					$fontascent = $this->getFontAscent($fontname, $fontstyle, $fontsize);
					$fontdescent = $this->getFontDescent($fontname, $fontstyle, $fontsize);
					if (($fontname != $curfontname) OR ($fontstyle != $curfontstyle) OR ($fontsize != $curfontsize)
						OR ($this->cell_height_ratio != $dom[$key]['line-height'])
						OR ($dom[$key]['tag'] AND $dom[$key]['opening'] AND ($dom[$key]['value'] == 'li')) ) {
						if (($key < ($maxel - 1)) AND (
								($dom[$key]['tag'] AND $dom[$key]['opening'] AND ($dom[$key]['value'] == 'li'))
								OR ($this->cell_height_ratio != $dom[$key]['line-height'])
								OR (!$this->newline AND is_numeric($fontsize) AND is_numeric($curfontsize)
								AND ($fontsize >= 0) AND ($curfontsize >= 0)
								AND (($fontsize != $curfontsize) OR ($fontstyle != $curfontstyle) OR ($fontname != $curfontname)))
							)) {
							if ($this->page > $startlinepage) {
								// fix lines splitted over two pages
								if (isset($this->footerlen[$startlinepage])) {
									$curpos = $this->pagelen[$startlinepage] - $this->footerlen[$startlinepage];
								}
								// line to be moved one page forward
								$pagebuff = $this->getPageBuffer($startlinepage);
								$linebeg = substr($pagebuff, $startlinepos, ($curpos - $startlinepos));
								$tstart = substr($pagebuff, 0, $startlinepos);
								$tend = substr($this->getPageBuffer($startlinepage), $curpos);
								// remove line start from previous page
								$this->setPageBuffer($startlinepage, $tstart.''.$tend);
								$pagebuff = $this->getPageBuffer($this->page);
								$tstart = substr($pagebuff, 0, $this->cntmrk[$this->page]);
								$tend = substr($pagebuff, $this->cntmrk[$this->page]);
								// add line start to current page
								$yshift = ($minstartliney - $this->y);
								$try = sprintf('1 0 0 1 0 %F cm', ($yshift * $this->k));
								$this->setPageBuffer($this->page, $tstart."\nq\n".$try."\n".$linebeg."\nQ\n".$tend);
								// shift the annotations and links
								if (isset($this->PageAnnots[$this->page])) {
									$next_pask = count($this->PageAnnots[$this->page]);
								} else {
									$next_pask = 0;
								}
								if (isset($this->PageAnnots[$startlinepage])) {
									foreach ($this->PageAnnots[$startlinepage] as $pak => $pac) {
										if ($pak >= $pask) {
											$this->PageAnnots[$this->page][] = $pac;
											unset($this->PageAnnots[$startlinepage][$pak]);
											$npak = count($this->PageAnnots[$this->page]) - 1;
											$this->PageAnnots[$this->page][$npak]['y'] -= $yshift;
										}
									}
								}
								$pask = $next_pask;
								$startlinepos = $this->cntmrk[$this->page];
								$startlinepage = $this->page;
								$startliney = $this->y;
							}
							if (!isset($dom[$key]['line-height'])) {
								$dom[$key]['line-height'] = $this->cell_height_ratio;
							}
							if (!$dom[$key]['block']) {
								if (!(isset($dom[($key + 1)]) AND $dom[($key + 1)]['tag'] AND (!$dom[($key + 1)]['opening']) AND ($dom[($key + 1)]['value'] != 'li') AND $dom[$key]['tag'] AND (!$dom[$key]['opening']))) {
									$this->y += (((($curfontsize * $this->cell_height_ratio) - ($fontsize * $dom[$key]['line-height'])) / $this->k) + $curfontascent - $fontascent - $curfontdescent + $fontdescent) / 2;
								}
								if (($dom[$key]['value'] != 'sup') AND ($dom[$key]['value'] != 'sub')) {
									$current_line_align_data = array($key, $minstartliney, $maxbottomliney);
									if (isset($line_align_data) AND (($line_align_data[0] == ($key - 1)) OR (($line_align_data[0] == ($key - 2)) AND (isset($dom[($key - 1)])) AND (preg_match('/^([\s]+)$/', $dom[($key - 1)]['value']) > 0)))) {
										$minstartliney = min($this->y, $line_align_data[1]);
										$maxbottomliney = max(($this->y + $this->getCellHeight($fontsize / $this->k)), $line_align_data[2]);
									} else {
										$minstartliney = min($this->y, $minstartliney);
										$maxbottomliney = max(($this->y + $this->getCellHeight($fontsize / $this->k)), $maxbottomliney);
									}
									$line_align_data = $current_line_align_data;
								}
							}
							$this->cell_height_ratio = $dom[$key]['line-height'];
							$fontaligned = true;
						}
						$this->setFont($fontname, $fontstyle, $fontsize);
						// reset row height
						$this->resetLastH();
						$curfontname = $fontname;
						$curfontstyle = $fontstyle;
						$curfontsize = $fontsize;
						$curfontascent = $fontascent;
						$curfontdescent = $fontdescent;
					}
				}
				// set text rendering mode
				$textstroke = isset($dom[$key]['stroke']) ? $dom[$key]['stroke'] : $this->textstrokewidth;
				$textfill = isset($dom[$key]['fill']) ? $dom[$key]['fill'] : (($this->textrendermode % 2) == 0);
				$textclip = isset($dom[$key]['clip']) ? $dom[$key]['clip'] : ($this->textrendermode > 3);
				$this->setTextRenderingMode($textstroke, $textfill, $textclip);
				if (isset($dom[$key]['font-stretch']) AND ($dom[$key]['font-stretch'] !== false)) {
					$this->setFontStretching($dom[$key]['font-stretch']);
				}
				if (isset($dom[$key]['letter-spacing']) AND ($dom[$key]['letter-spacing'] !== false)) {
					$this->setFontSpacing($dom[$key]['letter-spacing']);
				}
				if (($plalign == 'J') AND $dom[$key]['block']) {
					$plalign = '';
				}
				// get current position on page buffer
				$curpos = $this->pagelen[$startlinepage];
				if (isset($dom[$key]['bgcolor']) AND ($dom[$key]['bgcolor'] !== false)) {
					$this->setFillColorArray($dom[$key]['bgcolor']);
					$wfill = true;
				} else {
					$wfill = $fill | false;
				}
				if (isset($dom[$key]['fgcolor']) AND ($dom[$key]['fgcolor'] !== false)) {
					$this->setTextColorArray($dom[$key]['fgcolor']);
				}
				if (isset($dom[$key]['strokecolor']) AND ($dom[$key]['strokecolor'] !== false)) {
					$this->setDrawColorArray($dom[$key]['strokecolor']);
				}
				if (isset($dom[$key]['align'])) {
					$lalign = $dom[$key]['align'];
				}
				if (TCPDF_STATIC::empty_string($lalign)) {
					$lalign = $align;
				}
			}
			// align lines
			if ($this->newline AND (strlen($dom[$key]['value']) > 0) AND ($dom[$key]['value'] != 'td') AND ($dom[$key]['value'] != 'th')) {
				$newline = true;
				$fontaligned = false;
				// we are at the beginning of a new line
				if (isset($startlinex)) {
					$yshift = ($minstartliney - $startliney);
					if (($yshift > 0) OR ($this->page > $startlinepage)) {
						$yshift = 0;
					}
					$t_x = 0;
					// the last line must be shifted to be aligned as requested
					$linew = abs($this->endlinex - $startlinex);
					if ($this->inxobj) {
						// we are inside an XObject template
						$pstart = substr($this->xobjects[$this->xobjid]['outdata'], 0, $startlinepos);
						if (isset($opentagpos)) {
							$midpos = $opentagpos;
						} else {
							$midpos = 0;
						}
						if ($midpos > 0) {
							$pmid = substr($this->xobjects[$this->xobjid]['outdata'], $startlinepos, ($midpos - $startlinepos));
							$pend = substr($this->xobjects[$this->xobjid]['outdata'], $midpos);
						} else {
							$pmid = substr($this->xobjects[$this->xobjid]['outdata'], $startlinepos);
							$pend = '';
						}
					} else {
						$pstart = substr($this->getPageBuffer($startlinepage), 0, $startlinepos);
						if (isset($opentagpos) AND isset($this->footerlen[$startlinepage]) AND (!$this->InFooter)) {
							$this->footerpos[$startlinepage] = $this->pagelen[$startlinepage] - $this->footerlen[$startlinepage];
							$midpos = min($opentagpos, $this->footerpos[$startlinepage]);
						} elseif (isset($opentagpos)) {
							$midpos = $opentagpos;
						} elseif (isset($this->footerlen[$startlinepage]) AND (!$this->InFooter)) {
							$this->footerpos[$startlinepage] = $this->pagelen[$startlinepage] - $this->footerlen[$startlinepage];
							$midpos = $this->footerpos[$startlinepage];
						} else {
							$midpos = 0;
						}
						if ($midpos > 0) {
							$pmid = substr($this->getPageBuffer($startlinepage), $startlinepos, ($midpos - $startlinepos));
							$pend = substr($this->getPageBuffer($startlinepage), $midpos);
						} else {
							$pmid = substr($this->getPageBuffer($startlinepage), $startlinepos);
							$pend = '';
						}
					}
					if ((((($plalign == 'C') OR ($plalign == 'J') OR (($plalign == 'R') AND (!$this->rtl)) OR (($plalign == 'L') AND ($this->rtl)))))) {
						// calculate shifting amount
						$tw = $w;
						if (($plalign == 'J') AND $this->isRTLTextDir() AND ($this->num_columns > 1)) {
							$tw += $this->cell_padding['R'];
						}
						if ($this->lMargin != $prevlMargin) {
							$tw += ($prevlMargin - $this->lMargin);
						}
						if ($this->rMargin != $prevrMargin) {
							$tw += ($prevrMargin - $this->rMargin);
						}
						$one_space_width = $this->GetStringWidth(chr(32));
						$no = 0; // number of spaces on a line contained on a single block
						if ($this->isRTLTextDir()) { // RTL
							// remove left space if exist
							$pos1 = TCPDF_STATIC::revstrpos($pmid, '[(');
							if ($pos1 > 0) {
								$pos1 = intval($pos1);
								if ($this->isUnicodeFont()) {
									$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, '[('.chr(0).chr(32)));
									$spacelen = 2;
								} else {
									$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, '[('.chr(32)));
									$spacelen = 1;
								}
								if ($pos1 == $pos2) {
									$pmid = substr($pmid, 0, ($pos1 + 2)).substr($pmid, ($pos1 + 2 + $spacelen));
									if (substr($pmid, $pos1, 4) == '[()]') {
										$linew -= $one_space_width;
									} elseif ($pos1 == strpos($pmid, '[(')) {
										$no = 1;
									}
								}
							}
						} else { // LTR
							// remove right space if exist
							$pos1 = TCPDF_STATIC::revstrpos($pmid, ')]');
							if ($pos1 > 0) {
								$pos1 = intval($pos1);
								if ($this->isUnicodeFont()) {
									$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, chr(0).chr(32).')]')) + 2;
									$spacelen = 2;
								} else {
									$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, chr(32).')]')) + 1;
									$spacelen = 1;
								}
								if ($pos1 == $pos2) {
									$pmid = substr($pmid, 0, ($pos1 - $spacelen)).substr($pmid, $pos1);
									$linew -= $one_space_width;
								}
							}
						}
						$mdiff = ($tw - $linew);
						if ($plalign == 'C') {
							if ($this->rtl) {
								$t_x = -($mdiff / 2);
							} else {
								$t_x = ($mdiff / 2);
							}
						} elseif ($plalign == 'R') {
							// right alignment on LTR document
							$t_x = $mdiff;
						} elseif ($plalign == 'L') {
							// left alignment on RTL document
							$t_x = -$mdiff;
						} elseif (($plalign == 'J') AND ($plalign == $lalign)) {
							// Justification
							if ($this->isRTLTextDir()) {
								// align text on the left
								$t_x = -$mdiff;
							}
							$ns = 0; // number of spaces
							$pmidtemp = $pmid;
							// escape special characters
							$pmidtemp = preg_replace('/[\\\][\(]/x', '\\#!#OP#!#', $pmidtemp);
							$pmidtemp = preg_replace('/[\\\][\)]/x', '\\#!#CP#!#', $pmidtemp);
							// search spaces
							if (preg_match_all('/\[\(([^\)]*)\)\]/x', $pmidtemp, $lnstring, PREG_PATTERN_ORDER)) {
								$spacestr = $this->getSpaceString();
								$maxkk = count($lnstring[1]) - 1;
								for ($kk=0; $kk <= $maxkk; ++$kk) {
									// restore special characters
									$lnstring[1][$kk] = str_replace('#!#OP#!#', '(', $lnstring[1][$kk]);
									$lnstring[1][$kk] = str_replace('#!#CP#!#', ')', $lnstring[1][$kk]);
									// store number of spaces on the strings
									$lnstring[2][$kk] = substr_count($lnstring[1][$kk], $spacestr);
									// count total spaces on line
									$ns += $lnstring[2][$kk];
									$lnstring[3][$kk] = $ns;
								}
								if ($ns == 0) {
									$ns = 1;
								}
								// calculate additional space to add to each existing space
								$spacewidth = ($mdiff / ($ns - $no)) * $this->k;
								if ($this->FontSize <= 0) {
									$this->FontSize = 1;
								}
								$spacewidthu = -1000 * ($mdiff + (($ns + $no) * $one_space_width)) / $ns / $this->FontSize;
								if ($this->font_spacing != 0) {
									// fixed spacing mode
									$osw = -1000 * $this->font_spacing / $this->FontSize;
									$spacewidthu += $osw;
								}
								$nsmax = $ns;
								$ns = 0;
								reset($lnstring);
								$offset = 0;
								$strcount = 0;
								$prev_epsposbeg = 0;
								$textpos = 0;
								if ($this->isRTLTextDir()) {
									$textpos = $this->wPt;
								}
								while (preg_match('/([0-9\.\+\-]*)[\s](Td|cm|m|l|c|re)[\s]/x', $pmid, $strpiece, PREG_OFFSET_CAPTURE, $offset) == 1) {
									// check if we are inside a string section '[( ... )]'
									$stroffset = strpos($pmid, '[(', $offset);
									if (($stroffset !== false) AND ($stroffset <= $strpiece[2][1])) {
										// set offset to the end of string section
										$offset = strpos($pmid, ')]', $stroffset);
										while (($offset !== false) AND ($pmid[($offset - 1)] == '\\')) {
											$offset = strpos($pmid, ')]', ($offset + 1));
										}
										if ($offset === false) {
											$this->Error('HTML Justification: malformed PDF code.');
										}
										continue;
									}
									if ($this->isRTLTextDir()) {
										$spacew = ($spacewidth * ($nsmax - $ns));
									} else {
										$spacew = ($spacewidth * $ns);
									}
									$offset = $strpiece[2][1] + strlen($strpiece[2][0]);
									$epsposend = strpos($pmid, $this->epsmarker.'Q', $offset);
									if ($epsposend !== null) {
										$epsposend += strlen($this->epsmarker.'Q');
										$epsposbeg = strpos($pmid, 'q'.$this->epsmarker, $offset);
										if ($epsposbeg === null) {
											$epsposbeg = strpos($pmid, 'q'.$this->epsmarker, ($prev_epsposbeg - 6));
											$prev_epsposbeg = $epsposbeg;
										}
										if (($epsposbeg > 0) AND ($epsposend > 0) AND ($offset > $epsposbeg) AND ($offset < $epsposend)) {
											// shift EPS images
											$trx = sprintf('1 0 0 1 %F 0 cm', $spacew);
											$pmid_b = substr($pmid, 0, $epsposbeg);
											$pmid_m = substr($pmid, $epsposbeg, ($epsposend - $epsposbeg));
											$pmid_e = substr($pmid, $epsposend);
											$pmid = $pmid_b."\nq\n".$trx."\n".$pmid_m."\nQ\n".$pmid_e;
											$offset = $epsposend;
											continue;
										}
									}
									$currentxpos = 0;
									// shift blocks of code
									switch ($strpiece[2][0]) {
										case 'Td':
										case 'cm':
										case 'm':
										case 'l': {
											// get current X position
											preg_match('/([0-9\.\+\-]*)[\s]('.$strpiece[1][0].')[\s]('.$strpiece[2][0].')([\s]*)/x', $pmid, $xmatches);
											if (!isset($xmatches[1])) {
												break;
											}
											$currentxpos = $xmatches[1];
											$textpos = $currentxpos;
											if (($strcount <= $maxkk) AND ($strpiece[2][0] == 'Td')) {
												$ns = $lnstring[3][$strcount];
												if ($this->isRTLTextDir()) {
													$spacew = ($spacewidth * ($nsmax - $ns));
												}
												++$strcount;
											}
											// justify block
											if (preg_match('/([0-9\.\+\-]*)[\s]('.$strpiece[1][0].')[\s]('.$strpiece[2][0].')([\s]*)/x', $pmid, $pmatch) == 1) {
												$newpmid = sprintf('%F',(floatval($pmatch[1]) + $spacew)).' '.$pmatch[2].' x*#!#*x'.$pmatch[3].$pmatch[4];
												$pmid = str_replace($pmatch[0], $newpmid, $pmid);
												unset($pmatch, $newpmid);
											}
											break;
										}
										case 're': {
											// justify block
											if (!TCPDF_STATIC::empty_string($this->lispacer)) {
												$this->lispacer = '';
												break;
											}
											preg_match('/([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]('.$strpiece[1][0].')[\s](re)([\s]*)/x', $pmid, $xmatches);
											if (!isset($xmatches[1])) {
												break;
											}
											$currentxpos = $xmatches[1];
											$x_diff = 0;
											$w_diff = 0;
											if ($this->isRTLTextDir()) { // RTL
												if ($currentxpos < $textpos) {
													$x_diff = ($spacewidth * ($nsmax - $lnstring[3][$strcount]));
													$w_diff = ($spacewidth * $lnstring[2][$strcount]);
												} else {
													if ($strcount > 0) {
														$x_diff = ($spacewidth * ($nsmax - $lnstring[3][($strcount - 1)]));
														$w_diff = ($spacewidth * $lnstring[2][($strcount - 1)]);
													}
												}
											} else { // LTR
												if ($currentxpos > $textpos) {
													if ($strcount > 0) {
														$x_diff = ($spacewidth * $lnstring[3][($strcount - 1)]);
													}
													$w_diff = ($spacewidth * $lnstring[2][$strcount]);
												} else {
													if ($strcount > 1) {
														$x_diff = ($spacewidth * $lnstring[3][($strcount - 2)]);
													}
													if ($strcount > 0) {
														$w_diff = ($spacewidth * $lnstring[2][($strcount - 1)]);
													}
												}
											}
											if (preg_match('/('.$xmatches[1].')[\s]('.$xmatches[2].')[\s]('.$xmatches[3].')[\s]('.$strpiece[1][0].')[\s](re)([\s]*)/x', $pmid, $pmatch) == 1) {
												$newx = sprintf('%F',(floatval($pmatch[1]) + $x_diff));
												$neww = sprintf('%F',(floatval($pmatch[3]) + $w_diff));
												$newpmid = $newx.' '.$pmatch[2].' '.$neww.' '.$pmatch[4].' x*#!#*x'.$pmatch[5].$pmatch[6];
												$pmid = str_replace($pmatch[0], $newpmid, $pmid);
												unset($pmatch, $newpmid, $newx, $neww);
											}
											break;
										}
										case 'c': {
											// get current X position
											preg_match('/([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]('.$strpiece[1][0].')[\s](c)([\s]*)/x', $pmid, $xmatches);
											if (!isset($xmatches[1])) {
												break;
											}
											$currentxpos = $xmatches[1];
											// justify block
											if (preg_match('/('.$xmatches[1].')[\s]('.$xmatches[2].')[\s]('.$xmatches[3].')[\s]('.$xmatches[4].')[\s]('.$xmatches[5].')[\s]('.$strpiece[1][0].')[\s](c)([\s]*)/x', $pmid, $pmatch) == 1) {
												$newx1 = sprintf('%F',(floatval($pmatch[1]) + $spacew));
												$newx2 = sprintf('%F',(floatval($pmatch[3]) + $spacew));
												$newx3 = sprintf('%F',(floatval($pmatch[5]) + $spacew));
												$newpmid = $newx1.' '.$pmatch[2].' '.$newx2.' '.$pmatch[4].' '.$newx3.' '.$pmatch[6].' x*#!#*x'.$pmatch[7].$pmatch[8];
												$pmid = str_replace($pmatch[0], $newpmid, $pmid);
												unset($pmatch, $newpmid, $newx1, $newx2, $newx3);
											}
											break;
										}
									}
									// shift the annotations and links
									$cxpos = ($currentxpos / $this->k);
									$lmpos = ($this->lMargin + $this->cell_padding['L'] + $this->feps);
									if ($this->inxobj) {
										// we are inside an XObject template
										foreach ($this->xobjects[$this->xobjid]['annotations'] as $pak => $pac) {
											if (($pac['y'] >= $minstartliney) AND (($pac['x'] * $this->k) >= ($currentxpos - $this->feps)) AND (($pac['x'] * $this->k) <= ($currentxpos + $this->feps))) {
												if ($cxpos > $lmpos) {
													$this->xobjects[$this->xobjid]['annotations'][$pak]['x'] += ($spacew / $this->k);
													$this->xobjects[$this->xobjid]['annotations'][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->k);
												} else {
													$this->xobjects[$this->xobjid]['annotations'][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->k);
												}
												break;
											}
										}
									} elseif (isset($this->PageAnnots[$this->page])) {
										foreach ($this->PageAnnots[$this->page] as $pak => $pac) {
											if (($pac['y'] >= $minstartliney) AND (($pac['x'] * $this->k) >= ($currentxpos - $this->feps)) AND (($pac['x'] * $this->k) <= ($currentxpos + $this->feps))) {
												if ($cxpos > $lmpos) {
													$this->PageAnnots[$this->page][$pak]['x'] += ($spacew / $this->k);
													$this->PageAnnots[$this->page][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->k);
												} else {
													$this->PageAnnots[$this->page][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->k);
												}
												break;
											}
										}
									}
								} // end of while
								// remove markers
								$pmid = str_replace('x*#!#*x', '', $pmid);
								if ($this->isUnicodeFont()) {
									// multibyte characters
									$spacew = $spacewidthu;
									if ($this->font_stretching != 100) {
										// word spacing is affected by stretching
										$spacew /= ($this->font_stretching / 100);
									}
									// escape special characters
									$pos = 0;
									$pmid = preg_replace('/[\\\][\(]/x', '\\#!#OP#!#', $pmid);
									$pmid = preg_replace('/[\\\][\)]/x', '\\#!#CP#!#', $pmid);
									if (preg_match_all('/\[\(([^\)]*)\)\]/x', $pmid, $pamatch) > 0) {
										foreach($pamatch[0] as $pk => $pmatch) {
											$replace = $pamatch[1][$pk];
											$replace = str_replace('#!#OP#!#', '(', $replace);
											$replace = str_replace('#!#CP#!#', ')', $replace);
											$newpmid = '[('.str_replace(chr(0).chr(32), ') '.sprintf('%F', $spacew).' (', $replace).')]';
											$pos = strpos($pmid, $pmatch, $pos);
											if ($pos !== FALSE) {
												$pmid = substr_replace($pmid, $newpmid, $pos, strlen($pmatch));
											}
											++$pos;
										}
										unset($pamatch);
									}
									if ($this->inxobj) {
										// we are inside an XObject template
										$this->xobjects[$this->xobjid]['outdata'] = $pstart."\n".$pmid."\n".$pend;
									} else {
										$this->setPageBuffer($startlinepage, $pstart."\n".$pmid."\n".$pend);
									}
									$endlinepos = strlen($pstart."\n".$pmid."\n");
								} else {
									// non-unicode (single-byte characters)
									if ($this->font_stretching != 100) {
										// word spacing (Tw) is affected by stretching
										$spacewidth /= ($this->font_stretching / 100);
									}
									$rs = sprintf('%F Tw', $spacewidth);
									$pmid = preg_replace("/\[\(/x", $rs.' [(', $pmid);
									if ($this->inxobj) {
										// we are inside an XObject template
										$this->xobjects[$this->xobjid]['outdata'] = $pstart."\n".$pmid."\nBT 0 Tw ET\n".$pend;
									} else {
										$this->setPageBuffer($startlinepage, $pstart."\n".$pmid."\nBT 0 Tw ET\n".$pend);
									}
									$endlinepos = strlen($pstart."\n".$pmid."\nBT 0 Tw ET\n");
								}
							}
						} // end of J
					} // end if $startlinex
					if (($t_x != 0) OR ($yshift < 0)) {
						// shift the line
						$trx = sprintf('1 0 0 1 %F %F cm', ($t_x * $this->k), ($yshift * $this->k));
						$pstart .= "\nq\n".$trx."\n".$pmid."\nQ\n";
						$endlinepos = strlen($pstart);
						if ($this->inxobj) {
							// we are inside an XObject template
							$this->xobjects[$this->xobjid]['outdata'] = $pstart.$pend;
							foreach ($this->xobjects[$this->xobjid]['annotations'] as $pak => $pac) {
								if ($pak >= $pask) {
									$this->xobjects[$this->xobjid]['annotations'][$pak]['x'] += $t_x;
									$this->xobjects[$this->xobjid]['annotations'][$pak]['y'] -= $yshift;
								}
							}
						} else {
							$this->setPageBuffer($startlinepage, $pstart.$pend);
							// shift the annotations and links
							if (isset($this->PageAnnots[$this->page])) {
								foreach ($this->PageAnnots[$this->page] as $pak => $pac) {
									if ($pak >= $pask) {
										$this->PageAnnots[$this->page][$pak]['x'] += $t_x;
										$this->PageAnnots[$this->page][$pak]['y'] -= $yshift;
									}
								}
							}
						}
						$this->y -= $yshift;
					}
				}
				$pbrk = $this->checkPageBreak($this->lasth);
				$this->newline = false;
				$startlinex = $this->x;
				$startliney = $this->y;
				if ($dom[$dom[$key]['parent']]['value'] == 'sup') {
					$startliney -= ((0.3 * $this->FontSizePt) / $this->k);
				} elseif ($dom[$dom[$key]['parent']]['value'] == 'sub') {
					$startliney -= (($this->FontSizePt / 0.7) / $this->k);
				} else {
					$minstartliney = $startliney;
					$maxbottomliney = ($this->y + $this->getCellHeight($fontsize / $this->k));
				}
				$startlinepage = $this->page;
				if (isset($endlinepos) AND (!$pbrk)) {
					$startlinepos = $endlinepos;
				} else {
					if ($this->inxobj) {
						// we are inside an XObject template
						$startlinepos = strlen($this->xobjects[$this->xobjid]['outdata']);
					} elseif (!$this->InFooter) {
						if (isset($this->footerlen[$this->page])) {
							$this->footerpos[$this->page] = $this->pagelen[$this->page] - $this->footerlen[$this->page];
						} else {
							$this->footerpos[$this->page] = $this->pagelen[$this->page];
						}
						$startlinepos = $this->footerpos[$this->page];
					} else {
						$startlinepos = $this->pagelen[$this->page];
					}
				}
				unset($endlinepos);
				$plalign = $lalign;
				if (isset($this->PageAnnots[$this->page])) {
					$pask = count($this->PageAnnots[$this->page]);
				} else {
					$pask = 0;
				}
				if (!($dom[$key]['tag'] AND !$dom[$key]['opening'] AND ($dom[$key]['value'] == 'table')
					AND (isset($this->emptypagemrk[$this->page]))
					AND ($this->emptypagemrk[$this->page] == $this->pagelen[$this->page]))) {
					$this->setFont($fontname, $fontstyle, $fontsize);
					if ($wfill) {
						$this->setFillColorArray($this->bgcolor);
					}
				}
			} // end newline
			if (isset($opentagpos)) {
				unset($opentagpos);
			}
			if ($dom[$key]['tag']) {
				if ($dom[$key]['opening']) {
					// get text indentation (if any)
					if (isset($dom[$key]['text-indent']) AND $dom[$key]['block']) {
						$this->textindent = $dom[$key]['text-indent'];
						$this->newline = true;
					}
					// table
					if (($dom[$key]['value'] == 'table') AND isset($dom[$key]['cols']) AND ($dom[$key]['cols'] > 0)) {
						// available page width
						if ($this->rtl) {
							$wtmp = $this->x - $this->lMargin;
						} else {
							$wtmp = $this->w - $this->rMargin - $this->x;
						}
						// get cell spacing
						if (isset($dom[$key]['attribute']['cellspacing'])) {
							$clsp = $this->getHTMLUnitToUnits($dom[$key]['attribute']['cellspacing'], 1, 'px');
							$cellspacing = array('H' => $clsp, 'V' => $clsp);
						} elseif (isset($dom[$key]['border-spacing'])) {
							$cellspacing = $dom[$key]['border-spacing'];
						} else {
							$cellspacing = array('H' => 0, 'V' => 0);
						}
						// table width
						if (isset($dom[$key]['width'])) {
							$table_width = $this->getHTMLUnitToUnits($dom[$key]['width'], $wtmp, 'px');
						} else {
							$table_width = $wtmp;
						}
						$table_width -= (2 * $cellspacing['H']);
						if (!$this->inthead) {
							$this->y += $cellspacing['V'];
						}
						if ($this->rtl) {
							$cellspacingx = -$cellspacing['H'];
						} else {
							$cellspacingx = $cellspacing['H'];
						}
						// total table width without cellspaces
						$table_columns_width = ($table_width - ($cellspacing['H'] * ($dom[$key]['cols'] - 1)));
						// minimum column width
						$table_min_column_width = ($table_columns_width / $dom[$key]['cols']);
						// array of custom column widths
						$table_colwidths = array_fill(0, $dom[$key]['cols'], $table_min_column_width);
					}
					// table row
					if ($dom[$key]['value'] == 'tr') {
						// reset column counter
						$colid = 0;
					}

					// table cell
					if (($dom[$key]['value'] == 'td') OR ($dom[$key]['value'] == 'th')) {
						
						/*
						 * CUSTOM START
						 */
						if (isset($dom[$key]['attribute']['style']) && strpos($dom[$key]['attribute']['style'], 'writing-mode: vertical-rl') !== false) {
							$this->writingModeVertical = 'rl';
						} elseif (isset($dom[$key]['attribute']['style']) && strpos($dom[$key]['attribute']['style'], 'writing-mode: vertical-lr') !== false) {
							$this->writingModeVertical = 'lr';
						} else {
							$this->writingModeVertical = '';
						}
						/*
						 * CUSTOM END
						 */
						
						
						$trid = $dom[$key]['parent'];
						$table_el = $dom[$trid]['parent'];
						if (!isset($dom[$table_el]['cols'])) {
							$dom[$table_el]['cols'] = $dom[$trid]['cols'];
						}
						// store border info
						$tdborder = 0;
						if (isset($dom[$key]['border']) AND !empty($dom[$key]['border'])) {
							$tdborder = $dom[$key]['border'];
						}
						$colspan = intval($dom[$key]['attribute']['colspan']);
						if ($colspan <= 0) {
							$colspan = 1;
						}
						$old_cell_padding = $this->cell_padding;
						if (isset($dom[($dom[$trid]['parent'])]['attribute']['cellpadding'])) {
							$crclpd = $this->getHTMLUnitToUnits($dom[($dom[$trid]['parent'])]['attribute']['cellpadding'], 1, 'px');
							$current_cell_padding = array('L' => $crclpd, 'T' => $crclpd, 'R' => $crclpd, 'B' => $crclpd);
						} elseif (isset($dom[($dom[$trid]['parent'])]['padding'])) {
							$current_cell_padding = $dom[($dom[$trid]['parent'])]['padding'];
						} else {
							$current_cell_padding = array('L' => 0, 'T' => 0, 'R' => 0, 'B' => 0);
						}
						$this->cell_padding = $current_cell_padding;
						if (isset($dom[$key]['height'])) {
							// minimum cell height
							$cellh = $this->getHTMLUnitToUnits($dom[$key]['height'], 0, 'px');
						} else {
							$cellh = 0;
						}
						if (isset($dom[$key]['content'])) {
							$cell_content = $dom[$key]['content'];
						} else {
							$cell_content = '&nbsp;';
						}
						$tagtype = $dom[$key]['value'];
						$parentid = $key;
						while (($key < $maxel) AND (!(($dom[$key]['tag']) AND (!$dom[$key]['opening']) AND ($dom[$key]['value'] == $tagtype) AND ($dom[$key]['parent'] == $parentid)))) {
							// move $key index forward
							++$key;
						}
						if (!isset($dom[$trid]['startpage'])) {
							$dom[$trid]['startpage'] = $this->page;
						} else {
							$this->setPage($dom[$trid]['startpage']);
						}
						if (!isset($dom[$trid]['startcolumn'])) {
							$dom[$trid]['startcolumn'] = $this->current_column;
						} elseif ($this->current_column != $dom[$trid]['startcolumn']) {
							$tmpx = $this->x;
							$this->selectColumn($dom[$trid]['startcolumn']);
							$this->x = $tmpx;
						}
						if (!isset($dom[$trid]['starty'])) {
							$dom[$trid]['starty'] = $this->y;
						} else {
							$this->y = $dom[$trid]['starty'];
						}
						if (!isset($dom[$trid]['startx'])) {
							$dom[$trid]['startx'] = $this->x;
							$this->x += $cellspacingx;
						} else {
							$this->x += ($cellspacingx / 2);
						}
						if (isset($dom[$parentid]['attribute']['rowspan'])) {
							$rowspan = intval($dom[$parentid]['attribute']['rowspan']);
						} else {
							$rowspan = 1;
						}
						// skip row-spanned cells started on the previous rows
						if (isset($dom[$table_el]['rowspans'])) {
							$rsk = 0;
							$rskmax = count($dom[$table_el]['rowspans']);
							while ($rsk < $rskmax) {
								$trwsp = $dom[$table_el]['rowspans'][$rsk];
								$rsstartx = $trwsp['startx'];
								$rsendx = $trwsp['endx'];
								// account for margin changes
								if ($trwsp['startpage'] < $this->page) {
									if (($this->rtl) AND ($this->pagedim[$this->page]['orm'] != $this->pagedim[$trwsp['startpage']]['orm'])) {
										$dl = ($this->pagedim[$this->page]['orm'] - $this->pagedim[$trwsp['startpage']]['orm']);
										$rsstartx -= $dl;
										$rsendx -= $dl;
									} elseif ((!$this->rtl) AND ($this->pagedim[$this->page]['olm'] != $this->pagedim[$trwsp['startpage']]['olm'])) {
										$dl = ($this->pagedim[$this->page]['olm'] - $this->pagedim[$trwsp['startpage']]['olm']);
										$rsstartx += $dl;
										$rsendx += $dl;
									}
								}
								if (($trwsp['rowspan'] > 0)
									AND ($rsstartx > ($this->x - $cellspacing['H'] - $current_cell_padding['L'] - $this->feps))
									AND ($rsstartx < ($this->x + $cellspacing['H'] + $current_cell_padding['R'] + $this->feps))
									AND (($trwsp['starty'] < ($this->y - $this->feps)) OR ($trwsp['startpage'] < $this->page) OR ($trwsp['startcolumn'] < $this->current_column))) {
									// set the starting X position of the current cell
									$this->x = $rsendx + $cellspacingx;
									// increment column indicator
									$colid += $trwsp['colspan'];
									if (($trwsp['rowspan'] == 1)
										AND (isset($dom[$trid]['endy']))
										AND (isset($dom[$trid]['endpage']))
										AND (isset($dom[$trid]['endcolumn']))
										AND ($trwsp['endpage'] == $dom[$trid]['endpage'])
										AND ($trwsp['endcolumn'] == $dom[$trid]['endcolumn'])) {
										// set ending Y position for row
										$dom[$table_el]['rowspans'][$rsk]['endy'] = max($dom[$trid]['endy'], $trwsp['endy']);
										$dom[$trid]['endy'] = $dom[$table_el]['rowspans'][$rsk]['endy'];
									}
									$rsk = 0;
								} else {
									++$rsk;
								}
							}
						}
						if (isset($dom[$parentid]['width'])) {
							// user specified width
							$cellw = $this->getHTMLUnitToUnits($dom[$parentid]['width'], $table_columns_width, 'px');
							$tmpcw = ($cellw / $colspan);
							for ($i = 0; $i < $colspan; ++$i) {
								$table_colwidths[($colid + $i)] = $tmpcw;
							}
						} else {
							// inherit column width
							$cellw = 0;
							for ($i = 0; $i < $colspan; ++$i) {
								$cellw += (isset($table_colwidths[($colid + $i)]) ? $table_colwidths[($colid + $i)] : 0);
							}
						}
						$cellw += (($colspan - 1) * $cellspacing['H']);
						// increment column indicator
						$colid += $colspan;
						// add rowspan information to table element
						if ($rowspan > 1) {
							$trsid = array_push($dom[$table_el]['rowspans'], array('trid' => $trid, 'rowspan' => $rowspan, 'mrowspan' => $rowspan, 'colspan' => $colspan, 'startpage' => $this->page, 'startcolumn' => $this->current_column, 'startx' => $this->x, 'starty' => $this->y));
						}
						$cellid = array_push($dom[$trid]['cellpos'], array('startx' => $this->x));
						if ($rowspan > 1) {
							$dom[$trid]['cellpos'][($cellid - 1)]['rowspanid'] = ($trsid - 1);
						}
						// push background colors
						if (isset($dom[$parentid]['bgcolor']) AND ($dom[$parentid]['bgcolor'] !== false)) {
							$dom[$trid]['cellpos'][($cellid - 1)]['bgcolor'] = $dom[$parentid]['bgcolor'];
						}
						// store border info
						if (!empty($tdborder)) {
							$dom[$trid]['cellpos'][($cellid - 1)]['border'] = $tdborder;
						}
						$prevLastH = $this->lasth;
						// store some info for multicolumn mode
						if ($this->rtl) {
							$this->colxshift['x'] = $this->w - $this->x - $this->rMargin;
						} else {
							$this->colxshift['x'] = $this->x - $this->lMargin;
						}
						$this->colxshift['s'] = $cellspacing;
						$this->colxshift['p'] = $current_cell_padding;
						// ****** write the cell content ******

						
						
						
						/*
						 * CUSTOM START
						 */
						if ($this->writingModeVertical) {
							$cellw_orig = $cellw;
							$cellh_orig = $cellh;

							$pureText = $this->extractVisibleText($cell_content);

							$cellh = $this->GetStringWidth($pureText) + $this->cell_padding['T'] + $this->cell_padding['B'] + 1;

							$x = $this->GetX();
							$y = $this->GetY();

							$this->StartTransform();
							$this->Rotate($this->writingModeVertical == 'rl' ? -90 : 90);
							$this->MultiCell($cellh, $cellh, $cell_content, false, 'R', false, 2, $x-$cellh-($this->cell_padding['T']/2), $y+1, true, 0, true, true, 0, 'T', false);
							$this->StopTransform();

							// Manuell X-Position nach rechts verschieben
							#if (!$this->rtl) {
							   $this->SetX($x + $cellw_orig);
							   #$this->SetY($y + $cellh);
							#}
						} else {
							$this->MultiCell($cellw, $cellh, $cell_content, false, $lalign, false, 2, '', '', true, 0, true, true, 0, 'T', false);
						}

						$this->writingModeVertical = '';
						/*
						 * CUSTOM END
						 */
						
						
						
						
						
						
						// restore some values
						$this->colxshift = array('x' => 0, 's' => array('H' => 0, 'V' => 0), 'p' => array('L' => 0, 'T' => 0, 'R' => 0, 'B' => 0));
						$this->lasth = $prevLastH;
						$this->cell_padding = $old_cell_padding;
						$dom[$trid]['cellpos'][($cellid - 1)]['endx'] = $this->x;
						// update the end of row position
						if ($rowspan <= 1) {
							if (isset($dom[$trid]['endy'])) {
								if (($this->page == $dom[$trid]['endpage']) AND ($this->current_column == $dom[$trid]['endcolumn'])) {
									$dom[$trid]['endy'] = max($this->y, $dom[$trid]['endy']);
								} elseif (($this->page > $dom[$trid]['endpage']) OR ($this->current_column > $dom[$trid]['endcolumn'])) {
									$dom[$trid]['endy'] = $this->y;
								}
							} else {
								$dom[$trid]['endy'] = $this->y;
							}
							if (isset($dom[$trid]['endpage'])) {
								$dom[$trid]['endpage'] = max($this->page, $dom[$trid]['endpage']);
							} else {
								$dom[$trid]['endpage'] = $this->page;
							}
							if (isset($dom[$trid]['endcolumn'])) {
								$dom[$trid]['endcolumn'] = max($this->current_column, $dom[$trid]['endcolumn']);
							} else {
								$dom[$trid]['endcolumn'] = $this->current_column;
							}
						} else {
							// account for row-spanned cells
							$dom[$table_el]['rowspans'][($trsid - 1)]['endx'] = $this->x;
							$dom[$table_el]['rowspans'][($trsid - 1)]['endy'] = $this->y;
							$dom[$table_el]['rowspans'][($trsid - 1)]['endpage'] = $this->page;
							$dom[$table_el]['rowspans'][($trsid - 1)]['endcolumn'] = $this->current_column;
						}
						if (isset($dom[$table_el]['rowspans'])) {
							// update endy and endpage on rowspanned cells
							foreach ($dom[$table_el]['rowspans'] as $k => $trwsp) {
								if ($trwsp['rowspan'] > 0) {
									if (isset($dom[$trid]['endpage'])) {
										if (($trwsp['endpage'] == $dom[$trid]['endpage']) AND ($trwsp['endcolumn'] == $dom[$trid]['endcolumn'])) {
											$dom[$table_el]['rowspans'][$k]['endy'] = max($dom[$trid]['endy'], $trwsp['endy']);
										} elseif (($trwsp['endpage'] < $dom[$trid]['endpage']) OR ($trwsp['endcolumn'] < $dom[$trid]['endcolumn'])) {
											$dom[$table_el]['rowspans'][$k]['endy'] = $dom[$trid]['endy'];
											$dom[$table_el]['rowspans'][$k]['endpage'] = $dom[$trid]['endpage'];
											$dom[$table_el]['rowspans'][$k]['endcolumn'] = $dom[$trid]['endcolumn'];
										} else {
											$dom[$trid]['endy'] = $this->pagedim[$dom[$trid]['endpage']]['hk'] - $this->pagedim[$dom[$trid]['endpage']]['bm'];
										}
									}
								}
							}
						}
						$this->x += ($cellspacingx / 2);
					} else {
						// opening tag (or self-closing tag)
						if (!isset($opentagpos)) {
							if ($this->inxobj) {
								// we are inside an XObject template
								$opentagpos = strlen($this->xobjects[$this->xobjid]['outdata']);
							} elseif (!$this->InFooter) {
								if (isset($this->footerlen[$this->page])) {
									$this->footerpos[$this->page] = $this->pagelen[$this->page] - $this->footerlen[$this->page];
								} else {
									$this->footerpos[$this->page] = $this->pagelen[$this->page];
								}
								$opentagpos = $this->footerpos[$this->page];
							}
						}
						$dom = $this->openHTMLTagHandler($dom, $key, $cell);
					}
				} else { // closing tag
					$prev_numpages = $this->numpages;
					$old_bordermrk = $this->bordermrk[$this->page];
					$dom = $this->closeHTMLTagHandler($dom, $key, $cell, $maxbottomliney);
					if ($this->bordermrk[$this->page] > $old_bordermrk) {
						$startlinepos += ($this->bordermrk[$this->page] - $old_bordermrk);
					}
					if ($prev_numpages > $this->numpages) {
						$startlinepage = $this->page;
					}
				}
			} elseif (strlen($dom[$key]['value']) > 0) {
				// print list-item
				if (!TCPDF_STATIC::empty_string($this->lispacer) AND ($this->lispacer != '^')) {
					$this->setFont($pfontname, $pfontstyle, $pfontsize);
					$this->resetLastH();
					$minstartliney = $this->y;
					$maxbottomliney = ($startliney + $this->getCellHeight($this->FontSize));
					if (is_numeric($pfontsize) AND ($pfontsize > 0)) {
						$this->putHtmlListBullet($this->listnum, $this->lispacer, $pfontsize);
					}
					$this->setFont($curfontname, $curfontstyle, $curfontsize);
					$this->resetLastH();
					if (is_numeric($pfontsize) AND ($pfontsize > 0) AND is_numeric($curfontsize) AND ($curfontsize > 0) AND ($pfontsize != $curfontsize)) {
						$pfontascent = $this->getFontAscent($pfontname, $pfontstyle, $pfontsize);
						$pfontdescent = $this->getFontDescent($pfontname, $pfontstyle, $pfontsize);
						$this->y += ($this->getCellHeight(($pfontsize - $curfontsize) / $this->k) + $pfontascent - $curfontascent - $pfontdescent + $curfontdescent) / 2;
						$minstartliney = min($this->y, $minstartliney);
						$maxbottomliney = max(($this->y + $this->getCellHeight($pfontsize / $this->k)), $maxbottomliney);
					}
				}
				// text
				$this->htmlvspace = 0;
				$isRTLString = preg_match(TCPDF_FONT_DATA::$uni_RE_PATTERN_RTL, $dom[$key]['value']) || preg_match(TCPDF_FONT_DATA::$uni_RE_PATTERN_ARABIC, $dom[$key]['value']);
				if ((!$this->premode) AND $this->isRTLTextDir() AND !$isRTLString) {
					// reverse spaces order
					$lsp = ''; // left spaces
					$rsp = ''; // right spaces
					if (preg_match('/^('.$this->re_space['p'].'+)/'.$this->re_space['m'], $dom[$key]['value'], $matches)) {
						$lsp = $matches[1];
					}
					if (preg_match('/('.$this->re_space['p'].'+)$/'.$this->re_space['m'], $dom[$key]['value'], $matches)) {
						$rsp = $matches[1];
					}
					$dom[$key]['value'] = $rsp.$this->stringTrim($dom[$key]['value']).$lsp;
				}
				if ($newline) {
					if (!$this->premode) {
						$prelen = strlen($dom[$key]['value']);
						if ($this->isRTLTextDir() AND !$isRTLString) {
							// right trim except non-breaking space
							$dom[$key]['value'] = $this->stringRightTrim($dom[$key]['value']);
						} else {
							// left trim except non-breaking space
							$dom[$key]['value'] = $this->stringLeftTrim($dom[$key]['value']);
						}
						$postlen = strlen($dom[$key]['value']);
						if (($postlen == 0) AND ($prelen > 0)) {
							$dom[$key]['trimmed_space'] = true;
						}
					}
					$newline = false;
					$firstblock = true;
				} else {
					$firstblock = false;
					// replace empty multiple spaces string with a single space
					$dom[$key]['value'] = preg_replace('/^'.$this->re_space['p'].'+$/'.$this->re_space['m'], chr(32), $dom[$key]['value']);
				}
				$strrest = '';
				if ($this->rtl) {
					$this->x -= $this->textindent;
				} else {
					$this->x += $this->textindent;
				}
				if (!isset($dom[$key]['trimmed_space']) OR !$dom[$key]['trimmed_space']) {
					$strlinelen = $this->GetStringWidth($dom[$key]['value']);
					if (!empty($this->HREF) AND (isset($this->HREF['url']))) {
						// HTML <a> Link
						$hrefcolor = '';
						if (isset($dom[($dom[$key]['parent'])]['fgcolor']) AND ($dom[($dom[$key]['parent'])]['fgcolor'] !== false)) {
							$hrefcolor = $dom[($dom[$key]['parent'])]['fgcolor'];
						}
						$hrefstyle = -1;
						if (isset($dom[($dom[$key]['parent'])]['fontstyle']) AND ($dom[($dom[$key]['parent'])]['fontstyle'] !== false)) {
							$hrefstyle = $dom[($dom[$key]['parent'])]['fontstyle'];
						}
						$strrest = $this->addHtmlLink($this->HREF['url'], $dom[$key]['value'], $wfill, true, $hrefcolor, $hrefstyle, true);
					} else {
						$wadj = 0; // space to leave for block continuity
						if ($this->rtl) {
							$cwa = ($this->x - $this->lMargin);
						} else {
							$cwa = ($this->w - $this->rMargin - $this->x);
						}
						if (($strlinelen < $cwa) AND (isset($dom[($key + 1)])) AND ($dom[($key + 1)]['tag']) AND (!$dom[($key + 1)]['block'])) {
							// check the next text blocks for continuity
							$nkey = ($key + 1);
							$write_block = true;
							$same_textdir = true;
							$tmp_fontname = $this->FontFamily;
							$tmp_fontstyle = $this->FontStyle;
							$tmp_fontsize = $this->FontSizePt;
							while ($write_block AND isset($dom[$nkey])) {
								if ($dom[$nkey]['tag']) {
									if ($dom[$nkey]['block']) {
										// end of block
										$write_block = false;
									}
									$tmp_fontname = isset($dom[$nkey]['fontname']) ? $dom[$nkey]['fontname'] : $this->FontFamily;
									$tmp_fontstyle = isset($dom[$nkey]['fontstyle']) ? $dom[$nkey]['fontstyle'] : $this->FontStyle;
									$tmp_fontsize = isset($dom[$nkey]['fontsize']) ? $dom[$nkey]['fontsize'] : $this->FontSizePt;
									$same_textdir = ($dom[$nkey]['dir'] == $dom[$key]['dir']);
								} else {
									$nextstr = TCPDF_STATIC::pregSplit('/'.$this->re_space['p'].'+/', $this->re_space['m'], $dom[$nkey]['value']);
									if (isset($nextstr[0]) AND $same_textdir) {
										$wadj += $this->GetStringWidth($nextstr[0], $tmp_fontname, $tmp_fontstyle, $tmp_fontsize);
										if (isset($nextstr[1])) {
											$write_block = false;
										}
									}
								}
								++$nkey;
							}
						}
						if (($wadj > 0) AND (($strlinelen + $wadj) >= $cwa)) {
							$wadj = 0;
							$nextstr = TCPDF_STATIC::pregSplit('/'.$this->re_space['p'].'/', $this->re_space['m'], $dom[$key]['value']);
							$numblks = count($nextstr);
							if ($numblks > 1) {
								// try to split on blank spaces
								$wadj = ($cwa - $strlinelen + $this->GetStringWidth($nextstr[($numblks - 1)]));
							} else {
								// set the entire block on new line
								$wadj = $this->GetStringWidth($nextstr[0]);
							}
						}
						// check for reversed text direction
						if (($wadj > 0) AND (($this->rtl AND ($this->tmprtl === 'L')) OR (!$this->rtl AND ($this->tmprtl === 'R')))) {
							// LTR text on RTL direction or RTL text on LTR direction
							$reverse_dir = true;
							$this->rtl = !$this->rtl;
							$revshift = ($strlinelen + $wadj + 0.000001); // add little quantity for rounding problems
							if ($this->rtl) {
								$this->x += $revshift;
							} else {
								$this->x -= $revshift;
							}
							$xws = $this->x;
						}
						// ****** write only until the end of the line and get the rest ******
						$strrest = $this->Write($this->lasth, $dom[$key]['value'], '', $wfill, '', false, 0, true, $firstblock, 0, $wadj);
						// restore default direction
						if ($reverse_dir AND ($wadj == 0)) {
							$this->x = $xws; // @phpstan-ignore-line
							$this->rtl = !$this->rtl;
							$reverse_dir = false;
						}
					}
				}
				$this->textindent = 0;
				if (strlen($strrest) > 0) {
					// store the remaining string on the previous $key position
					$this->newline = true;
					if ($strrest == $dom[$key]['value']) {
						// used to avoid infinite loop
						++$loop;
					} else {
						$loop = 0;
					}
					$dom[$key]['value'] = $strrest;
					if ($cell) {
						if ($this->rtl) {
							$this->x -= $this->cell_padding['R'];
						} else {
							$this->x += $this->cell_padding['L'];
						}
					}
					if ($loop < 3) {
						--$key;
					}
				} else {
					$loop = 0;
					// add the positive font spacing of the last character (if any)
					 if ($this->font_spacing > 0) {
					 	if ($this->rtl) {
							$this->x -= $this->font_spacing;
						} else {
							$this->x += $this->font_spacing;
						}
					}
				}
			}
			++$key;
			if (isset($dom[$key]['tag']) AND $dom[$key]['tag'] AND (!isset($dom[$key]['opening']) OR !$dom[$key]['opening']) AND isset($dom[($dom[$key]['parent'])]['attribute']['nobr']) AND ($dom[($dom[$key]['parent'])]['attribute']['nobr'] == 'true')) {
				// check if we are on a new page or on a new column
				if ((!$undo) AND (($this->y < $this->start_transaction_y) OR (($dom[$key]['value'] == 'tr') AND ($dom[($dom[$key]['parent'])]['endy'] < $this->start_transaction_y)))) {
					// we are on a new page or on a new column and the total object height is less than the available vertical space.
					// restore previous object
					$this->rollbackTransaction(true);
					// restore previous values
					foreach ($this_method_vars as $vkey => $vval) {
						$$vkey = $vval;
					}
					if (!empty($dom[$key]['thead'])) {
						$this->inthead = true;
					}
					// add a page (or trig AcceptPageBreak() for multicolumn mode)
					$pre_y = $this->y;
					if ((!$this->checkPageBreak($this->PageBreakTrigger + 1)) AND ($this->y < $pre_y)) {
						$startliney = $this->y;
					}
					$undo = true; // avoid infinite loop
				} else {
					$undo = false;
				}
			}
		} // end for each $key
		// align the last line
		if (isset($startlinex)) {
			$yshift = ($minstartliney - $startliney);
			if (($yshift > 0) OR ($this->page > $startlinepage)) {
				$yshift = 0;
			}
			$t_x = 0;
			// the last line must be shifted to be aligned as requested
			$linew = abs($this->endlinex - $startlinex);
			if ($this->inxobj) {
				// we are inside an XObject template
				$pstart = substr($this->xobjects[$this->xobjid]['outdata'], 0, $startlinepos);
				if (isset($opentagpos)) {
					$midpos = $opentagpos;
				} else {
					$midpos = 0;
				}
				if ($midpos > 0) {
					$pmid = substr($this->xobjects[$this->xobjid]['outdata'], $startlinepos, ($midpos - $startlinepos));
					$pend = substr($this->xobjects[$this->xobjid]['outdata'], $midpos);
				} else {
					$pmid = substr($this->xobjects[$this->xobjid]['outdata'], $startlinepos);
					$pend = '';
				}
			} else {
				$pstart = substr($this->getPageBuffer($startlinepage), 0, $startlinepos);
				if (isset($opentagpos) AND isset($this->footerlen[$startlinepage]) AND (!$this->InFooter)) {
					$this->footerpos[$startlinepage] = $this->pagelen[$startlinepage] - $this->footerlen[$startlinepage];
					$midpos = min($opentagpos, $this->footerpos[$startlinepage]);
				} elseif (isset($opentagpos)) {
					$midpos = $opentagpos;
				} elseif (isset($this->footerlen[$startlinepage]) AND (!$this->InFooter)) {
					$this->footerpos[$startlinepage] = $this->pagelen[$startlinepage] - $this->footerlen[$startlinepage];
					$midpos = $this->footerpos[$startlinepage];
				} else {
					$midpos = 0;
				}
				if ($midpos > 0) {
					$pmid = substr($this->getPageBuffer($startlinepage), $startlinepos, ($midpos - $startlinepos));
					$pend = substr($this->getPageBuffer($startlinepage), $midpos);
				} else {
					$pmid = substr($this->getPageBuffer($startlinepage), $startlinepos);
					$pend = '';
				}
			}
			if ((((($plalign == 'C') OR (($plalign == 'R') AND (!$this->rtl)) OR (($plalign == 'L') AND ($this->rtl)))))) {
				// calculate shifting amount
				$tw = $w;
				if ($this->lMargin != $prevlMargin) {
					$tw += ($prevlMargin - $this->lMargin);
				}
				if ($this->rMargin != $prevrMargin) {
					$tw += ($prevrMargin - $this->rMargin);
				}
				$one_space_width = $this->GetStringWidth(chr(32));
				$no = 0; // number of spaces on a line contained on a single block
				if ($this->isRTLTextDir()) { // RTL
					// remove left space if exist
					$pos1 = TCPDF_STATIC::revstrpos($pmid, '[(');
					if ($pos1 > 0) {
						$pos1 = intval($pos1);
						if ($this->isUnicodeFont()) {
							$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, '[('.chr(0).chr(32)));
							$spacelen = 2;
						} else {
							$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, '[('.chr(32)));
							$spacelen = 1;
						}
						if ($pos1 == $pos2) {
							$pmid = substr($pmid, 0, ($pos1 + 2)).substr($pmid, ($pos1 + 2 + $spacelen));
							if (substr($pmid, $pos1, 4) == '[()]') {
								$linew -= $one_space_width;
							} elseif ($pos1 == strpos($pmid, '[(')) {
								$no = 1;
							}
						}
					}
				} else { // LTR
					// remove right space if exist
					$pos1 = TCPDF_STATIC::revstrpos($pmid, ')]');
					if ($pos1 > 0) {
						$pos1 = intval($pos1);
						if ($this->isUnicodeFont()) {
							$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, chr(0).chr(32).')]')) + 2;
							$spacelen = 2;
						} else {
							$pos2 = intval(TCPDF_STATIC::revstrpos($pmid, chr(32).')]')) + 1;
							$spacelen = 1;
						}
						if ($pos1 == $pos2) {
							$pmid = substr($pmid, 0, ($pos1 - $spacelen)).substr($pmid, $pos1);
							$linew -= $one_space_width;
						}
					}
				}
				$mdiff = ($tw - $linew);
				if ($plalign == 'C') {
					if ($this->rtl) {
						$t_x = -($mdiff / 2);
					} else {
						$t_x = ($mdiff / 2);
					}
				} elseif ($plalign == 'R') {
					// right alignment on LTR document
					$t_x = $mdiff;
				} elseif ($plalign == 'L') {
					// left alignment on RTL document
					$t_x = -$mdiff;
				}
			} // end if startlinex
			if (($t_x != 0) OR ($yshift < 0)) {
				// shift the line
				$trx = sprintf('1 0 0 1 %F %F cm', ($t_x * $this->k), ($yshift * $this->k));
				$pstart .= "\nq\n".$trx."\n".$pmid."\nQ\n";
				$endlinepos = strlen($pstart);
				if ($this->inxobj) {
					// we are inside an XObject template
					$this->xobjects[$this->xobjid]['outdata'] = $pstart.$pend;
					foreach ($this->xobjects[$this->xobjid]['annotations'] as $pak => $pac) {
						if ($pak >= $pask) {
							$this->xobjects[$this->xobjid]['annotations'][$pak]['x'] += $t_x;
							$this->xobjects[$this->xobjid]['annotations'][$pak]['y'] -= $yshift;
						}
					}
				} else {
					$this->setPageBuffer($startlinepage, $pstart.$pend);
					// shift the annotations and links
					if (isset($this->PageAnnots[$this->page])) {
						foreach ($this->PageAnnots[$this->page] as $pak => $pac) {
							if ($pak >= $pask) {
								$this->PageAnnots[$this->page][$pak]['x'] += $t_x;
								$this->PageAnnots[$this->page][$pak]['y'] -= $yshift;
							}
						}
					}
				}
				$this->y -= $yshift;
				$yshift = 0;
			}
		}
		// restore previous values
		$this->setGraphicVars($gvars);
		if ($this->num_columns > 1) {
			$this->selectColumn();
		} elseif ($this->page > $prevPage) {
			$this->lMargin = $this->pagedim[$this->page]['olm'];
			$this->rMargin = $this->pagedim[$this->page]['orm'];
		}
		// restore previous list state
		$this->cell_height_ratio = $prev_cell_height_ratio;
		$this->listnum = $prev_listnum;
		$this->listordered = $prev_listordered;
		$this->listcount = $prev_listcount;
		$this->lispacer = $prev_lispacer;
		if ($ln AND (!($cell AND ($dom[$key-1]['value'] == 'table')))) {
			$this->Ln($this->lasth);
			if (($this->y < $maxbottomliney) AND ($startlinepage == $this->page)) {
				$this->y = $maxbottomliney;
			}
		}
		unset($dom);
	}

}