<?php

require_once '../YdownClass.php';

$ydown = new YdownClass('https://www.youtube.com/watch?v=Q9OPOe_OO94');

$vi = $ydown->getVideoInfo();
if($vi ==false){
echo "failed to retrieve info try again\n";
exit;
}

echo 'video info: ';
print_r($vi);

$vi = (object)$vi; //easier to access for me :)
echo 'DownloadLink: ';
echo $ydown->getDownloadLink($vi->id,$vi->videoID,"mp4",$vi->resolution[0]);

//calling this will first retrieve info and download link before downloading
// no argument will download an mp4 at highest resolution possible to current directory
$ydown->download();
