<?php
namespace StringCommentsTransfor\VariableOperators;

use StringCommentsTransfor\FstForVariableTransfor;
use Exception;

class StateDefault implements OperatorInterface
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
        if (FstForVariableTransfor::STATE_DEFAULT != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
        if (is_null($token)) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $this->state->buffer,
            ];
            $this->state->buffer = '';
            $this->state->state = FstForVariableTransfor::STATE_END;
        } elseif ('this' == $token['token'] && FstForVariableTransfor::TYPE_CODE == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_OOP_THIS,
                'token' => 'this',
                'thisClassFunction' => uniqSourceLowerCase($this->state->fileName),
                'comment' => 'struct of ' . $this->state->fileName,
            ];
        } elseif (FstForVariableTransfor::TYPE_STRING == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_STRING,
                'token' => $token['token'],
            ];
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
        } elseif (
            PHP_EOL == $token['token']
            && FstForVariableTransfor::TYPE_CODE == $token['type']
        ) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
        } elseif (
            'new' == $token['token']
            && FstForVariableTransfor::TYPE_CODE == $token['type']
        ) {
            $this->state->state = FstForVariableTransfor::STATE_NEW_PREPARE;
        } elseif (
            (
                in_array($token['token'], ['{','}','(',' ',"\t",';'])
                && FstForVariableTransfor::TYPE_CODE == $token['type']
            )
            || FstForVariableTransfor::TYPE_COMMENTS == $token['type']
        ) {
            if (FstForVariableTransfor::TYPE_CODE == $token['type']) {
                $this->state->markTokens[] = [
                    'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                    'token' => $token['token'],
                ];
            } else {
                $this->state->markTokens[] = [
                    'type' => FstForVariableTransfor::TYPE_COMMENTS,
                    'token' => $token['token'],
                ];
            }
            $this->state->state = FstForVariableTransfor::STATE_NEW_ALLOW;
        } elseif (FstForVariableTransfor::TYPE_CODE == $token['type']) {
            $this->state->markTokens[] = [
                'type' => FstForVariableTransfor::TYPE_CODE_OTHERS,
                'token' => $token['token'],
            ];
        } else {
            throw new Exception('匹配不到对应的输入');
        }
    }
}



