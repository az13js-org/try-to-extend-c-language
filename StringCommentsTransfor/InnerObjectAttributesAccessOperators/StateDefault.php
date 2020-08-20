<?php
namespace StringCommentsTransfor\InnerObjectAttributesAccessOperators;

use StringCommentsTransfor\FstForInnerObjectAttributesAccess;
use Exception;

class StateDefault implements OperatorInterface
{
    /** @var FstForInnerObjectAttributesAccess */
    private $state;

    /**
     * @param FstForInnerObjectAttributesAccess $state
     */
    public function __construct(FstForInnerObjectAttributesAccess $state)
    {
        $this->state = $state;
    }

    /**
     * @param string|null $token
     * @return void
     */
    public function doOperation($token)
    {
        if (FstForInnerObjectAttributesAccess::STATE_DEFAULT != $this->state->state) {
            throw new Exception('状态机异常，状态与操作不对应');
        }
    }
}



