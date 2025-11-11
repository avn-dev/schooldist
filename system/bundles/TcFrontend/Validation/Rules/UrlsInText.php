<?php

namespace TcFrontend\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class UrlsInText implements Rule
{
    public function __construct(private readonly int $numberOfUrlsAllowed = 0) {}

    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        $found = 0;
        // Find links in value
        preg_match_all('@http://|https://|ftp://@', $value, $result);
        if (isset($result[0])) {
            $found = count($result[0]);
        }

        if($found > $this->numberOfUrlsAllowed) {
            return false;
        }

        return true;
    }

    public function message()
    {
        if ($this->numberOfUrlsAllowed > 0) {
            return 'Number of allowed urls exceeded';
        }
        return 'This field can not contain urls';
    }
}