<?php

namespace Core\Service\HtmlPurifier;

class InjectorRelNoreferrer extends \HTMLPurifier_Injector
{
    public $name = 'RelNoreferrer';
    public $needed = ['a'];

    public function handleElement(&$token)
    {
        if ($token->name === 'a' && $token instanceof HTMLPurifier_Token_Start) {
            if (!isset($token->attr['rel'])) {
                $token->attr['rel'] = 'noreferrer';
            } elseif (strpos($token->attr['rel'], 'noreferrer') === false) {
                $token->attr['rel'] .= ' noreferrer';
            }
        }
    }
}
