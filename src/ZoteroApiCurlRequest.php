<?php
namespace Adibaba\ZoteroApiClient;

/**
 * Requests the Zotero API using cURL
 *
 * @see https://www.zotero.org/support/dev/web_api/v3/start
 * @see http://php.net/manual/en/book.curl.php
 * @see https://curl.haxx.se/
 *
 * @author Adrian Wilke
 */
class ZoteroApiCurlRequest
{

    /**
     * The base URL for all API requests
     *
     * @var string
     */
    const BASE_URL = 'https://api.zotero.org';

    /**
     * API version 3 [...] is currently the default and recommended version
     *
     * @var integer
     */
    const ZOTERO_API_VERSION = 3;

    /**
     * Zotero API key for authentication
     *
     * @var string
     */
    private $zoteroApiKey;

    /**
     * cURL handle
     *
     * @var resource cURL
     */
    private $curlHandle;

    /**
     * Latest response header
     *
     * @var string
     */
    private $responseHeader;

    /**
     * Sets Zotero API key.
     * End users can create API keys via their Zotero account settings.
     * This is not required for read access to public libraries.
     *
     * @see https://www.zotero.org/settings/keys
     *
     * @param string $zoteroApiKey
     *            API key specified in Zotero account settings
     *            
     * @return \Adibaba\ZoteroApiClient\ZoteroApiCurlRequest
     */
    public function setZoteroApiKey($zoteroApiKey)
    {
        $this->zoteroApiKey = $zoteroApiKey;
        return $this;
    }

    /**
     * Initializes Zotero API request using cURL
     *
     * @see https://www.zotero.org/support/dev/web_api/v3/basics
     *
     * @param string $uri
     *            URI like specified in documentation
     *            
     * @throws \Exception If URI does not start with slash
     *        
     * @return \Adibaba\ZoteroApiClient\ZoteroApiCurlRequest
     */
    public function initialize($uri)
    {
        // Close handle, if still open (e.g. error handling on failures)
        if (is_resource($this->curlHandle)) {
            curl_close($this->curlHandle);
        }
        
        // URI has to begin with slash.
        // Just checking to avoid careless mistakes.
        if (substr($uri, 0, 1) !== '/') {
            throw new \Exception('URI not valid (no slash)');
        }
        
        // Initialize cURL and reset prior response header
        $this->curlHandle = curl_init();
        $this->responseHeader = '';
        
        // Set the URL to fetch
        curl_setopt($this->curlHandle, CURLOPT_URL, self::BASE_URL . $uri);
        
        // An array of HTTP header fields specifying API version and authentication data
        $headers = array(
            'Zotero-API-Version: ' . self::ZOTERO_API_VERSION
        );
        // Authentication is not required for read access to public libraries.
        if (isset($this->zoteroApiKey)) {
            array_push($headers, 'Zotero-API-Key: ' . $this->zoteroApiKey);
        }
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headers);
        
        // Return the transfer as a string instead of outputting it out directly
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        
        // The header data must be written by this callback. Return the number of bytes written.
        curl_setopt($this->curlHandle, CURLOPT_HEADERFUNCTION, array(
            &$this,
            'responseHeaderCallback'
        ));
        
        return $this;
    }

    /**
     * Executes Zotero API request using cURL
     *
     * @return string|false Returns the result on success, FALSE on failure.
     */
    public function execute()
    {
        // Execute request
        $response = curl_exec($this->curlHandle);
        
        // Free all resources.
        // For error handling do not close on failure.
        if (false !== $response) {
            curl_close($this->curlHandle);
        }
        
        return $response;
    }

    /**
     * Returns the latest response header
     *
     * @return string
     */
    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    /**
     * Returns the cURL handle
     *
     * @return resource
     */
    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    /**
     * Set cURL handle.
     * This optional function can be used between initializing and executing a request.
     *
     * @param resource $curlHandle
     */
    public function setCurlHandle($curlHandle)
    {
        $this->curlHandle = $curlHandle;
    }

    /**
     * Callback for header data
     *
     * @param resource $curlHandle
     *            cURL resource
     * @param string $line
     *            Header data to be written
     * @return number Number of bytes written
     */
    protected function responseHeaderCallback($curlHandle, $line)
    {
        $this->responseHeader .= $line;
        return strlen($line);
    }
}