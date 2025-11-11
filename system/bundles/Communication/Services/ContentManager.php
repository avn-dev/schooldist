<?php

namespace Communication\Services;

use Illuminate\Support\Arr;
use Pelago\Emogrifier\CssInliner;
use Illuminate\Support\Str;

class ContentManager
{
	public function extractHtmlSkeleton(string $content): string
	{
		[$content, $parts] = $this->searchAndSecureContentParts($content);

		$content = $this->inlineCss($content);

		if (empty($dom = $this->dom($content))) {
			return $content;
		}

		foreach (['script', 'style'] as $remove) {
			$elements = $dom->getElementsByTagName($remove);
			foreach ($elements as $element) {
				$element->parentNode->removeChild($element);
			}
		}

		$content = $dom->saveHTML();

		$content = preg_replace('/(<body[^>]*>)(.*?)(<\/body>)/is', '$1$3', $content);

		$content = $this->correctConditionalComments($content);
		$content = $this->restoreContentParts($content, $parts);

		return $content;
	}

	public function extractBody(string $content, bool $onlyText = false, array $removeTags = []): string
	{
		if (empty($content)) {
			return $content;
		}

		[$content, $parts] = $this->searchAndSecureContentParts($content);

		$content = $this->inlineCss($content);

		$dom = $this->dom($content);

		$body = $dom->getElementsByTagName('body')->item(0);

		if (!$body) {
			$body = $dom;
		}

		foreach (['head', 'script', 'style', ...$removeTags] as $remove) {
			$elements = $body->getElementsByTagName($remove);
			foreach ($elements as $element) {
				$element->parentNode->removeChild($element);
			}
		}

		if ($onlyText) {
			$cleanText = $body->textContent;
			$cleanText = preg_replace('/\s+/', ' ', $cleanText);
			$cleanText = str_replace("\u{A0}", ' ', $cleanText);
			$content = trim($cleanText);
		} else {
			$content = $dom->saveHTML($body);
			$content = $this->correctConditionalComments($content);
		}

		$content = $this->restoreContentParts($content, $parts);

		return $content;
	}

	public function combineAsHtml(string $skeleton, ...$contents): string
	{
		$final = '';
		foreach ($contents as $index => $content) {

			[$content, $separator] = Arr::wrap($content);

			if ($index > 0) {
				if ($separator) {
					$final.= '<div style="color: #666666; font-size: 12px;">'.$separator.'</div>';
				}
				$final .= '<hr style="border: 1px solid #CDCDCD;">';
			}

			$body = $this->extractBody($content);

			if (!empty($body)) {
				$bodyAsDiv = preg_replace('/<body([^>]*)>/i', '<div$1>', $body);
				$bodyAsDiv = preg_replace('/<\/body>/i', '</div>', $bodyAsDiv);

				$namespace = 'm_'.Str::random(8);
				$final .= '<div class="'.$namespace.' fidelo-message">' .$bodyAsDiv. '</div>';
			}
		}


		// Alle "$" gefolgt von einer Zahl durch einen Platzhalter ersetzen damit das nächst preg_replace() diese nicht als Backreferences interpretiert
		$final = preg_replace('/\$(\d+)/', '[||DOLLAR||]$1', $final);
		// Content in <body> einfügen
		$final = preg_replace('/(<body[^>]*>)(.*?)(<\/body>)/is', '$1' . $final . '$3', $skeleton);
		// Wieder zurückwandeln
		$final = str_replace('[||DOLLAR||]', '$', $final);

		$final = $this->correctConditionalComments($final);

		return $final;
	}

	public function combineAsText(...$contents): string
	{
		$final = '';
		foreach ($contents as $index => $content) {

			[$content, $separator] = Arr::wrap($content);

			if ($index > 0 && !empty($separator)) {
				$final .= PHP_EOL.$separator.PHP_EOL;
			}

			$body = $this->extractBody($content);

			$body = str_replace(['<br>', '<br/>', '<br />'], "\r\n", $body);

			$final .= strip_tags($body);
		}

		return $final;
	}

	public function combine(string $targetFormat, ...$contents): string
	{
		$final = '';
		foreach ($contents as $index => $content) {

			[$content, $separator] = Arr::wrap($content);

			if ($targetFormat === 'html') {

				if ($index > 0) {
					if ($separator) {
						$final.= '<div style="color: #666666; font-size: 12px;">'.$separator.'</div>';
					}
					$final .= '<hr style="border: 1px solid #CDCDCD;">';
				}

				if (!empty($content)) {
					$body = $this->extractBody($content);

					if (!empty($body)) {
						$bodyAsDiv = preg_replace('/<body([^>]*)>/i', '<div$1>', $body);
						$bodyAsDiv = preg_replace('/<\/body>/i', '</div>', $bodyAsDiv);

						$namespace = 'm_'.Str::random(8);
						$final .= '<div class="'.$namespace.' fidelo-message">' .$bodyAsDiv. '</div>';
					}
				}

			} else {

				$body = $this->extractBody($content);


				if ($separator) {
					$final.= "\r\n".$separator;
				}

				$body = str_replace(['<br>', '<br/>', '<br />'], "\r\n", $body);

				$final .= "\r\n".strip_tags($body);
			}
		}

		return $final;
	}

	public function correctConditionalComments(string $content): string
	{
		// \DOMDocument bzw. TinyMCE fügen bei z.b. <!--[if (mso)|(IE)]> einfach ein Leerzeichen vor das [if..] ein, das ist bei E-Mails
		// einfach falsch, weil es dann als Kommentar und nicht mehr als Bedingung behandelt wird
		$content = preg_replace('/<!--\s+\[if/', '<!--[if', $content);
		$content = preg_replace('/<!\[endif\]\s+-->/', '<![endif]-->', $content);
		return $content;
	}

	private function dom(string $content): ?\DOMDocument
	{
		if (empty($content) || $content === strip_tags($content)) {
			return null;
		}

		libxml_use_internal_errors(true); // Verhindert Warnungen bei fehlerhaftem HTML
		$dom = new \DOMDocument('1.0', 'UTF-8');
		#$dom->loadHTML('<?xml encoding="UTF-8">'.$content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		return $dom;
	}

	private function searchAndSecureContentParts(string $content): array
	{
		$matches = [];
		preg_match_all('/(?:href|src)=[\'"](.*?)[\'"]/i', $content, $matches);

		$parts = [];

		// Urls sichern - DOMDocument führt leider URL encoding aus und wenn jetzt in einer URL ein Platzhalter ist vorhanden
		// ist würde dieser nicht mehr ersetzt werden.
		foreach ($matches[1] ?? [] as $index => $url) {
			$content = str_replace($url, '#link'.$index.'#', $content);
			$parts['#link'.$index.'#'] = $url;
		}

		return [$content, $parts];
	}

	private function restoreContentParts(string $content, array $parts): string
	{
		foreach ($parts as $placeholder => $part) {
			$content = str_replace($placeholder, $part, $content);
		}

		return $content;
	}

	private function inlineCss(string $content): string
	{
		return CssInliner::fromHtml($content)->inlineCss()->render();
	}

}