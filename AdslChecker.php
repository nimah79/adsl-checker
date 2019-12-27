<?php

/**
 * Simple scraper for https://adsl.tci.ir
 * By NimaH79
 * NimaH79.ir.
 */
if (php_sapi_name() == 'cli') {
    define('NEWLINE', PHP_EOL);
} else {
    define('NEWLINE', '<br>');
}

libxml_use_internal_errors(true);

class AdslChecker
{
    private static $website_url = 'https://adsl.tci.ir';
    private static $cookie_file = __DIR__.'/adsl_cookie.txt';
    private static $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7';

    public static function getCaptcha()
    {
        $ch = curl_init(self::$website_url.'/captcha_code_file.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => self::$cookie_file,
            CURLOPT_COOKIEFILE => self::$cookie_file,
            CURLOPT_USERAGENT => self::$useragent
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public static function loginAndGetInfo($username, $password, $captcha)
    {
        $ch = curl_init(self::$website_url.'/panel/login/'.time());
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS => http_build_query(['redirect' => '', 'username' => $username, 'password' => $password, 'captcha' => $captcha, 'LoginFromWeb' => '']),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_COOKIEJAR => self::$cookie_file,
            CURLOPT_COOKIEFILE => self::$cookie_file,
            CURLOPT_USERAGENT => self::$useragent
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($error = self::checkError($response)) {
            return $error;
        }

        return self::parseInfo($response);

        return $response;
    }

    public static function getInfo()
    {
        $ch = curl_init(self::$website_url.'/panel/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => self::$cookie_file,
            CURLOPT_COOKIEFILE => self::$cookie_file,
            CURLOPT_USERAGENT => self::$useragent
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($error = self::checkError($response)) {
            return $error;
        }

        return self::parseInfo($response);
    }

    public static function logout()
    {
        if (is_file(self::$cookie_file)) {
            unlink(self::$cookie_file);
        }
    }

    private static function parseInfo($text)
    {
        if (strpos($text, 'کد امنیتی وارد شده نادرست است.') !== false) {
            return '<p style="color:red">کد امنیتی وارد شده نادرست است.</p>';
        }
        if (strpos($text, 'نام کاربری با گذرواژه همخوانی ندارد.') !== false) {
            return '<p style="color:red">نام کاربری با گذرواژه همخوانی ندارد.</p>';
        }
        if (preg_match('/کد مشترک: [0-9]+/', $text, $client_code)) {
            $result = '<p style="color:green">ورود با موفقیت انجام شد.</p>';
            $result .= NEWLINE;
            $result .= $client_code[0];
            $result .= NEWLINE;
            preg_match('/شماره تلفن: [0-9]+/', $text, $phone_number);
            $result .= $phone_number[0];
            $result .= NEWLINE;
            $info = self::xpathQuery($text, '//div/div/div/ul/li');
            foreach ($info as $item) {
                $result .= $item.NEWLINE;
            }
            $remained = self::xpathQuery($text, '//div[@class="percent"]/span');
            $result .= $remained[0].' از '.$remained[1].NEWLINE;
            $result .= $remained[2].' از '.$remained[3].NEWLINE;
            $result = str_replace(['ي', 'ك'], ['ی', 'ک'], $result);

            $active_service = self::xpathQuery($text, '(//h5)[1]');
            $result .= 'سرویس فعال شما: '.$active_service[0].NEWLINE;

            return $result;
        }

        return false;
    }

    private static function checkError($response)
    {
        if (preg_match('/<td class=error>(.*?)<\/td>/', $response, $error)) {
            return $error[1];
        }

        return false;
    }

    private static function xpathQuery($html, $query)
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $xpath = new DomXPath($dom);
        $results = $xpath->query($query);
        $results_array = [];
        foreach ($results as $node) {
            $results_array[] = $node->nodeValue;
        }

        return $results_array;
    }
}
