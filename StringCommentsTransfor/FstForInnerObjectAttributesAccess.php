<?php
namespace StringCommentsTransfor;

use StringCommentsTransfor\VariableOperators\OperatorFactory;

/**
 * 处理对象内访问内部属性
 *
 * TODO 为了省事，同时把此类创建的对象充当状态存储器，被Operator进行操作。
 *
 * FST - Finite State Transducer
 * @see https://www.cnblogs.com/jiu0821/p/7688669.html
 */
class FstForInnerObjectAttributesAccess
{
    /** @var int */
    const STATE_DEFAULT = 0;

    /** @var int CODE_NO_TYPE */
    const TYPE_CODE = 0;

    /** @var int */
    const TYPE_STRING = 1;

    /** @var int */
    const TYPE_COMMENTS = 2;

    /** @var int */
    const TYPE_CODE_OTHERS = 3;

    /** @var int */
    const TYPE_OOP_THIS = 4;

    /** @var int 转换出来的类型，用户自定义的类，所属的类型 */
    const TYPE_COVERD_CLASS = 5;

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
     * @var array 映射用户导入的类型到C源码类型
     * 图省事，作public属性
     */
    public $availableClasssMap = [];

    /**
     * @var array
     * 图省事，作public属性
     */
    public $userDefineFunctionMapping = [];

    /** @var string 图省事，作public属性 */
    public $fileName;

    /** @var string 图省事，作public属性 */
    public $dir;

    /**
     * @param array $tokens ['type' => , 'token' => '']
     * @param array $availableClasssMap 映射用户导入的类型到C源码类型
     * @param array $userDefineFunctionMapping
     * @param string $fileName
     * @param string $dir
     */
    public function __construct(array $tokens, array $availableClasssMap, array $userDefineFunctionMapping, string $fileName, string $dir)
    {
        $this->state = static::STATE_DEFAULT;
        $this->tokens = $tokens;
        $this->availableClasssMap = $availableClasssMap;
        $this->userDefineFunctionMapping = $userDefineFunctionMapping;
        $this->setFileNameDir($fileName, $dir);
        $this->parse();
    }

    /**
     * @return array
     */
    public function getTokenItems(): array
    {
        echo '替换出来的类型是' . PHP_EOL;
        foreach ($this->markTokens as $t) {
            echo json_encode($t) . PHP_EOL;
        }
        return $this->markTokens;
    }

    /**
     * @param string $fileName
     * @param string $dir
     * @return void
     */
    private function setFileNameDir(string $fileName, string $dir)
    {
        $this->fileName = $fileName;
        if (!is_file($fileName)) {
            throw new Exception('文件不存在' . $fileName);
        }
        $this->dir = $dir;
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



