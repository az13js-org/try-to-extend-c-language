<?php
namespace StringCommentsTransfor\VariableOperators;

use StringCommentsTransfor\FstForVariableTransfor;

interface OperatorInterface
{
    /**
     * @param FstForVariableTransfor $state
     */
    public function __construct(FstForVariableTransfor $state);

    /**
     * @param array|null $token
     * @return void
     */
    public function doOperation($token);
}



