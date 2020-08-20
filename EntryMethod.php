<?php
class EntryMethod
{
    /** @var string */
    private $fileName;

    /** @var string */
    private $dir;

    /** @var string */
    private $methodCode;

    /** @var string */
    private $cMethodCode;

    /**
     * @param string $fileName
     * @param string $methodCode
     * @param string $dir
     */
    public function __construct(string $fileName, string $methodCode, string $dir)
    {
        $this->fileName = $fileName;
        if (!is_file($fileName)) {
            throw new Exception('文件不存在' . $fileName);
        }
        $this->methodCode = $methodCode;
        $this->dir = $dir;
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
     * @return void
     */
    private function parse()
    {
        $uniqSourceLowerCase = uniqSourceLowerCase($this->fileName);
        $coverCCode = $this->parseFunctionSource2C();
        $this->cMethodCode = <<<MAIN_FUNCTION
int main(void) {
    struct ${uniqSourceLowerCase}_attributes *this = (struct ${uniqSourceLowerCase}_attributes *)malloc(sizeof(struct ${uniqSourceLowerCase}_attributes));
    if (NULL != this) {
$coverCCode
        free(this);
    }
    return 0;
}

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
}

