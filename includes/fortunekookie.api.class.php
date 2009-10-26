<?php
/*
* Copyright (c) <2009> FortuneKookie.com
*
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use,
* copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the
* Software is furnished to do so, subject to the following
* conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
* OTHER DEALINGS IN THE SOFTWARE.
*/
 
/**
* FK_libphp is a PHP implementation of the FortuneKookie API, allowing you
* to take advantage of it from within your PHP applications.
*
* @author FortuneKookie Crew <support@fortunekookie.com>
* @package FK_libphp
*/
 
/**
* FortuneKookie API abstract class
* @package FK_libphp
*/
abstract class fk_Base {
 
  /**
   * the last HTTP status code returned
   * @access private
   * @var integer
   */
  private $http_status;
  
  /**
   * the whole URL of the last API call
   * @access private
   * @var string
   */
  private $last_api_call;
  
  /**
   * the application calling the API
   * @access private
   * @var string
   */
  private $application_source;

  
  /**
   * Sends a new direct message to the specified user from the authenticating user.
   * @param string $nbr The number of fortunes requested (between 1 and 10)
   * @param string $code Security code (request one from us at support@fortunekookie.com)
   * @param string $format Return format
   * @return string
   */
  function getFortunes($nbr, $code, $format = 'xml') {
    $options = array(
      'nbr' => urlencode($nbr),
      'code' => urlencode($code)
    );
    $api_call = $this->buildRequest('fortune_server.php', $format, $options);
    return $this->APICall($api_call);
  }
  

  
  /**
   * Builds an API URL out of a method, format, and option list
   * @access private
   * @param $method string FortuneKookie API method
   * @param $fmt string Return format
   * @param $options array API method options
   * @return string
   */
  private function buildRequest($method, $fmt, $options = array()) {
    $request = sprintf('http://api.fortunekookie.com/%s', $method);
    /* Add application source to the options */
    if ($this->application_source) {
      $options['source'] = $this->application_source;
    }
    /* Convert all options to GET params */
    if (count($options) > 0) {
      $keyvals = array();
      foreach($options as $option => $value) {
        array_push($keyvals, sprintf('%s=%s', $option, $value));
      }
      $request .= '?' . implode($keyvals, '&');
    }
    return $request;
  }
  
  /**
   * Returns the last HTTP status code
   * @return integer
   */
  function lastStatusCode() {
    return $this->http_status;
  }
  
  /**
   * Returns the URL of the last API call
   * @return string
   */
  function lastAPICall() {
    return $this->last_api_call;
  }
}
 
/**
* Access to the FortuneKookie API through HTTP auth
* @package FK_libphp
*/
class FortuneKookie extends fk_Base {
 
  /**
   * the FortuneKookie credentials in HTTP format, username:password
   * @access private
   * @var string
   */
  var $credentials;
  
  /**
   * Fills in the credentials {@link $credentials} and the application source {@link $application_source}.
   * @param string $username FortuneKookie username
   * @param string $password FortuneKookie password
   * @param $source string Optional. Name of the application using the API
   */
  function __construct($username, $password, $source = null) {
    $this->credentials = sprintf("%s:%s", $username, $password);
    $this->application_source = $source;
  }
  
  /**
   * Executes an API call
   * @param string $api_url Full URL of the API method
   * @param boolean $require_credentials Whether or not credentials are required
   * @param boolean $http_post Whether or not to use HTTP POST
   * @return string
   */
  protected function APICall($api_url, $require_credentials = false, $http_post = false) {
	//for debugging purposes:
	//echo $api_url . "\n";
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $api_url);
    if ($require_credentials) {
      curl_setopt($curl_handle, CURLOPT_USERPWD, $this->credentials);
    }
    if ($http_post) {
      curl_setopt($curl_handle, CURLOPT_POST, true);
    }
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
    $fk_data = curl_exec($curl_handle);
    $this->http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    $this->last_api_call = $api_url;
    curl_close($curl_handle);
    return $fk_data;
  }
 
}
 
?>