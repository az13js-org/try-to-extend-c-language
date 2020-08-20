<?php
namespace StringCommentsTransfor\VariableOperators;

use StringCommentsTransfor\FstForVariableTransfor;
use Exception;

class StateNew implements OperatorInterface
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
        if (FstForVariableTransfor::STATE_NEW != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if (is_null($token)) {
            throw new Exception('new 一个类的时候，意外结束');
        } elseif (
            FstForVariableTransfor::TYPE_STRING == $token['type']
        ) {
            throw new Exception('new 一个字符串是错误的，这个错误的字符串是' . $token['token']);
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], ['{', '}', '(', ';', 'new', 'this'])
        ) {
            throw new Exception('你在new什么？ new ' . $token['token']);
        } elseif (
            FstForVariableTransfor::TYPE_COMMENTS == $token['type']
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_COMMENTS,
                'token' => $token['token'],
            ];
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], [' ', PHP_EOL, "\t"])
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
        } elseif (
            isset($this->state->userDefineFunctionMapping[$token['token']])
            && FstForVariableTransfor::TYPE_CODE == $token['type']
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_COVERD_CLASS,
                'token' => $this->state->userDefineFunctionMapping[$token['token']] . "new",
                'sourceDefine' => $token['token'],
                'coverType' => 'newObject',
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } elseif (FstForVariableTransfor::TYPE_CODE == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
            throw new Exception('你在new什么？ new ' . $token['token']);
        } else {
            throw new Exception('匹配不到对应的输入');
        }
    }
}



