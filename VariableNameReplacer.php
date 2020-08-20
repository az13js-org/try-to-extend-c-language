<?php
/**
 * 变量名替换工具
 */
class VariableNameReplacer
{
    /** @var string */
    private $fileName;

    /** @var string */
    private $dir;

    /** @var string */
    private $replacedCode = '';

    /** @var array 键是变量的名称，值是变量的类型 */
    private $functionInputParams = [];

    /**
     * @param string $functionBodyCode
     * @param array $availableClasss
     * @param array $availableTypes
     * @param array $userDefineFunctionMapping
     * @param string $fileName
     * @param string $dir
     */
    public function __construct(
        string $functionBodyCode,
        array $availableClasss,
        array $availableTypes,
        array $userDefineFunctionMapping,
        string $fileName,
        string $dir
    ) {
        $this->fileName = $fileName;
        if (!is_file($fileName)) {
            throw new Exception('文件不存在' . $fileName);
        }
        $this->dir = $dir;
        $this->initFunctionInputParams();
        $stringCommentTokens = $this->stringCommentsTokens($functionBodyCode);
        $typesNoCommentTokens = $this->noCommentsTokens($stringCommentTokens, $availableClasss, $userDefineFunctionMapping);
        $assObjectAccess = $this->objectAccess($typesNoCommentTokens);
        $callOrAccess = $this->markCallOrAccess($assObjectAccess);
        echo json_encode($callOrAccess) . PHP_EOL;
        $this->replacedCode = $this->buildString($callOrAccess);
    }

    /**
     * 返回转换成的C语言代码
     *
     * @return string
     */
    public function getReplacedCode(): string
    {
        return $this->replacedCode;
    }

    /**
     * @return string
     */
    public function buildString(array $tokens): string
    {
        $str = '';
        foreach ($tokens as $key => $token) {
            $str .= $token['token'];
        }
        return $str;
    }

    /**
     * @return void
     */
    private function initFunctionInputParams()
    {
        return [];
    }

    /**
     * @return string
     */
    private function markCallOrAccess(array $tokensSource): array
    {
        $tokens = $tokensSource;
        $state = 'inFunction';
        $name = '';
        $keyAsk = 0;
        foreach ($tokens as $key => $token) {
            if ('inFunction' == $state) {
                if (StringCommentsTransfor\FstForVariableTransfor::TYPE_ACCESS == $token['type']) {
                    $state = 'readyForAccess';
                    $keyAsk = $key;
                } else {
                }
            } elseif ('readyForAccess' == $state) {
                if (StringCommentsTransfor\FstForVariableTransfor::TYPE_COMMENTS == $token['type']) {
                } elseif (empty(trim($token['token']))) {
                } elseif ($this->isName($token['token'])) {
                    $state = 'inName';
                    $name = $token['token'];
                } else {
                    $state = 'inFunction';
                }
            } elseif ($state == 'inName') {
                if (StringCommentsTransfor\FstForVariableTransfor::TYPE_COMMENTS == $token['type']) {
                } elseif (empty(trim($token['token']))) {
                } elseif ('(' == $token['token']) {
                    $state = 'inFunction';
                    $tokens[$keyAsk]['type'] = StringCommentsTransfor\FstForVariableTransfor::TYPE_CALL;
                    $tokens[$keyAsk]['comment'] = 'Call a method, name is ' . trim($name);
                    $tokens[$keyAsk]['methodName'] = trim($name);
                } else {
                    $state = 'inFunction';
                    $tokens[$keyAsk]['comment'] = 'Call a attribute, name is ' . trim($name);
                    $tokens[$keyAsk]['attrName'] = trim($name);
                }
            } else {
                throw new Exception('未知状态');
            }
        }
        unset($token);
        return $tokens;
    }

    /**
     * 第一步，首先进行粗加工，将麻烦的注释和字符串提取出来，因为这些内容含有
     * 任何可能的关键字，但是它们不需要被转换。
     *
     * @param string $functionBodyCode
     * @return array
     */
    private function stringCommentsTokens(string $functionBodyCode): array
    {
        if (empty($functionBodyCode)) {
            return [];
        }
        $processor = new TokenProcessor(
            $functionBodyCode,
            ["\"", "//", "/*", "*/", PHP_EOL, "\\"],
        );
        $fst = new StringCommentsTransfor\FstForStringTransfor($processor->getTokens());
        return $fst->getTokenItems();
    }

    /**
     * 第二步，针对关键字new进行处理，以及导入的类名称进行识别。删除注释
     *
     * @param array $tokens 第一步得到的 token
     * @param array $availableClasss 类型名称和对应替换的类型
     * @param array $userDefineFunctionMapping
     * @return array
     */
    private function noCommentsTokens(array $stringCommentTokens, array $availableClasss, array $userDefineFunctionMapping): array
    {
        $keywordToken = array_merge(["new", "this"], array_keys($availableClasss));
        $typeTokens = [];
        foreach ($stringCommentTokens as $item) {
            if (StringCommentsTransfor\FstForStringTransfor::TYPE_CODE == $item['type']) {
                $processor = new TokenProcessor(
                    $item['token'],
                    // . 放在下一步解析
                    array_merge(["}", "{", "(", " ", PHP_EOL, "\t", ";", "new", "this"], array_keys($availableClasss)),
                );
                foreach ($processor->getTokens() as $token) {
                    // 类型维持不变，FstForVariableTransfor内再去调整
                    $typeTokens[] = [
                        'type' => StringCommentsTransfor\FstForStringTransfor::TYPE_CODE,
                        'token' => $token,
                        'isKeyword' => in_array($token, $keywordToken),
                        'startAllow' => $this->startWith($token, $this->allow()),
                        'endAllow' => $this->endWith($token, $this->allow()),
                    ];
                }
                unset($token);
            } else {
                $typeTokens[] = $item;
            }
        }
        unset($item);
        // 修复一个BUG。任何出现关键字的变量被误拆开了。
        $typeTokens = $this->fixErrorTransfor($typeTokens);
        $fst = new StringCommentsTransfor\FstForVariableTransfor($typeTokens, $availableClasss, $userDefineFunctionMapping, $this->fileName, $this->dir);
        return $fst->getTokenItems();
    }

    /**
     * @param array $mayErrorToken
     * @return array
     */
    private function fixErrorTransfor(array $mayErrorToken): array
    {
        $inputs = $mayErrorToken;
        foreach ($inputs as &$token) {
            if ($token['type'] == StringCommentsTransfor\FstForStringTransfor::TYPE_CODE) {
                if ($token['isKeyword']) {
                    $token['tempInput'] = 1;
                } elseif ($token['startAllow'] && $token['endAllow']) {
                    $token['tempInput'] = 2;
                } elseif ($token['startAllow']) {
                    $token['tempInput'] = 3;
                } elseif ($token['endAllow']) {
                    $token['tempInput'] = 4;
                } else {
                    $token['tempInput'] = 5;
                }
            } else {
                $token['tempInput'] = 0;
            }
        }
        unset($token);
        /*
            tempInput: 0 是注释或是字符串
            tempInput: 1 是关键字
            tempInput: 2 开始和结束是合法的声明
            tempInput: 3 开始是合法的声明，但结束不是
            tempInput: 4 结束是合法的声明，但开始不是
            tempInput: 5 其它暂时没有分析的代码，开始和结束都不是声明
        */
        $rightTokens = [];
        // 0 默认状态，1 后面能接受关键字连接
        $state = 0;
        $buffer = '';
        foreach ($inputs as $token) {
            switch ($state) {
            case 0:
                switch ($token['tempInput']) {
                case 0:
                    $rightTokens[] = $token;
                    break;
                case 1:
                    $buffer = $token['token'];
                    $state = 1;
                    break;
                case 2:
                    $buffer = $token['token'];
                    $state = 1;
                    break;
                case 3:
                    $rightTokens[] = $token;
                    break;
                case 4:
                    $buffer = $token['token'];
                    $state = 1;
                    break;
                case 5:
                    $rightTokens[] = $token;
                    break;
                default:
                    throw new Exception('Unknow tempInput=' . $token['tempInput']);
                }
                break;
            case 1:
                switch ($token['tempInput']) {
                case 0:
                    $rightTokens[] = [
                        'token' => $buffer,
                        'type' => StringCommentsTransfor\FstForStringTransfor::TYPE_CODE,
                    ];
                    $rightTokens[] = $token;
                    $buffer = '';
                    $state = 0;
                    break;
                case 1:
                    $buffer .= $token['token'];
                    break;
                case 2:
                    $buffer .= $token['token'];
                    break;
                case 3:
                    $rightTokens[] = [
                        'token' => $buffer . $token['token'],
                        'type' => StringCommentsTransfor\FstForStringTransfor::TYPE_CODE,
                    ];
                    $buffer = '';
                    $state = 0;
                    break;
                case 4:
                    $rightTokens[] = [
                        'token' => $buffer,
                        'type' => StringCommentsTransfor\FstForStringTransfor::TYPE_CODE,
                    ];
                    $buffer = $token['token'];
                    break;
                case 5:
                    $rightTokens[] = [
                        'token' => $buffer,
                        'type' => StringCommentsTransfor\FstForStringTransfor::TYPE_CODE,
                    ];
                    $buffer = '';
                    $rightTokens[] = $token;
                    $state = 0;
                    break;
                default:
                    throw new Exception('Unknow tempInput=' . $token['tempInput']);
                }
                break;
            default:
                throw new Exception('Unknow state=' . $state);
            }
        }
        if (!empty($buffer)) {
            $rightTokens[] = [
                'token' => $buffer,
                'type' => StringCommentsTransfor\FstForStringTransfor::TYPE_CODE,
            ];
        }
        return $rightTokens;
    }

    /**
     * @param array $tokens
     * @return array
     */
    private function objectAccess(array $tokens): array
    {
        $result = [];
        foreach ($tokens as $token) {
            if (strlen($token['token']) <= strlen(' ')) {
                $result[] = $token;
            } elseif (StringCommentsTransfor\FstForVariableTransfor::TYPE_STRING == $token['type']) {
                $result[] = $token;
            } elseif (StringCommentsTransfor\FstForVariableTransfor::TYPE_COMMENTS == $token['type']) {
                $result[] = $token;
            } else {
                $template = $token;
                $processor = new TokenProcessor($token['token'], ['..', '.', ')', '<', '>', '=']);
                foreach ($processor->getTokens() as $subToken) {
                    $template['token'] = $subToken;
                    $result[] = $template;
                }
            }
        }
        $total = count($result, COUNT_NORMAL);
        $results = [];
        $lastToken = [];
        for ($i = 0; $i < $total; $i++) {
            if (0 == $i) {
                $results[$i] = $result[$i];
                $lastToken = $result[$i];
            } elseif (in_array($result[$i]['type'], [StringCommentsTransfor\FstForVariableTransfor::TYPE_COMMENTS])) {
                $results[$i] = $result[$i];
            } else {
                if ('.' == $result[$i]['token'] && !$this->endWith($lastToken['token'], ['0','1','2','3','4','5','6','7','8','9'])) {
                    $results[$i] = $result[$i];
                    $results[$i]['token'] = '->';
                    $results[$i]['type'] = StringCommentsTransfor\FstForVariableTransfor::TYPE_ACCESS;
                    $results[$i]['comment'] = 'Access a attribute or method in a object.';
                } else {
                    $results[$i] = $result[$i];
                }
                $lastToken = $result[$i];
            }
        }
        return $results;
    }

    private function endWith(string $src, array $finds)
    {
        foreach ($finds as $find) {
            $last = strrpos($src, $find);
            if ($last !== false) {
                if ($last + strlen($find) == strlen($src)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function startWith(string $src, array $finds)
    {
        foreach ($finds as $find) {
            $last = strpos($src, $find);
            if ($last === 0) {
                return true;
            }
        }
        return false;
    }

    private function allow(): array
    {
        $numbers = ['0','1','2','3','4','5','6','7','8','9'];
        $charsLow = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        $charsUp = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        return array_merge($numbers, $charsLow, $charsUp, ['_']);
    }

    /**
     * 检测是不是合法的用户自定义名称
     */
    private function isName(string $maybeName): bool
    {
        $numbers = ['0','1','2','3','4','5','6','7','8','9'];
        $charsLow = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        $charsUp = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        foreach (str_split($maybeName) as $ch) {
            if (!in_array($ch, $numbers) && !in_array($ch, $charsLow) && !in_array($ch, $charsUp)) {
                if ('_' != $ch) {
                    return false;
                }
            }
        }
        if ($this->startWith($maybeName, $numbers)) {
            return false;
        }
        return true;
    }
}

