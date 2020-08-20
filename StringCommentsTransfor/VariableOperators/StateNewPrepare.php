<?php
namespace StringCommentsTransfor\VariableOperators;

use StringCommentsTransfor\FstForVariableTransfor;
use Exception;

class StateNewPrepare implements OperatorInterface
{
    /** @var FstForVariableTransfor */
    private $state;

    /**
     * @param FstForVariableTransfor $state
     */
    public function __construct(FstForVariableTransfor $state)
    {
        $this->state = $state;
    }

    /**
     * @param string|null $token
     * @return void
     */
    public function doOperation($token)
    {
        if (FstForVariableTransfor::STATE_NEW_PREPARE != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if (is_null($token)) {
            throw new Exception('new一个类的时候，意外结束');
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], ['new', 'this'])
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && isset($this->state->availableClasssMap[$token['token']])
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } elseif (
            FstForVariableTransfor::TYPE_STRING == $token['type']
        ) {
            throw new Exception('尝试new一个字符串是错误的');
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], ['{', '}', '(', ';'])
        ) {
            throw new Exception('尝试new一个"'.$token['token'].'"是错误的');
        } elseif (
            FstForVariableTransfor::TYPE_COMMENTS == $token['type']
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_COMMENTS,
                'token' => $token['token'],
            ];
            $this->state->state = FstForVariableTransfor::STATE_NEW;
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], [' ', "\t", PHP_EOL])
        ) {
            $this->state->state = FstForVariableTransfor::STATE_NEW;
        } elseif (FstForVariableTransfor::TYPE_CODE == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } else {
            throw new Exception('匹配不到对应的输入');
        }
    }
}



