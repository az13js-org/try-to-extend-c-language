<?php
namespace StringCommentsTransfor\VariableOperators;

use StringCommentsTransfor\FstForVariableTransfor;
use Exception;

class OperatorFactory
{
    /** @var array */
    private $stateOperatorCache = [];

    /**
     * @param FstForVariableTransfor $state
     * @throws Exception
     * @return OperatorInterface
     */
    public function getOperator(FstForVariableTransfor $state)
    {
        $stateMap2Operator = [
            FstForVariableTransfor::STATE_DEFAULT => 'StateDefault',
            FstForVariableTransfor::STATE_NEW_PREPARE => 'StateNewPrepare',
            FstForVariableTransfor::STATE_NEW => 'StateNew',
            FstForVariableTransfor::STATE_EXCEPTION => 'StateException',
            FstForVariableTransfor::STATE_NEW_ALLOW => 'StateNewAllow',
            FstForVariableTransfor::STATE_END => 'StateEnd',
        ];
        if (!isset($stateMap2Operator[$state->state])) {
            throw new Exception('未定义的状态：' . $state->state);
        }
        if (!isset($this->stateOperatorCache[$state->state])) {
            $operatorClass = "StringCommentsTransfor\\VariableOperators\\{$stateMap2Operator[$state->state]}";
            return $this->stateOperatorCache[$state->state] = new $operatorClass($state);
        }
        return $this->stateOperatorCache[$state->state];
    }
}



