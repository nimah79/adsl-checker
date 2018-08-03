<?php

/**
 * Simple scraper for http://adsl.tci.ir
 * By NimaH79
 * NimaH79.ir
 */

function getCaptcha() {
    $ch = curl_init('http://adsl.tci.ir');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'adsl_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'adsl_cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
    $op = curl_exec($ch);
    preg_match('/op=(.*?)"/', $op, $op);
    $op = $op[1];
    file_put_contents('op.txt', $op);
    curl_setopt($ch, CURLOPT_URL, 'http://adsl.tci.ir/captcha_code_file.php');
    $captcha = curl_exec($ch);
    curl_close($ch);
    return $captcha;
}

function loginAndGetInfo($username, $password, $captcha) {
    $ch = curl_init('http://adsl.tci.ir/user.php?op='.file_get_contents('op.txt'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('hidlogin' => '1', 'username' => $username, 'password' => $password, 'seccode' => $captcha, 'submit' => 'ورود به سامانه'));
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'adsl_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'adsl_cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
    $resp = curl_exec($ch);
    curl_close($ch);
    return parseInfo($resp);
}

function getInfo() {
    $ch = curl_init('http://adsl.tci.ir/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'adsl_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'adsl_cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
    $resp = curl_exec($ch);
    curl_close($ch);
    return parseInfo($resp);
}

function parseInfo($text) {
    echo $text;
    if(preg_match_all('/<td class=(\'|\")lable2(\'|\") ?(colspan=\"3\")?>(.*?)<\/td>/s', $text, $resp)) {
        $result = '';
        for ($i = 0; $i < count($resp[4]); $i = $i + 2) {
            if(empty($resp[4][$i])) {
                continue;
            }
            $resp[4][$i] = trim($resp[4][$i]);
            $resp[4][$i] = str_replace(' :', ':', $resp[4][$i]);
            $resp[4][$i + 1] = trim($resp[4][$i + 1]);
            $resp[4][$i + 1] = str_replace(' :', ':', $resp[4][$i + 1]);
            $result .= $resp[4][$i];
            $resp[4][$i + 1] = preg_replace('/([\x{0600}-\x{06FF}\s])([0-9]+)/u', "$1 $2", $resp[4][$i + 1]);
            if(preg_match('/title=\'(.*?)\'/', $resp[4][$i + 1])) {
                $result .= ' ';
                preg_match('/title=\'(.*?)\'/', $resp[4][$i + 1], $title);
                $result .= $title[1]."\n";
            }
            elseif(preg_match_all('/>(.*?\S.*?)</s', $resp[4][$i + 1], $parts)) {
                if(count($parts[1]) > 1) {
                    $result .= "\n";
                }
                else {
                    $result .= ' ';
                }
                $resp[4][$i + 1] = '';
                foreach ($parts[1] as $part) {
                    $resp[4][$i + 1] .= $part."\n";
                }
                $result .= $resp[4][$i + 1]."\n";
            }
            else {
                $result .= ' ';
                $result .= $resp[4][$i + 1]."\n";
            }
        }
        $result = str_replace(array('ي', 'ك'), array('ی', 'ک'), $result);
        return $result;
    }
    return false;
}

file_put_contents('captcha.jpg', getCaptcha());
echo loginAndGetInfo('YOUR_USERNAME', 'YOUR_PASSWORD', 'CAPTCHA_CODE');
echo getInfo('YOUR_USERNAME', 'YOUR_PASSWORD', 'CAPTCHA_CODE');