<?php

namespace Synaq\CurlBundle\Curl;

use Synaq\CurlBundle\Exception\CurlException;

class Wrapper
{
    /**
     * The user agent to send along with requests
     *
     * @var string $userAgent
     **/
    private $userAgent;

    /**
     * The file to read and write cookies to for requests
     *
     * @var string $cookieFile
     **/
    public $cookieFile;

    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean $followRedirects
     **/
    public $followRedirects = true;

    /**
     * The referrer header to send along with requests
     *
     * @var string $referrer
     **/
    public $referrer;

    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array $options
     **/
    public $options;

    /**
     * An associative array of headers to send along with requests
     *
     * @var array
     **/
    public $headers;

    /**
     * Stores an error string for the last request if one occurred
     *
     * @var string $error
     **/
    private $error = '';

    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource $request
     **/
    private $request;


    public function __construct($userAgent = null, $cookieFile = false, $followRedirects = false, $referrer = false, $options = array(), $headers = array())
    {
        if (is_null($userAgent)) {
            $this->userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)';
        }
        $this->cookieFile = $cookieFile;
        $this->followRedirects = $followRedirects;
        $this->referrer = $referrer;
        $this->options = $options;
        $this->headers = $headers;
    }

    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
     **/
    function getError()
    {
        return $this->error;
    }

    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array $params
     * @internal param array|string $vars
     * @return Response object
     */
    public function delete($url, $params = array())
    {
        return $this->request('DELETE', $url, $params);
    }

    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array $params
     * @internal param array|string $vars
     * @return Response
     */
    public function get($url, $params = array())
    {
        if (!empty($params)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($params)) ? $params : http_build_query($params, '', '&');
        }

        return $this->request('GET', $url);
    }

    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array $params
     * @internal param array|string $vars
     * @return Response
     */
    public function head($url, $params = array())
    {
        return $this->request('HEAD', $url, $params);
    }

    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return Response|boolean
     */
    public function post($url, $params = array(), $headers = array())
    {
        return $this->request('POST', $url, $params, $headers);
    }

    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array $params
     * @internal param array|string $vars
     * @return Response|boolean
     */
    public function put($url, $params = array())
    {
        return $this->request('PUT', $url, $params);
    }

    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @throws \Exception
     * @return Response|boolean
     */
    public function request($method, $url, $params = array(), $headers = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params, '', '&');
        }

        $this->error = '';
        $this->request = curl_init();


        $this->setRequestMethod($method);
        $this->setRequestOptions($url, $params);
        $this->setRequestHeaders($headers);

        $rawResponse = curl_exec($this->request);

        if ($rawResponse) {
            $response = new Response($rawResponse);
        } else {

            throw new CurlException($this->error = curl_errno($this->request).' - '.curl_error($this->request));
        }

        curl_close($this->request);

        return $response;

    }

    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
     **/
    private function setRequestMethod($method)
    {
        switch($method) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
     **/
    private function setRequestOptions($url, $vars)
    {
        curl_setopt($this->request, CURLOPT_URL, $url);
        if (!empty($vars)) {
            curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
        }

        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, true);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->userAgent);
        if ($this->cookieFile) {
            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookieFile);
        }
        if ($this->followRedirects) {
            curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        }
        if ($this->referrer) {
            curl_setopt($this->request, CURLOPT_REFERER, $this->referrer);
        }

        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }

    /**
     * Formats and adds custom headers to the current request
     *
     * @param array $headers
     * @return void
     * @access protected
     */
    private function setRequestHeaders($headers = array()) {
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }
}