<?php

/**
 * Simple scraper for http://adsl.tci.ir
 * By NimaH79
 * NimaH79.ir
 */

if(php_sapi_name() == 'cli') {
    define('NEWLINE', PHP_EOL);
}
else {
    define('NEWLINE', '<br>');
}

class AdslChecker {

    private static $website_url = 'http://adsl.tci.ir';
    private static $cookie_file = __DIR__.'/adsl_cookie.txt';
    private static $op_file = __DIR__.'/op.txt';

    public static function getCaptcha() {
        $ch = curl_init(self::$website_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
        $op = curl_exec($ch);
        preg_match('/op=(.*?)"/', $op, $op);
        $op = $op[1];
        file_put_contents(self::$op_file, $op);
        curl_setopt($ch, CURLOPT_URL, 'http://adsl.tci.ir/captcha_code_file.php');
        $captcha = curl_exec($ch);
        curl_close($ch);
        return $captcha;
    }
    
    public static function loginAndGetInfo($username, $password, $captcha) {
        $ch = curl_init(self::$website_url.'/user.php?op='.file_get_contents(self::$op_file));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('hidlogin' => '1', 'username' => $username, 'password' => $password, 'seccode' => $captcha, 'submit' => 'ورود به سامانه'));
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
        $response = curl_exec($ch);
        curl_close($ch);
        if($error = self::checkError($response)) {
            return $error;
        }
        return self::parseInfo($response);
    }
    
    public static function getInfo() {
        $ch = curl_init(self::$website_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
        $response = curl_exec($ch);
        curl_close($ch);
        if($error = self::checkError($response)) {
            return $error;
        }
        return self::parseInfo($response);
    }
    
    public static function parseInfo($text) {
        if(preg_match_all('/<td class=[\'\"]lable2.*?>(.*?)<\/td>/s', $text, $response)) {
            $result = '';
            for ($i = 0; $i < count($response[1]); $i = $i + 2) {
                if(empty($response[1][$i]) || $response[1][$i] == '&nbsp;' || preg_match('/(تست سرعت|خرید ترافیک|تمدید سرویس|تغییر سرویس|جهت مشاهده|اعتبار پنل)/su', $response[1][$i])) {
                    continue;
                }
                $response[1][$i] = trim($response[1][$i]);
                $response[1][$i] = str_replace(' :', ':', $response[1][$i]);
                $response[1][$i + 1] = trim($response[1][$i + 1]);
                $response[1][$i + 1] = str_replace(' :', ':', $response[1][$i + 1]);
                $result .= $response[1][$i];
                $response[1][$i + 1] = preg_replace('/([\x{0600}-\x{06FF}\s])([0-9]+)/u', "$1 $2", $response[1][$i + 1]);
                if(preg_match('/title=\'(.*?)\'/', $response[1][$i + 1])) {
                    $result .= ' ';
                    preg_match('/title=\'(.*?)\'/', $response[1][$i + 1], $title);
                    $result .= $title[1].NEWLINE;
                }
                elseif(preg_match_all('/>(.*?\S.*?)</s', $response[1][$i + 1], $parts)) {
                    if(count($parts[1]) > 1) {
                        $result .= NEWLINE;
                    }
                    else {
                        $result .= ' ';
                    }
                    $response[1][$i + 1] = '';
                    foreach ($parts[1] as $part) {
                        $response[1][$i + 1] .= $part.NEWLINE;
                    }
                    $result .= $response[1][$i + 1].NEWLINE;
                }
                else {
                    $result .= ' ';
                    $result .= $response[1][$i + 1].NEWLINE;
                }
            }
            $result = str_replace(array('ي', 'ك'), array('ی', 'ک'), $result);
            return $result;
        }
        return false;
    }
    
    public static function checkError($response) {
        if(preg_match('/<td class=error>(.*?)<\/td>/', $response, $error)) {
            return $error[1];
        }
        return false;
    }
    
    public static function logout() {
        if(is_file(self::$cookie_file)) {
            unlink(self::$cookie_file);
        }
    }

}
