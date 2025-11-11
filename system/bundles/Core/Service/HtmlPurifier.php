<?php

namespace Core\Service;

use Illuminate\Support\Arr;

class HtmlPurifier extends \HTMLPurifier {

	// Was TCPDF alles unterstützt
	const SET_TCPDF = 'tcpdf';

	// TinyMCE simple
	const SET_FRONTEND = 'frontend';

	const SETS = [
		self::SET_TCPDF => [
			'trusted' => true,
			'html' => [
				'em',
				'p[style|pagebreak]',
				'b',
				'strong',
				'br[pagebreak]',
				'li',
				'ol',
				'ul',
				'hr',
				'h1[style]',
				'h2[style]',
				'h3[style]',
				'h4[style]',
				'h5[style]',
				'h6[style]',
				'pre',
				'address',
				'u',
				'i',
				'img[src|alt|width|height|style]',
				'table[border|cellspacing|cellpadding|style|width]',
				'tr[nobr]',
				'th[width|style|colspan]',
				'td[width|style|colspan]',
				'a[href]',
				'div[style|pagebreak]',
				'span[style]',
				'sup',
				'sub',
				'input[type|name|value|size|checked]',
				'textarea[name|cols|rows]',
				'select[name]',
				'option[value]',
				'button',
			],
			'css' => [
				'text-align',
				'color',
				'border',
				'border-top',
				'border-bottom',
				'width',
				'height',
				'font-family',
				'font-size',
				'font-weight',
				'text-decoration',
				'line-height',
				'background-color'
			]
		],
		self::SET_FRONTEND => [
			'trusted' => false,
			'html' => [
				'a[href|target]', // Nicht im Editor
				'blockquote',
				'br',
				'code',
				'em',
				'h1',
				'h2',
				'h3',
				'h4',
				'h5',
				'h6',
				'li', // Nicht im Editor
				'ol', // Nicht im Editor
				'p',
				'pre',
				'span[style]',
				'strong',
				'sup',
				'sub',
				'ul' // Nicht im Editor
			],
			'css' => [
				'color',
				'text-decoration'
			]
		]
	];

	/**
	 * @var self
	 */
	private static $oCache;

	/**
	 * @param string|array $mSet
	 */
	public function __construct($mSet = null) {

		// Abwärtskompatibilität
		if(is_array($mSet)) {
			$aSet = $mSet;
			if (!Arr::isAssoc($mSet)) {
				$aSet = [
					'trusted' => false,
					'html' => $mSet,
					'css' => []
				];
			}
		} elseif($mSet instanceof \HTMLPurifier_Config) {
			parent::__construct($mSet);
			return;
		} else {
		
			if ($mSet === null) {
				$mSet = self::SET_TCPDF;
			}

			if (!isset(self::SETS[$mSet])) {
				throw new \InvalidArgumentException('Unknown set: '.$mSet);
			}
			
			$aSet = self::SETS[$mSet];
			
		}

		$sCacheDir = \Util::getDocumentRoot().'storage/cache/htmlpurifier';
		\Util::checkDir($sCacheDir);

		$oConfig = \HTMLPurifier_Config::createDefault();

		$oConfig->set('Core.Encoding', 'UTF-8');
		$oConfig->set('HTML.Doctype', 'HTML 4.01 Transitional');
		$oConfig->set('HTML.Allowed', join(',', $aSet['html']));
		$oConfig->set('HTML.Trusted', $aSet['trusted'] ?? false);
		$oConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'ftp' => true, 'nntp' => true, 'news' => true, 'tel' => true, 'data' => true]);

		$oConfig->set('CSS.AllowedProperties', join(',', $aSet['css'] ?? []));
		$oConfig->set('CSS.AllowedFonts', null); // Alle erlauben
		$oConfig->set('Attr.AllowedFrameTargets', ['_blank']);

		$oConfig->set('Cache.SerializerPath', $sCacheDir);

		$oDefinition = $oConfig->getHTMLDefinition(true);
		$oDefinition->addAttribute('tr', 'nobr', 'Text');
		$oDefinition->addAttribute('p', 'pagebreak', 'Text');
		$oDefinition->addAttribute('br', 'pagebreak', 'Text');
		$oDefinition->addAttribute('div', 'pagebreak', 'Text');
		$oDefinition->addAttribute('img', 'src', new HtmlPurifierValidation());

		// call parent constructor
		parent::__construct($oConfig);

	}

	// chr 160 leerzeichen verhindern ( erzeugt falsche zeichen im PDF )
	// &nbsp; wird im Purifier durch chr 160 ersetzt und nicht durch 32
	// was im PDF in unserer Arial Schrftart nicht gemapt ist.
	public function purify($mValue, $config = NULL) {
		
		$mValue = str_replace('<p>&nbsp;', '<p>', $mValue);
		$mValue = str_replace('&nbsp;</p>', '</p>', $mValue);
		$mValue = str_replace('&nbsp;', ' ', $mValue);

		$mValue = parent::purify($mValue, $config);
		
		// Muss explizit umgewandelt werden in XHTML für PDF Klasse, Doctype im Purifyer umstellen geht nicht da elemente aus 4.01 nicht mehr unterstützt werden 
		$mValue = str_replace("<br>", "<br />", $mValue);

		return $mValue;
	}

	/**
	 * @deprecated
	 * @param $mValue
	 * @return string|string[]
	 */
	public static function p($mValue) {

		if(!self::$oCache instanceof self) {
			self::$oCache = new self();
		}
		$mValue = self::$oCache->purify($mValue);

		return $mValue;

	}

}