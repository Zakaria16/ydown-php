<?php

require_once 'YdownClass.php';

function getArg($opt, $sKey, $lKey, $default = null)
{
    if (isset($opt[$sKey]) || isset($opt[$lKey])) {
        return $opt[$sKey] ?? $opt[$lKey];
    }
    return $default;
}

$scriptName = $argv[0];
function usage()
{
    global $scriptName;
    echo "Usage:
    php $scriptName [OPTIONS] youtube_link
    OPTION:
        -i, --info only display info no download
        -d, --dir directory to save video
        -t, --type media type eg. mp4 or mp3
        -r, --res resolution eg. 1080
        -h, --help to show this help
Examples:
    php $scriptName https://www.youtube.com/watch?v=Q9OPOe_OO94  
    php $scriptName -d d:\\videos -t mp4 -r 1080 https://www.youtube.com/watch?v=Q9OPOe_OO94\n";
}

$type = 'mp4';
$resolution = '1080';
$dir = '.';
$onlyInfo = false;
$lastInd = 0;
$opt = getopt('r:d:t:ih', ['res:', 'dir:', 'type:', 'info', 'help'], $lastInd);

if ($argc < 2 || getArg($opt, 'h', 'help') !== null) {
    usage();
    exit;
}

if (getArg($opt, 'i', 'info') !== null) {
    $onlyInfo = true;
} else {
    $type = getArg($opt, 't', 'type', 'mp4');
    if ($type != 'mp4' && $type != 'mp3') exit('only mp4 and mp3 type are supported');

    $resolution = getArg($opt, 'r', 'res', 0);
    if (!preg_match('/[0-9]+/', $resolution)) exit("$resolution is invalid resolution integer required");

    $dir = getArg($opt, 'd', 'dir', '.');
    if (!is_dir($dir)) exit("directory: '$dir' is invalid ");
}
if ($lastInd === $argc) {
    exit('youtube link is required');
}

$link = $argv[$lastInd];
if (null === $link) {
    exit('youtube link is required');
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




