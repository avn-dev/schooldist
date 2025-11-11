<?php

namespace TcFrontend\Traits;

/**
 * TODO evlt. nach Core verschieben
 */
trait WithInputCleanUp
{
    /**
     * XSS verhindern (Array)
     *
     * @param array $data
     * @return array
     */
    protected function cleanUpRecursive(array $data): array
    {
        array_walk_recursive($data, function (&$input) {
            if (is_string($input)) {
                $input = $this->cleanUp($input);
            }
        });

        return $data;
    }

    /**
     * HTML-Tags entfernen (z.B. XSS)
     *
     * @param string $input
     * @return string
     */
    protected function cleanUp(string $input): string
    {
		// filter_var() arbeitet leider nicht sauber und schneidet den Text ab wenn ein einfaches "<" vorkommt
		// z.b. "this is < not a tag" wird zu "this is "
		// $input = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        // Klammern von HTML-Tags durch ähnliche ersetzen (wird so nicht mehr als HTML erkannt). strip_tags() alleine
        // schneidet den Text ab, wenn z.b. "<3" verwendet wird.
        $input = str_replace(['<', '>'], ['﹤', '﹥'], $input);
        $input = trim(strip_tags($input));
        return $input;
    }
}
