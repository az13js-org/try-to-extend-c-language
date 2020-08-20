<?php
namespace StringCommentsTransfor\StringOperators;

use StringCommentsTransfor\FstForStringTransfor;
use Exception;

class StateException implements OperatorInterface
{
    /**
     * @param FstForStringTransfor $state
     */
    public function __construct(FstForStringTransfor $state)
    {
    }

    /**
     * @param string|null $token
     * @return void
     */
    public function doOperation($token)
    {
        throw new Exception('已经是异常状态，不应该再出现别的操作');
    }
}



