<?php
/**
 * 解析和提取源代码中的函数方法
 */
class DefineMetheds
{
    /** @var string */
    private $fileName;

    /** @var string */
    private $dir;

    /** @var string */
    private $fileText;

    /** @var string[] */
    private $methodCodes = [];

    /** @var DefineMethed[] */
    private $methodModules = [];

    /** @var EntryMethod */
    private $entryFunction;

    /**
     * @param string $fileName
     * @param string $dir
     */
    public function __construct(string $fileName, string $dir)
    {
        $this->dir = $dir;
        $this->fileName = $fileName;
        if (!is_file($fileName)) {
            throw new Exception('文件不存在' . $fileName);
        }
        $this->fileText = file_get_contents($fileName);
        $this->entryFunction = new EntryMethod($this->fileName, trim(getEntryFunctionDefine($this->fileName)), $this->dir);
        $this->newFunction = new NewMethod(
            $this->fileName,
            trim(getEntryFunctionNewDefine($this->fileName)),
            $this->dir,
            getEntryFunctionNewDefineLine($this->fileName)
        );
        $this->parse();
    }

    /**
     * @return DefineMethed[]
     */
    public function getMethods(): array
    {
        return $this->methodModules;
    }

    public function getEntry(): EntryMethod
    {
        return $this->entryFunction;
    }

    public function getNewFunction(): NewMethod
    {
        return $this->newFunction;
    }

    /**
     * @return void
     */
    private function parse()
    {
        $lines = explode(PHP_EOL, $this->fileText);
        foreach ($lines as $offset => $line) {
            // 判断此行是不是定义了函数
            if (
                $this->startWithType($line)
                && $this->endWithSeparator($line, '{')
                && $this->haveSimbol($line, ['(', ')'])
            ) {
                $this->prepareMethod($offset);
            }
        }
    }

    /**
     * @param string $line
     * @return bool
     */
    private function startWithType(string $line): bool
    {
        if (0 !== strpos($line, ' ')) {
            $types = array_keys(getTypesToCTypeMapping($this->fileName, $this->dir));
            foreach ($types as $type) {
                if (0 === strpos($line, $type . ' ')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $line
     * @param string $separator
     * @return bool
     */
    private function endWithSeparator(string $line, string $separator): bool
    {
        $offset = strpos($line, $separator);
        if (false === $offset) {
            return false;
        }
        if ($offset + strlen($separator) == strlen($line)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $line
     * @param array $simbol
     * @return bool
     */
    private function haveSimbol(string $line, array $simbol): bool
    {
        foreach ($simbol as $s) {
            if (false === strpos($line, $s)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param int $offset
     * @return void
     */
    private function prepareMethod(int $offset)
    {
        $methodCode = '';
        $state = false;
        $end = 0;
        $lines = explode(PHP_EOL, $this->fileText);
        foreach ($lines as $i => $line) {
            if ($i == $offset) {
                $state = true;
            }
            if ($state) {
                if (!empty($methodCode)) {
                    $methodCode .= PHP_EOL;
                }
                $methodCode .= $line;
                if (0 === strpos($line, '}')) {
                    $state = false;
                    break;
                }
            }
        }
        $this->methodCodes[] = $methodCode;
        $this->methodModules[] = new DefineMethed($this->fileName, $methodCode, $this->dir, $lines[$offset]);
    }
}

