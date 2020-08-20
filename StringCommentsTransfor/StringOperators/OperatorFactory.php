<?php
namespace StringCommentsTransfor\StringOperators;

use StringCommentsTransfor\FstForStringTransfor;
use Exception;

class OperatorFactory
{
    /** @var array */
    private $stateOperatorCache = [];

    /**
     * @param FstForStringTransfor $state
     * @throws Exception
     * @return OperatorInterface
     */
    public function getOperator(FstForStringTransfor $state)
    {
        $stateMap2Operator = [
            FstForStringTransfor::STATE_DEFAULT => 'StateDefault',
            FstForStringTransfor::STATE_IN_STRING => 'StateInString',
            FstForStringTransfor::STATE_ESCAPE => 'StateEscape',
            FstForStringTransfor::STATE_EXCEPTION => 'StateException',
            FstForStringTransfor::STATE_LINE_COMMENTS => 'StateLineComments',
            FstForStringTransfor::STATE_LINES_COMMENTS => 'StateLinesComments',
            FstForStringTransfor::STATE_END => 'StateEnd',
        ];
        if (!isset($stateMap2Operator[$state->state])) {
            throw new Exception('未定义的状态：' . $state->state);
        }
        if (!isset($this->stateOperatorCache[$state->state])) {
            $operatorClass = "StringCommentsTransfor\\StringOperators\\{$stateMap2Operator[$state->state]}";
            return $this->stateOperatorCache[$state->state] = new $operatorClass($state);
        }
        return $this->stateOperatorCache[$state->state];
    }
}



