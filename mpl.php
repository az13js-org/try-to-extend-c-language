#!/opt/php_7_4_6/bin/php
<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

if (!isset($_SERVER['PWD'])) {
    echo 'Miss PWD' . PHP_EOL;
    exit();
}

if (!is_dir($_SERVER['PWD'])) {
    echo $_SERVER['PWD'] . ' is not a dir.' . PHP_EOL;
    exit();
}

function getImportClass(string $fileName): array
{
    $importClasses = [];
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    foreach ($lines as $line) {
        $cleanLine = trim($line);
        if (0 === strpos($cleanLine, '@')) {
            $location = ltrim($cleanLine, '@');
            $locationArray = explode('/', $location);
            $importClasses[] = $locationArray[count($locationArray, COUNT_NORMAL) - 1];
        }
    }
    return $importClasses;
}

function getImportClassWithPath(string $fileName): array
{
    $importClasses = [];
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    foreach ($lines as $line) {
        $cleanLine = trim($line);
        if (0 === strpos($cleanLine, '@')) {
            $importClasses[] = trim(ltrim($cleanLine, '@'));
        }
    }
    return $importClasses;
}

function getImportClassAlias(string $fileName): array
{
    // TODO 现在没有实现别名，以后再说。这里直接用导入的类名做别名就算了
    return getImportClass($fileName);
}

function getDefaultTypes(): array
{
    return ['void', 'int', 'char', 'float', 'double', 'long', 'short', 'unsigned'];
}

function getTypes(string $fileName): array
{
    $defaultTypes = getDefaultTypes();
    $existTypes = array_merge($defaultTypes, getImportClassAlias($fileName));
    return array_unique($existTypes);
}

function getTypesToCTypeMapping(string $fileName, string $dir): array
{
    $map = [];
    $defaultTypes = getDefaultTypes();
    foreach ($defaultTypes as $type) {
        $map[$type] = $type;
    }
    $userImportClass = getImportClassWithPath($fileName);
    $userDefineAlias = getImportClassAlias($fileName);
    if (count($userImportClass) != count($userDefineAlias)) {
        throw new Exception('Import class != alias');
    }
    foreach ($userDefineAlias as $k => $alias) {
        $map[$alias] = 'struct ' . strtolower($dir) . '_' . strtolower(str_replace('/', '_', $userImportClass[$k])) . '_attributes *';
    }
    // 对于给定的文件来说，里面代码可用的类型还有一个，就是它自己。
    $selfClass = pathinfo($fileName, PATHINFO_FILENAME);
    $uniqSourceLowerCase = uniqSourceLowerCase($fileName);
    $map[$selfClass] = "struct ${uniqSourceLowerCase}_attributes *";
    return $map;
}

function getTypesToCFunctionPrefixMapping(string $fileName, string $dir): array
{
    $map = [];
    $userImportClass = getImportClassWithPath($fileName);
    $userDefineAlias = getImportClassAlias($fileName);
    if (count($userImportClass) != count($userDefineAlias)) {
        throw new Exception('Import class != alias');
    }
    foreach ($userDefineAlias as $k => $alias) {
        $map[$alias] = strtolower($dir) . '_' . strtolower(str_replace('/', '_', $userImportClass[$k])) . '_';
    }
    // 对于给定的文件来说，里面代码可用的类型还有一个，就是它自己。
    $selfClass = pathinfo($fileName, PATHINFO_FILENAME);
    $uniqSourceLowerCase = uniqSourceLowerCase($fileName);
    $map[$selfClass] = "${uniqSourceLowerCase}_";
    return $map;
}

function getEntryFunctionDefine(string $fileName): string
{
    $functionCode = '';
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    $haveEntry = false;
    foreach ($lines as $line) {
        foreach (explode('(', $line) as $n => $ins) {
            if (trim($ins) == '+new' && 0 == $n && 0 === strpos($line, '+new')) {
                $haveEntry = true;
            }
        }
        if ($haveEntry) {
            $functionCode .= $line . PHP_EOL;
        }
        if ($haveEntry && 0 === strpos($line, '}')) {
            $haveEntry = false;
            break;
        }
    }
    return $functionCode;
}

function getEntryFunctionNewDefine(string $fileName): string
{
    $functionCode = '';
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    $haveEntry = false;
    foreach ($lines as $line) {
        foreach (explode('(', $line) as $n => $ins) {
            if (trim($ins) == 'new' && 0 == $n && 0 === strpos($line, 'new')) {
                $haveEntry = true;
            }
        }
        if ($haveEntry) {
            $functionCode .= $line . PHP_EOL;
        }
        if ($haveEntry && 0 === strpos($line, '}')) {
            $haveEntry = false;
            break;
        }
    }
    return $functionCode;
}

function getEntryFunctionNewDefineLine(string $fileName): string
{
    $functionCode = '';
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    $haveEntry = false;
    foreach ($lines as $line) {
        foreach (explode('(', $line) as $n => $ins) {
            if (trim($ins) == 'new' && 0 == $n && 0 === strpos($line, 'new')) {
                return $line;
            }
        }
    }
    return '';
}

function startWith(string $contents, string $find): bool
{
    return 0 === strpos(trim($contents), $find);
}

function endWith(string $contents, string $find): bool
{
    $cleanText = trim($contents);
    if (false === ($res = strpos($cleanText, $find))) {
        return false;
    }
    return ($res + strlen($find)) == strlen($cleanText);
}

function cleanToken(string $input): string
{
    $result = str_replace(PHP_EOL, ' ', $input);
    $result = str_replace("\t", ' ', $result);
    return str_replace('  ', ' ', $result);
}

function getStructAttributes(string $fileName): array
{
    $attributes = [];
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    foreach ($lines as $line) {
        $cleanLine = trim($line);
        foreach (getTypes($fileName) as $type) {
            if (0 !== strpos($line, ' ') && startWith($cleanLine, $type) && endWith($cleanLine, ';')) {
                $attributes[] = cleanToken($cleanLine);
            }
        }
    }
    return $attributes;
}

function getUserDefineMetheds(string $fileName, string $dir): DefineMetheds
{
    $paser = new DefineMetheds($fileName, $dir);
    return $paser;
}

function getShutIncludeFilePath(string $fileName): array
{
    $paths = [];
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    foreach ($lines as $line) {
        $cleanLine = trim($line);
        if (startWith($cleanLine, '@')) {
            $importInfo = explode(' ', ltrim($cleanLine, '@'));
            $paths[] = $importInfo[0] . '.h';
        }
    }
    return $paths;
}

function uniqSourceUpperCase(string $fileName): string
{
    $info = pathinfo($fileName);
    $upper = strtoupper($info['filename']);
    $uniqSource = strtoupper(implode('_', explode(DIRECTORY_SEPARATOR, $info['dirname']))) . '_' . $upper;
    return $uniqSource;
}

function uniqSourceLowerCase(string $fileName): string
{
    return strtolower(uniqSourceUpperCase($fileName));
}

function getUserIncludeAndMicro(string $fileName): string
{
    $paths = '';
    $contents = file_get_contents($fileName);
    $lines = explode(PHP_EOL, $contents);
    foreach ($lines as $line) {
        $cleanLine = trim($line);
        if (startWith($cleanLine, '#')) {
            $paths .= $cleanLine;
        }
    }
    return $paths;
}

function process(string $name, string $fileName, string $src, string $dir): string
{
    $file = $name;
    $lower = strtolower($file);
    $upper = strtoupper($file);

    $uniqSource = uniqSourceUpperCase($fileName);
    $uniqSourceLowerCase = uniqSourceLowerCase($fileName);

    $structDefines = getStructAttributes($fileName);
    $stringOfStructAttributes = '';
    $map = getTypesToCTypeMapping($fileName, $src);
    foreach ($structDefines as $defineString) {
        if (!empty($stringOfStructAttributes)) {
            $stringOfStructAttributes .= PHP_EOL;
        }
        $arr = explode(' ', $defineString);
        $arr[0] = $map[$arr[0]];
        $stringOfStructAttributes .= "    " . implode(' ', $arr);
    }
    unset($map);

    $shoultIncludeHeader = '';
    foreach (getShutIncludeFilePath($fileName) as $path) {
        if (!empty($shoultIncludeHeader)) {
            $shoultIncludeHeader .= PHP_EOL;
        }
        $shoultIncludeHeader .= "#include \"$path\"";
    }
    if (!empty($shoultIncludeHeader)) {
        $shoultIncludeHeader .= PHP_EOL;
    }

    $userIncludeHeaderAndMicro = getUserIncludeAndMicro($fileName);

    $userDefineMethods = getUserDefineMetheds($fileName, $src);
    $functionsDefineForHeader = '';
    $functionsDefineForCSource = '';
    foreach ($userDefineMethods->getMethods() as $method) {
        $functionsDefineForHeader .= $method->getHeaderDefineString() . PHP_EOL . PHP_EOL;
        $functionsDefineForCSource .= $method->getCCodeDefineString() . PHP_EOL . PHP_EOL;
    }

    $mainFunction = $userDefineMethods->getEntry()->getCCodeDefineString();
    if (!empty($mainFunction)) {
        $mainFunction .= PHP_EOL;
    }
    $newFunction = $userDefineMethods->getNewFunction()->getCCodeDefineString();
    if (!empty($newFunction)) {
        $newFunction .= PHP_EOL;
    }
    $newFunctionParams = $userDefineMethods->getNewFunction()->getCCodeParmsString();

    $dotH = <<<DOT_H
#ifndef $uniqSource
#define $uniqSource
#include <malloc.h>
#include <stdlib.h>
#include <stdio.h>
$shoultIncludeHeader
struct ${uniqSourceLowerCase}_attributes {
$stringOfStructAttributes
};

struct ${uniqSourceLowerCase}_attributes *${uniqSourceLowerCase}_new($newFunctionParams);

void ${uniqSourceLowerCase}_destory(struct ${uniqSourceLowerCase}_attributes *${uniqSourceLowerCase}_for_distory);

$functionsDefineForHeader
#endif

DOT_H;

    $dotC = <<<DOT_C
#include "$file.h"
$shoultIncludeHeader
$userIncludeHeaderAndMicro
$mainFunction
struct ${uniqSourceLowerCase}_attributes *${uniqSourceLowerCase}_new($newFunctionParams) {
$newFunction
}

void ${uniqSourceLowerCase}_destory(struct ${uniqSourceLowerCase}_attributes *${uniqSourceLowerCase}_for_distory) {
    free(${uniqSourceLowerCase}_for_distory);
}

$functionsDefineForCSource
DOT_C;
    file_put_contents($dir . DIRECTORY_SEPARATOR . $file . '.h', $dotH);
    file_put_contents($dir . DIRECTORY_SEPARATOR . $file . '.c', $dotC);
    return $dir . DIRECTORY_SEPARATOR . $file . '.c';
}

function dirProcess(string $src, string $dst)
{
    $cFiles = [];
    if (!is_dir($dst) && false === mkdir($dst)) {
        return false;
    }
    $d = opendir($src);
    if (false === $d) {
        return false;
    }
    while (!empty($file = readdir($d))) {
        if (in_array($file, ['..', '.'])) {
            continue;
        }
        if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
            $cFiles = array_merge($cFiles, dirProcess($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file));
        }
        if (is_file($src . DIRECTORY_SEPARATOR . $file)) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) != 'txt') {
                continue;
            }
            $cFiles[] = process(pathinfo($file, PATHINFO_FILENAME), $src . DIRECTORY_SEPARATOR . $file, $src, $dst);
        }
    }
    closedir($d);
    return $cFiles;
}

$isHelp = false;
foreach ($_SERVER['argv'] as $param) {
    if (0 === stripos($param, '-h')) {
        $isHelp = true;
    }
    if (0 === stripos($param, '--help')) {
        $isHelp = true;
    }
}

if ($isHelp || empty($_SERVER['argv'][1]) || empty($_SERVER['argv'][2])) {
    echo <<<HELPSTR
My Programming Language - 版本 0.0.1

使用方法：

    $ mpl sourcedir targetdir

示例（ src 是存放源码的文件夹，dst 是 mpl 生成C源码的文件夹）：

    $ mkdir src dst
    $ mpl src dst

可选命令参数：

-h, --help   打印此帮助信息。

HELPSTR;
    exit();
}

$src = rtrim($_SERVER['argv'][1], DIRECTORY_SEPARATOR);
$dst = rtrim($_SERVER['argv'][2], DIRECTORY_SEPARATOR);

if (!is_dir($src) || !is_dir($dst)) {
    echo 'Miss:' . PHP_EOL;
    echo $src . PHP_EOL;
    echo $dst . PHP_EOL;
    exit();
}

$complieFiles = dirProcess($src, $dst);
file_put_contents('output.log', 'gcc -O3 -o main ' . implode(' ', $complieFiles) . PHP_EOL);
