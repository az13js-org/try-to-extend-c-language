<?php
/**
 * 解析和提取源代码中的函数方法
 */
class DefineMethed
{
    /** @var string */
    private $fileName;

    /** @var string */
    private $fileText;

    /** @var string */
    private $dir;

    /** @var string */
    private $methodCode;

    /** @var string */
    private $methodDefineReturnType;

    /** @var string */
    private $methodDefineName;

    /** @var string[] */
    private $defineSourceCode = [];

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
        $this->fileText = file_get_contents($fileName);
        $this->methodCode = $methodCode;
        $this->dir = $dir;
        $this->defineParams = $this->parseInputParams($defineCode);
        $this->parse();
    }

    /**
     * @return string
     */
    public function getHeaderDefineString(): string
    {
        $uniqSourceLowerCase = uniqSourceLowerCase($this->fileName);
        $map = getTypesToCTypeMapping($this->fileName, $this->dir);
        $cMethodName = $uniqSourceLowerCase . '_' . trim(strtolower($this->methodDefineName));
        $str = '';
        $str .= $map[$this->methodDefineReturnType] . ' ' . $cMethodName . '(';
        $str .= "struct ${uniqSourceLowerCase}_attributes *this";
        foreach ($this->defineParams as $define) {
            if (!isset($map[$define['type']])) {
                throw new Exception("未知的类型：{$define['type']}");
            }
            $str .= ', ' . $map[$define['type']] . ' ' . $define['name'];
        }
        $str .= ');';
        return $str;
    }

    /**
     * @return string
     */
    public function getCCodeDefineString(): string
    {
        $uniqSourceLowerCase = uniqSourceLowerCase($this->fileName);
        $map = getTypesToCTypeMapping($this->fileName, $this->dir);
        $cMethodName = $uniqSourceLowerCase . '_' . trim(strtolower($this->methodDefineName));
        $str = '';
        $str .= $map[$this->methodDefineReturnType] . ' ' . $cMethodName . '(';
        $str .= "struct ${uniqSourceLowerCase}_attributes *this";
        foreach ($this->defineParams as $define) {
            $str .= ', ' . $map[$define['type']] . ' ' . $define['name'];
        }
        $str .= ') {' . PHP_EOL;

        $functionBodyUserCode = '';
        foreach ($this->defineSourceCode as $srcCode) {
            $functionBodyUserCode .= $srcCode . PHP_EOL;
        }

        $str .= $this->parseFunctionSource2C($functionBodyUserCode);
        $str .= '}';
        return $str;
    }

    /**
     * @return string
     */
    private function getCCodeParmsString(): string
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
            if (!empty($def)) {
                $params[] = [
                    'type' => $def,
                    'name' => $name,
                    'default' => null,
                ];
            }
        }
        return $params;
    }

    /**
     * @param string $functionBodyUserCode
     * @return string
     */
    private function parseFunctionSource2C(string $functionBodyUserCode): string
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
     * @return void
     */
    private function parse()
    {
        $lines = explode(PHP_EOL, $this->methodCode);
        $define = $lines[0];
        $this->returnType($define);
        $this->nameOfMethod($define);
        unset($lines[0]);
        foreach ($lines as $srcCode) {
            if (0 !== strpos($srcCode, '}')) {
                $this->defineSourceCode[] = $srcCode;
            }
        }
    }

    /**
     * @param string $define
     * @return void
     */
    private function returnType(string $define)
    {
        foreach (array_keys(getTypesToCTypeMapping($this->fileName, $this->dir)) as $t) {
            if (0 === strpos($define, $t . ' ')) {
                $this->methodDefineReturnType = $t;
                break;
            }
        }
        if (empty($this->methodDefineReturnType)) {
            throw new Exception('方法返回值解析失败');
        }
    }

    /**
     * @param string $define
     * @return void
     */
    private function nameOfMethod(string $define)
    {
        $cleanNoReturn = trim(ltrim($define, $this->methodDefineReturnType));
        $paramsStartOffset = strpos($cleanNoReturn, '(');
        if (false === $paramsStartOffset) {
            throw new Exception('解析方法名称失败');
        }
        $this->methodDefineName = trim(substr($cleanNoReturn, 0, $paramsStartOffset));
    }
}

