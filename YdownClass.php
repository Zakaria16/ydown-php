<?php

require_once 'util.php';

class YdownRegex
{
    const VIDEO_IDS_PATTERN = '/k_data_vid\s=\s*\\\"([\w\s\S]*)\\\";\svar\sk_data[\s\S]*k__id\s*=\s*\\\"([\w\s\S]*)\\\";\s*var\s*video_service/';
    const VIDEO_TITLE_PATTERN = '/text-left\\\">\s<b>([\s\S]*)<[\W]*b>[\s\S]/';
    const VIDEO_RESOLUTION_PATTERN = '/data-fquality=\\\"([\d]*)/';
    const VIDEO_DOWNLOAD_LINK_PATTERN = '/href=\\\"([\w\s\S]*)\\\"\s*rel/';
}

class ErrorCode
{
    const SUCCESS = 0;
    const RETRIEVE_ERROR = -1;
    const RESOLUTION_ERROR = -2;
    const DIRECTORY_ERROR = -3;
    const CONVERSION_ERROR = -4;
}

class YdownClass
{

    private string $youtubeLink;
    private int $errorCode;

    public function __construct($youtubeLink)
    {
        $this->youtubeLink = $youtubeLink;
        $this->errorCode=ErrorCode::RETRIEVE_ERROR;
    }


    public function getVideoInfo()
    {
        $body = array(
            'url' => $this->youtubeLink,
            'q_auto' => 1,
            'ajax' => 1
        );

        $an_res = curlPost('https://www.y2mate.com/mates/analyze/ajax', [], $body);
        if (empty($an_res)) {
            $this->errorCode = ErrorCode::RETRIEVE_ERROR;
            return false;
        }

        $res = [];
        $rt = preg_match(YdownRegex::VIDEO_IDS_PATTERN, $an_res, $res);

        if (!$rt || empty($res)) {
            $this->errorCode = ErrorCode::RETRIEVE_ERROR;
            return false;
        }
        // vid_id = $res[1], id = $res[2]
        [, $vid_id, $id] = $res;

        $videoInfo = array(
            "id" => $id,
            "videoID" => $vid_id
        );


        $title = [];
        if (preg_match(YdownRegex::VIDEO_TITLE_PATTERN, $an_res, $title)) {
            $title[1] = preg_replace('/[^A-Za-z0-9]+/', '_', $title[1]);
            $videoInfo['title'] = $title[1];
        }

        $res_types = [];
        if (preg_match_all(YdownRegex::VIDEO_RESOLUTION_PATTERN, $an_res, $res_types)) {
            $videoInfo['resolution'] = $res_types[1];
        }

        $this->errorCode = ErrorCode::SUCCESS;
        return $videoInfo;
    }

    public function getDownloadLink($id, $videoID, $type, $resolution)
    {
        $vid_array = array(
            'type' => 'youtube',
            '_id' => $id,
            'v_id' => $videoID,
            'ajax' => '1',
            'token' => '',
            'ftype' => $type,
            'fquality' => $resolution
        );

        $res = curlPost('https://www.y2mate.com/mates/convert', [], $vid_array);
        if(empty($res)){
            $this->errorCode = ErrorCode::CONVERSION_ERROR;
            return false;
        }
        $url = null;
        $link_match = [];
        if (preg_match(YdownRegex::VIDEO_DOWNLOAD_LINK_PATTERN, $res, $link_match)) {
            // Initialize a file URL to the variable
            $url = str_replace('\\', '', $link_match[1]);
        }else{
            $this->errorCode =ErrorCode::CONVERSION_ERROR;
            return false;
        }
        return $url;
    }

    /**
     * download the video
     * @param string $dir where to save the video default is current directory
     * @param string $mediaType media type default is mp4
     * @param string $resolution resolution to use default is the highest resolution available
     * @return bool
     */
    public function download(string $dir = '.', string $mediaType = 'mp4', $resolution = 0): ?bool
    {
        $vidInfo = $this->getVideoInfo();
        if ($vidInfo == false) {
            return false;
        }
        $vidInfo = (object)$vidInfo;
        if ($resolution == 0) {
            $resolution = $mediaType == 'mp3' ? 128 : $vidInfo->resolution[0];
        } else {
            if (!in_array($resolution, $vidInfo->resolution)) {
                $this->errorCode = ErrorCode::RESOLUTION_ERROR;
                return false;
            }
        }

        $link = $this->getDownloadLink($vidInfo->id, $vidInfo->videoID, $mediaType, $resolution);
        if($link==false){
            $this->errorCode = ErrorCode::CONVERSION_ERROR;
            return false;
        }

        $save_name = "$vidInfo->title.$mediaType";
        if(!is_dir($dir)){
            $this->errorCode = ErrorCode::DIRECTORY_ERROR;
            return false;
        }

        if ($dir[-1] !== '\\' || $dir[-1] !== '/') {
            $dir .= '\\';
        }
        $savePath = $dir . trim(strip_tags($save_name));
        return $this->fileDownload($link, $savePath);
    }

    private function fileDownload($url, $dir): bool
    {
        //download progress
        $ctx = stream_context_create();
        stream_context_set_params($ctx, array("notification" => [$this, "stream_notification_callback"]));

        if (empty($dir)) {
            $dir = 'file_' . time() . 'media';
        }
        
        echo "saving file as: $dir\n";

        $file_content = file_get_contents($url, false, $ctx);
        if (file_put_contents($dir, $file_content)) {
            echo "\nFile download successful\n";
            return true;
        }
        echo "File downloading failed.\n";
        $err = error_get_last();
        echo "\nError..\n", $err["message"], "\n";
        return false;
    }


    private
    function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max)
    {
        static $filesize = null;

        switch ($notification_code) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                echo "Being redirected to: ", $message, "\n";
                break;

            case STREAM_NOTIFY_CONNECT:
                echo "Connected...\n";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $filesize = $bytes_max;
                echo "Filesize: ", $filesize, "\n";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                echo "Mime-type: ", $message, "\n";
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ($bytes_transferred > 0) {
                    if (!isset($filesize)) {
                        printf("\rUnknown filesize.. %2d kb done..", $bytes_transferred / 1024);
                    } else {
                        $length = (int)(($bytes_transferred / $filesize) * 100);
                        printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat("=", $length) . ">", $length, ($bytes_transferred / 1024), $filesize / 1024);
                    }
                }
                break;
        }
    }


    /**
     * Get the recent error code that occurred on the last method called
     * @return int the error code
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }


}
