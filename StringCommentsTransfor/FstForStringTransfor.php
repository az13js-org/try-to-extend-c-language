<?php
namespace StringCommentsTransfor;

use StringCommentsTransfor\StringOperators\OperatorFactory;

/**
 * 去除代码注释并将字符串定义提取出来。
 *
 * TODO 为了省事，同时把此类创建的对象充当状态存储器，被Operator进行操作。
 *
 * FST - Finite State Transducer
 * @see https://www.cnblogs.com/jiu0821/p/7688669.html
 */
class FstForStringTransfor
{
    /** @var int */
    const STATE_DEFAULT = 0;

    /** @var int */
    const STATE_IN_STRING = 1;

    /** @var int */
    const STATE_ESCAPE = 2;

    /** @var int */
    const STATE_EXCEPTION = 3;

    /** @var int */
    const STATE_LINE_COMMENTS = 4;

    /** @var int */
    const STATE_LINES_COMMENTS = 5;

    /** @var int */
    const STATE_END = 6;

    /** @var int */
    const TYPE_CODE = 0;

    /** @var int */
    const TYPE_STRING = 1;

    /** @var int */
    const TYPE_COMMENTS = 2;

    /** @var array */
    private $tokens;

    /** @var int 图省事，作public属性 */
    public $state;

    /** @var string 图省事，作public属性 */
    public $buffer = '';

    /**
     * @var array 元素结构：['type' => TYPE_xxx, 'token' => 'string']
     * 图省事，作public属性
     */
    public $markTokens = [];

    /**
     * @param array $tokens
     */
    public function __construct(array $tokens)
    {
        $this->state = static::STATE_DEFAULT;
        $this->tokens = $tokens;
        $this->parse();
    }

    /**
     * @return array
     */
    public function getTokenItems(): array
    {
        return $this->markTokens;
    }

    /**
     * @return void
     */
    private function parse()
    {
        $opFactory = new OperatorFactory();
        foreach (array_merge($this->tokens, [null]) as $token) {
            $operator = $opFactory->getOperator($this);
            $operator->doOperation($token);
        }
    }
}



