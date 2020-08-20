<?php
namespace StringCommentsTransfor\StringOperators;

use StringCommentsTransfor\FstForStringTransfor;

interface OperatorInterface
{
    /**
     * @param FstForStringTransfor $state
     */
    public function __construct(FstForStringTransfor $state);

    /**
     * @param string|null $token
     * @return void
     */
    public function doOperation($token);
}



