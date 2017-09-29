<?php
/**
 * Nexway Connect API
 * https://api-doc.nexway.store/nexway-connect/reference
 * @Author: Md. Sabbirul Latif
 */

/**
 * Class NexwayConnect
 * @package NexwayConnect
 */
class NexwayConnect
{
    private $tokenURL;
    private $hostURL;
    private $feedUrl;
    private $clientSecret;
    private $realmName;
    private $staging;

    /**
     * NexwayConnect constructor.
     * Initialize variables
     *
     * @param string $clientSecret
     * @param string $realmName
     * @param bool $staging
     *
     * @throws Exception
     */
    function __construct($clientSecret = "", $realmName = "", $staging = true)
    {
        if (empty($clientSecret) || empty($realmName)) {
            throw new Exception("client credentials Missing. Secret, realmName Required");
        }
        $this->clientSecret = $clientSecret;
        $this->realmName = $realmName;
        $this->staging = $staging;
        $this->hostURL = ($staging)? "https://api-uat.staging.nexway.build": "https://api.nexway.store";
        $this->feedUrl = ($staging)? "http://connect-uat.nexway.build": "http://webservices.nexway.com";
        $this->tokenURL = ($staging)? "https://api.staging.nexway.build": "https://api.nexway.store";
    }

    /**
     * Generate and refresh access token
     * https://api-doc.nexway.store/nexway-connect/reference/jwt-authentication/get-user-token
     * @param bool $refresh
     * @param bool $return
     *
     * @return
     * @throws Exception
     */
    function getUserToken($refresh = false, $return = false)
    {
        $response = "";
        $url = $this->tokenURL . "/iam/tokens";
        $param = array(
            "clientSecret" => $this->clientSecret,
            "realmName"    => $this->realmName,
        );
        if ($refresh && isset($this->token->refresh_token) && !empty($this->token->refresh_token)) {
            $param["grantType"] = "refresh_token";
            $param["refreshToken"] = $this->token->refresh_token;
        } else {
            $param["grantType"] = "client_credentials";
        }
        $response = $this->getCurlResponse($url, "post", json_encode($param), array(
            "Content-Type: application/json"
        ));
        if (empty($response)) throw new Exception("Empty returned");
        $response = json_decode($response);
        if (!is_object($response)) throw new Exception("Data format not supported.");
        if (isset($response->error)) {
            throw new Exception("!!ERROR: $response->error \n Description: $response->message");
        } elseif (isset($response->access_token) && !empty($response->access_token)) {
            $this->token = $response;
            if ($return) return $response->access_token;
        }
        else throw new Exception("Token Not Found");
    }

    /**
     * Reset refresh tokens
     * https://api-doc.nexway.store/nexway-connect/reference/jwt-authentication/invalidate-token
     */
    function invalidateToken()
    {
        $url = $this->hostURL . "/iam/tokens/reset";
        $response = $this->getCurlResponse($url, "DELETE");
        unset($this->token);
    }

    /**
     * Get stock information about a product
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-products/get-stock-status
     * @param string $secret
     * @param string $productRef
     *
     * @return string
     * @throws Exception
     */
    function getStockStatus($secret = "", $productRef = "")
    {
        if (empty($secret) || empty($productRef)) {
            throw new Exception("Secret or productRef Missing.");
        }
        $productRef=(!is_array($productRef))?array($productRef):$productRef;
        $url = $this->hostURL . "/connect/stock";
        $bearer = $this->generateAccessTokens(true, true);
        $param = array("productRefs" => $productRef);
        $response = $this->getCurlResponse($url, "post", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Get cross sell and up sell products
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-products/get-cross-up-sell
     * @param string $secret
     * @param string $language
     * @param string $products
     *
     * @return string
     */
    function getCrossUpSell($secret = "", $language = "", $products = "")
    {
        if (empty($secret) || empty($language) || empty($products)) {
            throw new Exception("Secret or language or productRef Missing.");
        }
        $products=(!is_array($products))?array($products):$products;
        $url = $this->hostURL . "/connect/order/crossupsell";
        $bearer = $this->generateAccessTokens(true, true);
        $param = array(
            "language" => $language,
            "products" => $products
        );
        $response = $this->getCurlResponse($url, "post", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Create new order
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-orders/create-order
     * @param string $secret
     * @param string $order
     *
     * @return string
     */
    function createOrder($secret = "", $order = "")
    {
        if (empty($secret) || empty($order)) {
            throw new Exception("Secret or Order Missing.");
        }
        $url = $this->hostURL . "/connect/order/new";
        $bearer = $this->generateAccessTokens(true, true);
        $response = $this->getCurlResponse($url, "post", $order, array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Cancel a order
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-orders/cancel-order
     * @param string $secret
     * @param string $partnerOrderNumber
     * @param int $reasonCode
     * @param string $comment
     *
     * @return string
     */
    function cancelOrder($secret = "", $partnerOrderNumber = "", $reasonCode = 2, $comment = "")
    {
        if (empty($secret) || empty($partnerOrderNumber) || !is_numeric($reasonCode)) {
            throw new Exception("Secret or partnerOrderNumber Missing. reasonCode Must be int");
        }
        $url = $this->hostURL . "/connect/order/cancel";
        $param = array(
            "comment"            => $comment,
            "partnerOrderNumber" => $partnerOrderNumber,
            "reasonCode"         => $reasonCode
        );
        $bearer = $this->generateAccessTokens(true, true);
        $response = $this->getCurlResponse($url, "PUT", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . trim($bearer),
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Get a order Information
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-orders/get-order
     * @param string $secret
     * @param string $orderId
     *
     * @return string
     */
    function getOrder($secret = "", $orderId = "")
    {
        if (empty($secret) || empty($orderId)) {
            throw new Exception("secret or orderId Missing.");
        }
        $url = $this->hostURL . "/connect/order/";
        $bearer = $this->generateAccessTokens(true, true);
        $response = $this->getCurlResponse($url . $orderId, "get", "", array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Get order download info
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-orders/get-order-download-info
     * @param string $secret
     * @param string $orderId
     *
     * @return string
     */
    function getOrderDownloadInfo($secret = "", $orderId = "")
    {
        if (empty($secret) || empty($orderId)) {
            throw new Exception("secret or orderId Missing.");
        }
        $url = $this->hostURL . "/connect/order/$orderId/download";
        $bearer = $this->generateAccessTokens(true, true);
        $response = $this->getCurlResponse($url, "get", "", array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Update order download time
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-orders/update-download-time
     * @param string $partnerOrderNumber
     * @param string $value
     *
     * @return string
     */
    function updateDownloadTime($partnerOrderNumber = "", $value = "")
    {
        if (empty($secret) || empty($value)) {
            throw new Exception("partnerOrderNumber of Expire date Missing.");
        }
        $url = $this->hostURL . "/connect/order/download";
        $bearer = $this->generateAccessTokens(true, true);
        $param = array(
            "partnerOrderNumber" => $partnerOrderNumber,
            "value"              => $value
        );
        $response = $this->getCurlResponse($url, "PUT", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Get product feed
     *
     * @param string $secret
     * @param string $provider
     * @param string $config
     *
     * @return mixed|string
     */
    function getProductFeed($secret = "", $provider = "", $config = "")
    {
        if (empty($secret) || empty($provider) || empty($config)) {
            return "";
        }
        $url = $this->feedUrl . "/getCatalog.xml";
        $response = $this->getCurlResponse($url, "get", array(
            "secret"   => $secret,
            "provider" => $provider,
            "config"   => $config,
        ));
        return $response;
    }


    /**
     * Get subscription status
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-subscription/get-subscription-status
     * @param string $secret
     * @param string $partnerOrderNumber
     * @param string $subscriptionId
     *
     * @return string
     */
    function getSubscriptionStatus($secret = "", $partnerOrderNumber = "", $subscriptionId = "")
    {
        if (empty($secret) || empty($partnerOrderNumber) || empty($subscriptionId)) {
            throw new Exception("secret or partnerOrderNumber or subscriptionId Missing.");
        }
        $url = $this->hostURL . "/connect/subscription";
        $bearer = $this->generateAccessTokens(true, true);
        $param = array(
            "partnerOrderNumber" => $partnerOrderNumber,
            "subscriptionId"     => $subscriptionId
        );
        $response = $this->getCurlResponse($url, "post", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Cancel subscription
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-subscription/cancel-subscription
     * @param string $secret
     * @param string $partnerOrderNumber
     * @param string $subscriptionId
     *
     * @return string
     */
    function cancelSubscription($secret = "", $partnerOrderNumber = "", $subscriptionId = "")
    {
        if (empty($secret) || empty($partnerOrderNumber) || empty($subscriptionId)) {
            throw new Exception("secret or partnerOrderNumber or subscriptionId Missing.");
        }
        $url = $this->hostURL . "/connect/subscription";
        $bearer = $this->generateAccessTokens(true, true);
        $param = array(
            "partnerOrderNumber" => $partnerOrderNumber,
            "subscriptionId"     => $subscriptionId
        );
        $response = $this->getCurlResponse($url, "PUT", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Renew subscription
     * https://api-doc.nexway.store/nexway-connect/reference/manage-your-subscription/renew-subscription
     * @param string $secret
     * @param string $partnerOrderNumber
     * @param string $subscriptionId
     *
     * @return string
     */
    function renewSubscription($secret = "", $partnerOrderNumber = "", $subscriptionId = "")
    {
        if (empty($secret) || empty($partnerOrderNumber) || empty($subscriptionId)) {
            throw new Exception("secret or partnerOrderNumber or subscriptionId Missing.");
        }
        $url = $this->hostURL . "/connect/subscription/renew";
        $bearer = $this->generateAccessTokens(true, true);
        $param = array(
            "partnerOrderNumber" => $partnerOrderNumber,
            "subscriptionId"     => $subscriptionId
        );
        $response = $this->getCurlResponse($url, "PUT", json_encode($param), array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Get all nexway categories
     * https://api-doc.nexway.store/nexway-connect/reference/get-information-on-catalog/get-categories
     * @param string $secret
     * @param string $language
     *
     * @return string
     */
    function getCategories($secret = "", $language = "")
    {
        if (empty($secret) || empty($language)) {
            throw new Exception("secret or language Missing.");
        }
        $url = $this->hostURL . "/connect/catalog/categories/" . $language;
        $bearer = $this->generateAccessTokens(true, true);
        $response = $this->getCurlResponse($url, "get", "", array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * Get OS list
     * https://api-doc.nexway.store/nexway-connect/reference/get-information-on-catalog/get-operating-systems
     * @param string $secret
     *
     * @return bool|string
     */
    function getOperatingSystems($secret = "")
    {
        if (empty($secret)) {
            throw new Exception("secret Missing.");
        }
        $url = $this->hostURL . "/connect/catalog/oslist";
        $bearer = $this->generateAccessTokens(true, true);
        $response = $this->getCurlResponse($url, "get", "", array(
            "secret: " . $secret,
            "Authorization: Bearer " . $bearer,
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));
        if (!empty($response)) {
            return json_decode($response);
        }else{
            throw new Exception("Empty returned.");
        }
    }

    /**
     * PHP CURL
     *
     * @param string $url
     * @param string $type
     * @param array $data
     * @param array $header
     *
     * @return mixed|string
     * @throws Exception
     */
    public function getCurlResponse($url = "", $type = "", $data = array(), $header = array())
    {
        $supportedMethods = array("post", "get", "put", "delete");
        if (empty($url) || !in_array(strtolower($type), $supportedMethods)) {
            throw new Exception("CURL Required Parameter Missing");
        }
        $type = empty($type)? "get": $type;
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        $curl = curl_init();
        if (!$curl) {
            throw new Exception("CURL failed to initialize ");
        }
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if ((strtolower($type) === "get")) {
            $url = $url . "?" . $data;
        } elseif (strtolower($type) === "post") {
            curl_setopt($curl, CURLOPT_POST, true);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        if ((strtolower($type) != "get") && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (false === $response || $httpcode == 400) {
            throw new Exception("CURL Failed OR 400 Bad Request ");
        }
        curl_close($curl);
        return $response;
    }
}
