<?php

/**
 * Use to make http request
 * @param $url string the url to send the request
 * @param $headers array of string. the headers for the http request
 * @param $body array|object associative array of the http body
 * @param string $type request type POST or GET, default is POST
 * @param bool $returnResult , the return type is boolean when false or the result of the request when true
 * @return mixed|null return array of request result when $returnResult=true or boolean when $returnResult=false
 */
function curlPost($url, $headers, $body, $type = "POST", $returnResult = true)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => $returnResult,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $type,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    //$res_info = curl_getinfo($curl);
    curl_close($curl);
    //$http_code = $res_info['http_code'];

    if ($err) {
        // echo 'Error #:' . $err . '<br />';
        return null;
    }
    return $response;
}
