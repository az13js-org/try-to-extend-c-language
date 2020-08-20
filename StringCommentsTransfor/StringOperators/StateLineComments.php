<?php
namespace StringCommentsTransfor\StringOperators;

use StringCommentsTransfor\FstForStringTransfor;
use Exception;

class StateLineComments implements OperatorInterface
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
        if (FstForStringTransfor::STATE_LINE_COMMENTS != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if (PHP_EOL == $token) {
            $this->state->markTokens[] = [
                'type' => FstForStringTransfor::TYPE_COMMENTS,
                'token' => $this->state->buffer . PHP_EOL,
            ];
            $this->state->buffer = '';
            $this->state->state = FstForStringTransfor::STATE_DEFAULT;
        } elseif (is_null($token)) {
            $this->state->markTokens[] = [
                'type' => FstForStringTransfor::TYPE_COMMENTS,
                'token' => $this->state->buffer . PHP_EOL,
            ];
            $this->state->buffer = '';
            $this->state->state = FstForStringTransfor::STATE_END;
        } else {
            $this->state->buffer .= $token;
        }
    }
}



