<?php
namespace StringCommentsTransfor\VariableOperators;

use StringCommentsTransfor\FstForVariableTransfor;
use Exception;

class StateNewAllow implements OperatorInterface
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
        if (FstForVariableTransfor::STATE_NEW_ALLOW != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if (is_null($token)) {
            $this->state->state = FstForVariableTransfor::STATE_END;
        } elseif (
            FstForVariableTransfor::TYPE_COMMENTS == $token['type']
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_COMMENTS,
                'token' => $token['token'],
            ];
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], ['{', '}', '(', ';', ' ', "\t", PHP_EOL])
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], ['this'])
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_OOP_THIS,
                'token' => 'this',
                'thisClassFunction' => uniqSourceLowerCase($this->state->fileName),
                'comment' => 'struct of ' . $this->state->fileName,
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } elseif (
            isset($this->state->availableClasssMap[$token['token']])
            && FstForVariableTransfor::TYPE_CODE == $token['type']
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_COVERD_CLASS,
                'token' => $this->state->availableClasssMap[$token['token']],
                'sourceDefine' => $token['token'],
                'coverType' => 'defineType',
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } elseif (
            FstForVariableTransfor::TYPE_CODE == $token['type']
            && in_array($token['token'], ['new'])
        ) {
            $this->state->state = FstForVariableTransfor::STATE_NEW_PREPARE;
        } elseif (FstForVariableTransfor::TYPE_STRING == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_STRING,
                'token' => $token['token'],
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } elseif (FstForVariableTransfor::TYPE_CODE == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
            $this->state->state = FstForVariableTransfor::STATE_DEFAULT;
        } else {
            throw new Exception('匹配不到对应的输入，token=' . print_r($token, true));
        }
    }
}



