<?php
namespace StringCommentsTransfor\StringOperators;

use StringCommentsTransfor\FstForStringTransfor;
use Exception;

class StateDefault implements OperatorInterface
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
        if (FstForStringTransfor::STATE_DEFAULT != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if ('*/' == $token) {
            $this->state->state = FstForStringTransfor::STATE_EXCEPTION;
            throw new Exception('语言解析失败，突然多了个"*/"');
        } elseif ('"' == $token) {
            $this->state->markTokens[] = [
                'type' => FstForStringTransfor::TYPE_CODE,
                'token' => $this->state->buffer,
            ];
            $this->state->buffer = $token;
            $this->state->state = FstForStringTransfor::STATE_IN_STRING;
        } elseif ('//' == $token) {
            $this->state->markTokens[] = [
                'type' => FstForStringTransfor::TYPE_CODE,
                'token' => $this->state->buffer,
            ];
            $this->state->buffer = $token;
            $this->state->state = FstForStringTransfor::STATE_LINE_COMMENTS;
        } elseif ('/*' == $token) {
            $this->state->markTokens[] = [
                'type' => FstForStringTransfor::TYPE_CODE,
                'token' => $this->state->buffer,
            ];
            $this->state->buffer = $token;
            $this->state->state = FstForStringTransfor::STATE_LINES_COMMENTS;
        } elseif (is_null($token)) {
            $this->state->markTokens[] = [
                'type' => FstForStringTransfor::TYPE_CODE,
                'token' => $this->state->buffer,
            ];
            $this->state->buffer = '';
            $this->state->state = FstForStringTransfor::STATE_END;
        } else {
            $this->state->buffer .= $token;
        }
    }
}



