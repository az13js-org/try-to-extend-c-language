<?php
class NewMethod
{
    /** @var string */
    private $fileName;

    /** @var string */
    private $dir;

    /** @var string */
    private $methodCode;

    /** @var string */
    private $cMethodCode;

    /** @var array */
    private $defineParams;

    /**
     * @param string $fileName
     * @param string $methodCode
     * @param string $dir
     */
    public function __construct(string $fileName, string $methodCode, string $dir, string $defineCode)
    {
        $this->fileName = $fileName;
        if (!is_file($fileName)) {
            throw new Exception('文件不存在' . $fileName);
        }
        $this->methodCode = $methodCode;
        $this->dir = $dir;
        $this->defineParams = $this->parseInputParams($defineCode);
    }

    /**
     * @return string
     */
    public function getCCodeDefineString(): string
    {
        if (empty($this->methodCode)) {
            return '';
        }
        $this->parse();
        return $this->cMethodCode;
    }

    /**
     * @return string
     */
    public function getCCodeParmsString(): string
    {
        if (empty($this->defineParams)) {
            return 'void';
        }
        $defineCode = '';
        $userCodeTypes2CStruct = getTypesToCTypeMapping($this->fileName, $this->dir);
        foreach ($this->defineParams as $paramInfo) {
            if (!empty($defineCode)) {
                $defineCode .= ', ';
            }
            if (isset($userCodeTypes2CStruct[$paramInfo['type']])) {
                $defineCode .= $userCodeTypes2CStruct[$paramInfo['type']];
            } else {
                $defineCode .= $paramInfo['type'];
            }
            $defineCode .= " {$paramInfo['name']}";
        }
        return $defineCode;
    }

    /**
     * @return void
     */
    private function parse()
    {
        $uniqSourceLowerCase = uniqSourceLowerCase($this->fileName);
        $coverCCode = $this->parseFunctionSource2C();
        $this->cMethodCode = <<<MAIN_FUNCTION
    struct ${uniqSourceLowerCase}_attributes *this = (struct ${uniqSourceLowerCase}_attributes *)malloc(sizeof(struct ${uniqSourceLowerCase}_attributes));
    if (NULL != this) {
$coverCCode
    }
    return this;

MAIN_FUNCTION;
    }

    /**
     * @return string
     */
    private function parseFunctionSource2C(): string
    {
        $userDefineFunctionMapping = $defaultTypesMapping = $userDefineTypeMapping = [];
        $defaultTypes = getDefaultTypes();
        foreach (getTypesToCTypeMapping($this->fileName, $this->dir) as $functionUse => $realCType) {
            if (in_array($functionUse, $defaultTypes)) {
                $defaultTypesMapping[$functionUse] = $realCType;
            } else {
                $userDefineTypeMapping[$functionUse] = $realCType;
            }
        }
        foreach (getTypesToCFunctionPrefixMapping($this->fileName, $this->dir) as $functionUse => $realCFuncPrefix) {
            $userDefineFunctionMapping[$functionUse] = $realCFuncPrefix;
        }
        $functionBodyUserCode = '';
        foreach (explode(PHP_EOL, $this->methodCode) as $line) {
            if (0 === strpos($line, ' ')) {
                if (!empty($functionBodyUserCode)) {
                    $functionBodyUserCode .= PHP_EOL;
                }
                $functionBodyUserCode .= '    ' . $line;
            }
        } 
        $replacer = new VariableNameReplacer(
            $functionBodyUserCode,
            $userDefineTypeMapping,
            $defaultTypesMapping,
            $userDefineFunctionMapping,
            $this->fileName,
            $this->dir
        );
        return $replacer->getReplacedCode();
    }

    /**
     * @param string $defineLine
     * @return array
     */
    private function parseInputParams(string $defineLine): array
    {
        if (empty($defineLine)) {
            return [];
        }
        $tokens = (new TokenProcessor($defineLine, ['(', ')']))->getTokens();
        $state = 0;
        $paramsDefine = '';
        foreach ($tokens as $token) {
            switch ($state) {
            case 0:
                if ('(' == $token) {
                    $state = 1;
                } elseif (')' == $token) {
                    throw new Exception('定义方法的时候，没有找到(突然结束');
                } else {
                }
                break;
            case 1:
                if ('(' == $token) {
                    throw new Exception('定义方法的时候，遇到两个(');
                } elseif (')' == $token) {
                    $state = 2;
                } else {
                    $paramsDefine .= $token;
                }
                break;
            case 2:
                break;
            default:
                throw new Exception('异常函数定义解析');
            }
        }
        unset($token);
        $params = []; // ['type' => 'int', 'name' => 'x', 'default' => null]
        $expo = explode(',', $paramsDefine);
        foreach ($expo as $defString) {
            $branks = explode(' ', trim($defString));
            $name = array_pop($branks);
            $def = trim(implode(' ', $branks));
            $params[] = [
                'type' => $def,
                'name' => $name,
                'default' => null,
            ];
        }
        return $params;
    }
}

