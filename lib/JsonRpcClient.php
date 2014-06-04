<?php
/**
 * @file
 * Original copyright 2007 Sergio Vaccaro <sergio@inservibile.org>
 * part of JSON-RPC PHP, modified for Drupal/eCash.io using
 * cURL instead of fopen
 */

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class WooCommerca_Ripple_JsonRPCClient
{

    /**
     * Watchdog state
     *
     * @var boolean
     */
    private $watchdog = array();

    /**
     * The server URL
     *
     * @var string
     */
    private $url;
    /**
     * The request id
     *
     * @var integer
     */
    private $id;
    /**
     * If true, notifications are performed instead of requests
     *
     * @var boolean
     */
    private $notification = false;

    /**
     * Takes the connection parameters
     *
     * @param string $url
     * @param boolean $watchdog
     */
    public function __construct($url)
    {
        $this->url = $url;
        empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
        $this->watchdog = '';
        $this->id = 1;
    }

    /**
     * Performs a jsonRCP request and gets the results as an array
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public function __call($method, $params)
    {
        if (!is_scalar($method)) {
            throw new Exception('JSON-RPC Error: method name has no scalar value.');
        }
        if (is_array($params)) {
            $params = array_values($params);
        }
        else {
            throw new Exception('JSON-RPC Error: params must be given as array.');
        }

        $currentId = $this->id;

        // prepares the request
        $request = array(
            'jsonrpc' => '1.0',
            'id' => $currentId,
            'method' => $method,
            'params' => $params,
        );
        $request = json_encode($request);
        $this->watchdog['request'] = $request;

        $response = $this->curlPost($request);

        // final checks and return
        if (isset($response['id']) && $response['id'] != $currentId) {
            throw new Exception('JSON-RPC Error: incorrect response ID. Current: ' . $currentId . '. Resonse: ' . $this->watchdog['request']);
        }
        if (isset($response['id']) && !is_null($response['error'])) {
            throw new Exception('JSON-RPC Error: ' . $this->watchdog['response']);
        }
        return isset($response['result']) ? $response['result'] : FALSE;
    }

    /**
     * Get with CURL and return response
     */
    public function curlPost($request)
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (curl_errno($ch)) {
            throw new Exception('JSON-RPC Error: ' . curl_error($ch));
        }

        $data = json_decode(curl_exec($ch), TRUE);
        if (empty($data)) {
            throw new Exception('JSON-RPC Error: no data. Please check your Ripple JSON-RPC settings.');
        }
        elseif (isset($data['error'])) {
            throw new Exception('JSON-RPC Error: ' . $data['error']['message'] . ' (' . $data['error']['code'] . ')' );
        }
        return $data;
    }

}
