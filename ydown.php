<?php

require_once 'YdownClass.php';

function getArg($opt, $sKey, $lKey, $default = null)
{
    if (isset($opt[$sKey]) || isset($opt[$lKey])) {
        return $opt[$sKey] ?? $opt[$lKey];
    }
    return $default;
}

$type = 'mp4';
$resolution = '1080';
$dir = '.';
$onlyInfo = false;
$lastInd = 0;
$opt = getopt('r:d:t:i', ['res:', 'dir:', 'type:', 'info'], $lastInd);
$link = null;
if ($opt !== false) {
    $link = $argv[$lastInd];

    if (isset($opt['i']) || isset($opt['info'])) {
        $onlyInfo = true;
    } else {
        $type = getArg($opt, 't', 'type', 'mp4');
        // print_r($type);
        $resolution = getArg($opt, 'r', 'res', 0);
        // print_r($resolution);
        $dir = getArg($opt, 'd', 'dir', '.');
        // print_r($dir);
    }

}

if (null === $link) {
    die('link is required');
}

$ydown = new YdownClass($link);

if ($onlyInfo) {
    echo "Retrieving video info\n";
    $vidInfo = $ydown->getVideoInfo();
    if ($vidInfo == false) {
        $errorCode = $ydown->getErrorCode();
        echo "cant retrieve video info code=$errorCode\n";
        exit;
    }
    echo "vid info: ";
    print_r($vidInfo);
    exit;
}
echo "Downloading video...\n";

if ($ydown->download($dir, $type, $resolution)) {
    echo "done\n";
} else {
    $errorCode = $ydown->getErrorCode();
    echo "error occurred code=$errorCode\n";
}




