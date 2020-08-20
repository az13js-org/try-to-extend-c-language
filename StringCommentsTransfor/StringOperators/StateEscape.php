<?php
namespace StringCommentsTransfor\StringOperators;

use StringCommentsTransfor\FstForStringTransfor;
use Exception;

class StateEscape implements OperatorInterface
{
    /** @var FstForStringTransfor */
    private $state;

    /**
     * @param FstForStringTransfor $state
     */
    public function __construct(FstForStringTransfor $state)
    {
        $this->state = $state;
    }

    /**
     * @param string|null $token
     * @return void
     */
    public function doOperation($token)
    {
        if (FstForStringTransfor::STATE_ESCAPE != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if (is_null($token) || PHP_EOL == $token) {
            $this->state->state = FstForStringTransfor::STATE_EXCEPTION;
            throw new Exception('语言解析失败，突然在字符串转义时结束或换行');
        } else {
            $this->state->buffer .= $token;
            $this->state->state = FstForStringTransfor::STATE_IN_STRING;
        }
    }
}



