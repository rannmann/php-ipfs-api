<?php
/*
    This code is licensed under the MIT license.
    See the LICENSE file for more information.
*/

namespace rannmann\PhpIpfsApi;

use Exception;

/**
 * Class IPFS
 *
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
     * @var resource
     */
    private $curl;

    protected $curlTimeout = 5;

    const ERROR_BAD_PROGRAMMER = 1;
    const ERROR_EMPTY_RESPONSE = 2;

    /**
     * IPFS constructor.
     *
     * @param string $host
     * @param int    $port
     * @param int    $apiPort
     */
    function __construct($host = "localhost", $port = 8080, $apiPort = 5001)
    {
        $this->gatewayIP = $host;
        $this->gatewayPort = $port;
        $this->gatewayApiPort = $apiPort;
    }

    /**
     * Retrieves the contents of a single hash
     *
     * @param  string $hash
     * @return string
     * @throws Exception
     */
    public function cat($hash)
    {
        return $this->curl($this->getIpfsUrl() . "/$hash");
    }

    /**
     * Adds content to IPFS.
     *
     * @param  $content
     * @return mixed
     * @throws Exception
     */
    public function add($content)
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/add?stream-channels=true", $content)
        );
        if ($response) {
            $response = $response['Hash'];
        }
        return $response;
    }

    /**
     * @param  string $filePath
     * @param  array  $params
     * @return mixed|null
     * @throws Exception
     */
    public function addFromPath(string $filePath, array $params = [])
    {
        $response = $this->safeDecode(
            $this->curlFile($this->getApiUrl() . "/add", $filePath, $params)
        );
        if ($response) {
            $response = $response['Hash'];
        }
        return $response;
    }

    /**
     * Returns the node structure of a hash
     *
     * @param  string $hash
     * @return mixed
     * @throws Exception
     */
    public function ls($hash)
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/ls/$hash")
        );

        if ($response) {
            $response = $response['Objects'][0]['Links'];
        }
        return $response;
    }

    /**
     * @param  string $hash
     * @return mixed
     * @throws Exception
     */
    public function size($hash)
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/object/stat/$hash")
        );

        if ($response) {
            $response = $response['CumulativeSize'];
        }
        return $response;
    }

    /**
     * Pin a hash
     *
     * @param  string $hash
     * @return mixed
     * @throws Exception
     */
    public function pinAdd($hash): ?array
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/pin/add/$hash")
        );

        return $response;
    }

    /**
     * Unpin a hash
     *
     * @param  string $hash
     * @return array|null
     * @throws Exception
     */
    public function pinRm($hash): ?array
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/pin/rm/$hash")
        );
        return $response;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function version()
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/version")
        );

        if ($response) {
            $response = $response['Version'];
        }
        return $response;
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function id(): ?array
    {
        $response = $this->safeDecode(
            $this->curl($this->getApiUrl() . "/id")
        );
        return $response;
    }

    /**
     * Gets the base url for all API calls, no trailing slash.
     *
     * @return string
     */
    private function getApiUrl(): string
    {
        return "http://{$this->gatewayIP}:{$this->gatewayPort}/api/v0";
    }

    /**
     * Gets the base url for all IPFS calls, no trailing slash.
     *
     * @return string
     */
    private function getIpfsUrl(): string
    {
        return "http://{$this->gatewayIP}:{$this->gatewayApiPort}/ipfs";
    }

    /**
     * @param  $input
     * @return array|null
     */
    private function safeDecode($input): ?array
    {
        if ($input === null || $input === false) {
            return null;
        }
        return json_decode($input, true);
    }

    private function resetCurl()
    {
        if (empty($this->curl)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getCurlTimeout());
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $this->curl = $ch;
        }
        // Shared resets
        curl_setopt($this->curl, CURLOPT_POST, 0); // We'll set this to 1 if we actually post data.
    }

    /**
     * @param  string      $url
     * @param  string|null $data
     * @param  string|null $filePath
     * @param  array       $params   GET parameters
     * @return string
     * @throws Exception
     */
    private function executeCurl(string $url, ?string $data = null, ?string $filePath = null, $params = []): string
    {
        if ($data && $filePath) {
            throw new Exception(
                "Cannot send both POST data and a file at the same time",
                self::ERROR_BAD_PROGRAMMER
            );
        }

        $queryString = $params ? '?' . http_build_query($params) : '';
        $url .= $queryString;
        curl_setopt($this->curl, CURLOPT_URL, $url);

        if ($data) {
            // Handle raw data, such as strings
            $boundary = "a831rwxi1a3gzaorw1w2z49dlsor";
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data; boundary=$boundary"));
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, "--$boundary\r\nContent-Type: application/octet-stream\r\nContent-Disposition: file; \r\n\r\n" . $data . "\r\n--$boundary\r\n");
        } elseif ($filePath) {
            // Handle file paths instead
            curl_setopt($this->curl, CURLOPT_POST, 1);
            $cfile = curl_file_create($filePath, 'application/octet-stream', basename($filePath));
            $postFields = ['file' => $cfile];
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);
        }

        // See what IPFS says
        $output = curl_exec($this->curl);

        // Store this for later
        $responseCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        // Free up resources
        curl_close($this->curl);
        $this->curl = null;

        // Handle any 400s or 500s
        if ($responseCode >= 400 && $responseCode < 600) {
            $data = @json_decode($output, true);
            if (!$data AND json_last_error() != JSON_ERROR_NONE) {
                throw new Exception(
                    "IPFS returned response code $responseCode: " . substr($output, 0, 200),
                    $responseCode
                );
            }
            if (is_array($data)) {
                if (isset($data['Code']) && isset($data['Message'])) {
                    throw new Exception("IPFS Error {$data['Code']}: {$data['Message']}", $responseCode);
                }
            }
        }

        if ($output === false) {
            // If we get no response and no 400-500 error, something really weird happened.
            throw new Exception("IPFS Error: No Response", self::ERROR_EMPTY_RESPONSE);
        }

        return $output;
    }

    /**
     * @param  string $url
     * @param  string $data
     * @param  array  $params GET parameters
     * @return string
     * @throws Exception
     */
    private function curl(string $url, string $data = "", array $params = []): string
    {
        $this->resetCurl();
        $output = $this->executeCurl($url, $data, null, $params);

        return $output;
    }

    /**
     * @param  string $url
     * @param  string $filePath or Directory path
     * @param  array  $params   GET parameters
     * @return string
     * @throws Exception
     */
    private function curlFile(string $url, string $filePath, array $params = []): string
    {
        $this->resetCurl();
        $output = $this->executeCurl($url, null, $filePath, $params);

        return $output;
    }

    /**
     * @return int
     */
    public function getCurlTimeout(): int
    {
        return $this->curlTimeout;
    }

    /**
     * @param int $curlTimeout
     */
    public function setCurlTimeout(int $curlTimeout): void
    {
        $this->curlTimeout = $curlTimeout;
    }
}


