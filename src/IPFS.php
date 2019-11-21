<?php
/*
	This code is licensed under the MIT license.
	See the LICENSE file for more information.
*/

namespace rannmann\PhpIpfsApi;

/**
 * Class IPFS
 * @package rannmann\PhpIpfsApi
 */
class IPFS
{
    /**
     * @var string
     */
    private $gatewayIP;
    /**
     * @var string
     */
    private $gatewayPort;
    /**
     * @var string
     */
    private $gatewayApiPort;

    /**
     * IPFS constructor.
     * @param string $ip
     * @param int $port
     * @param int $apiPort
     */
    function __construct($ip = "localhost", $port = 8080, $apiPort = 5001)
    {
        $this->gatewayIP = $ip;
        $this->gatewayPort = $port;
        $this->gatewayApiPort = $apiPort;
    }

    /**
     * Retrieves the contents of a single hash
     *
     * @param string $hash
     * @return bool|string
     */
    public function cat($hash)
    {
        return $this->curl($this->getIpfsUrl() . "/$hash");
    }

    /**
     * Adds content to IPFS.
     *
     * @param $content
     * @return mixed
     */
    public function add($content)
    {
        $req = $this->curl($this->getApiUrl() . "/add?stream-channels=true", $content);
        if ($req !== false) {
            $req = json_decode($req, true);
        }

        return $req['Hash'];
    }

    /**
     * Returns the node structure of a hash
     *
     * @param string $hash
     * @return mixed False on failure
     */
    public function ls($hash)
    {
        $req = $this->curl($this->getApiUrl() . "/ls/$hash");

        if ($req !== false) {
            $req = json_decode($req, true);
            return $req['Objects'][0]['Links'];
        }

        return false;
    }

    /**
     * @param string $hash
     * @return mixed
     */
    public function size($hash)
    {
        $req = $this->curl($this->getApiUrl() . "/object/stat/$hash");
        if ($req !== false) {
            $req = json_decode($req, true);
            return $req['CumulativeSize'];
        }

        return false;
    }

    /**
     * Pin a hash
     *
     * @param string $hash
     * @return mixed
     */
    public function pinAdd($hash)
    {
        $req = $this->curl($this->getApiUrl() . "/pin/add/$hash");
        if ($req !== false) {
            $req = json_decode($req, true);
        }

        return $req;
    }

    /**
     * Unpin a hash
     *
     * @param string $hash
     * @return mixed
     */
    public function pinRm($hash)
    {
        $req = $this->curl($this->getApiUrl() . "/pin/rm/$hash");
        if ($req !== false) {
            $req = json_decode($req, true);
        }

        return $req;
    }

    /**
     * @return mixed
     */
    public function version()
    {
        $req = $this->curl($this->getApiUrl() . "/version");
        if ($req !== false) {
            $req = json_decode($req, true);
            return $req['Version'];
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function id()
    {
        $req = $this->curl($this->getApiUrl() . "/id");
        if ($req !== false) {
            $req = json_decode($req, true);
            return $req['Version'];
        }
        return $req;
    }

    /**
     * Gets the base url for all API calls, no trailing slash.
     *
     * @return string
     */
    private function getApiUrl()
    {
        return "http://{$this->gatewayIP}:{$this->gatewayPort}/api/v0";
    }

    /**
     * Gets the base url for all IPFS calls, no trailing slash.
     *
     * @return string
     */
    private function getIpfsUrl()
    {
        return "http://{$this->gatewayIP}:{$this->gatewayApiPort}/ipfs";
    }

    /**
     * @param $url
     * @param string $data
     * @return false|string False on failure
     */
    private function curl($url, $data = "")
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

        if ($data != "") {
            $boundary = "a831rwxi1a3gzaorw1w2z49dlsor";
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data; boundary=$boundary"));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "--$boundary\r\nContent-Type: application/octet-stream\r\nContent-Disposition: file; \r\n\r\n" . $data . "\r\n--$boundary\r\n");
        }

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}


