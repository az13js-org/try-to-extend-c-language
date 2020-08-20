<?php
namespace StringCommentsTransfor\InnerObjectAttributesAccessOperators;

use StringCommentsTransfor\FstForInnerObjectAttributesAccess;

interface OperatorInterface
{
    /**
     * @param FstForInnerObjectAttributesAccess $state
     */
    public function __construct(FstForInnerObjectAttributesAccess $state);

    /**
     * @param array|null $token
     * @return void
     */
    public function doOperation($token);
}



