<?php
namespace StringCommentsTransfor\InnerObjectAttributesAccessOperators;

use StringCommentsTransfor\FstForInnerObjectAttributesAccess;
use Exception;

class OperatorFactory
{
    /** @var array */
    private $stateOperatorCache = [];

    /**
     * @param FstForInnerObjectAttributesAccess $state
     * @throws Exception
     * @return OperatorInterface
     */
    public function getOperator(FstForInnerObjectAttributesAccess $state)
    {
        $stateMap2Operator = [
            FstForInnerObjectAttributesAccess::STATE_DEFAULT => 'StateDefault',
        ];
        if (!isset($stateMap2Operator[$state->state])) {
            throw new Exception('未定义的状态：' . $state->state);
        }
        if (!isset($this->stateOperatorCache[$state->state])) {
            $operatorClass = "StringCommentsTransfor\\InnerObjectAttributesAccessOperators\\{$stateMap2Operator[$state->state]}";
            return $this->stateOperatorCache[$state->state] = new $operatorClass($state);
        }
        return $this->stateOperatorCache[$state->state];
    }
}



