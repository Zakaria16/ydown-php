# Youtube Video Downloader

Retrieves and download a given YouTube video.
It uses [y2mate](http://y2mate.com) to process the youtube video

## Download a video
use the `php ydown.php [OPTIONS] <youtube_link>`
options available:

    -i, --info only display info no download
    -d, --dir directory to save video
    -t, --type media type eg. mp4 or mp3
    -r, --res resolution eg. 1080


Example:

To download the video at the highest resolution available to the current directory
```shell
php ydown.php https://www.youtube.com/watch?v=Q9OPOe_OO94
```

```shell
php ydown.php -d f:\music -t mp4 -r 480  https://www.youtube.com/watch?v=Q9OPOe_OO94
```

## Using in your code
To use this in your code include the `YdownClass.php`:

- To get video information like the title and the resolutions available use `getVideoInfo()`

- You can also get the video download link after retrieving the video info `getDownloadLink($id, $videoID, $type, $resolution)`
  the `getVideoInfo()` returns values contains `$id` and `videoID`

```php
require_once 'YdownClass.php'

$ydown = new YdownClass('https://www.youtube.com/watch?v=Q9OPOe_OO94');
$videoInfo = $ydown->getVideoInfo();
if($videoInfo!=false){
 print_r($videoInfo);
 //retrieve download link
 $ydown->getDownloadLink($videoInfo['id'],$videoInfo['videoID'],'mp4',1080);
}else{
 $errorCode = $ydown->getErrorCode();
 echo "something bad happened code=".$errorCode;
}
```

- To download the video use  `download($directory,$type,$resolution);` or to download to the current directory at the highest
resolution available use `download()` with no argument.

```php
require_once 'YdownClass.php'

$ydown = new YdownClass('');
if($ydown->download('d:\video','mp4',720)){
    echo 'done';
}
else{
    $errorCode = $ydown->getErrorCode();
    echo "failed code=".$errorCode;
}

```


