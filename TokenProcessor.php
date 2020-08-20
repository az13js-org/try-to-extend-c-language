<?php
class TokenProcessor
{
    /** @var string */
    private $sourceCode;

    /** @var array */
    private $tokens;

    /** @var array 解析后拆分的Token，用于返回给调用方 */
    private $parsedTokens = [];

    /**
     * @param string $src
     * @param array $tokens
     */
    public function __construct(string $src, array $tokens)
    {
        if (empty($src)) {
            throw new Exception("需要解析token的字符串不能为空");
        }
        if (empty($tokens)) {
            throw new Exception("Token不能为空");
        }
        foreach ($tokens as $token) {
            if (!is_string($token)) {
                throw new Exception('Token 非字符串："' . print_r($token, true) . '"');
            }
        }
        $this->sourceCode = $src;
        $this->tokens = $tokens;
        $this->startParse();
    }

    /**
     * 返回解析后的Tokens数组
     *
     * @return string[]
     */
    public function getTokens(): array
    {
        return $this->parsedTokens;
    }

    /**
     * @return void
     */
    private function startParse()
    {
        $workString = $this->sourceCode;
        while (true) {
            $tokenAndOffset = [];
            foreach ($this->tokens as $token) {
                $tokenAndOffset[$token] = strpos($workString, $token);
            }
            $minOffset = null;
            $minOffsetToken = null;
            foreach ($tokenAndOffset as $token => $offset) {
                if (
                    (is_null($minOffset) && false !== $offset)
                    || (false !== $offset && $offset < $minOffset)
                ) {
                    $minOffset = $offset;
                    $minOffsetToken = $token;
                } elseif (!is_null($minOffset) && false !== $offset && $offset == $minOffset) {
                    if (strlen($token) > strlen($minOffsetToken)) {
                        $minOffset = $offset;
                        $minOffsetToken = $token;
                    }
                }
            }
            if (is_null($minOffset)) {
                $this->parsedTokens[] = $workString;
                $workString = '';
            } elseif ($minOffset == 0) {
                $this->parsedTokens[] = $minOffsetToken;
                $workString = substr($workString, strlen($minOffsetToken));
            } else {
                $this->parsedTokens[] = substr($workString, 0, $minOffset);
                $this->parsedTokens[] = $minOffsetToken;
                $workString = substr($workString, $minOffset + strlen($minOffsetToken));
            }
            if (empty($workString)) {
                break;
            }
        }
    }
}



