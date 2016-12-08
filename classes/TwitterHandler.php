<?php

/**
 * Similar to Facebook Apps, you'll need to create a Twitter app first: https://apps.twitter.com/
 *
 * Code below from http://stackoverflow.com/questions/12916539/simplest-php-example-retrieving-user-timeline-with-twitter-api-version-1-1 by Rivers
 * with a few modfications by Mike Rogers to support variables in the URL nicely
 */

class TwitterHandler
{

    private $oauthAccessToken = '';
    private $oauthAccessTokenSecret = '';
    private $consumerKey = '';
    private $consumerSecret = '';
    private $userId = '';
    private $screenName = '';
    private $count = 5;
    /**
     * The tokens, keys and secrets from the app you created at https://dev.twitter.com/apps
     */
    private $config = array(
        'useWhitelist' => true, // If you want to only allow some requests to use this script.
        'baseUrl' => 'https://api.twitter.com/1.1/'
    );

    /**
     * Only allow certain requests to twitter. Stop randoms using your server as a proxy.
     */
    private $whitelist = array();

    /**
     *	@param	string	$oauthAccessToken			OAuth Access Token			('Access token' on https://apps.twitter.com)
     *	@param	string	$oauthAccessTokenSecret	    OAuth Access Token Secret	('Access token secret' on https://apps.twitter.com)
     *	@param	string	$consumerKey				Consumer key				('API key' on https://apps.twitter.com)
     *	@param	string	$consumerSecret			    Consumer secret				('API secret' on https://apps.twitter.com)
     *	@param	string	$userId					    User id (http://gettwitterid.com/)
     *	@param	string	$screenName				    Twitter handle
     *	@param	string	$count						The number of tweets to pull out
     */
    public function __construct($oauthAccessToken, $oauthAccessTokenSecret, $consumerKey, $consumerSecret, $userId, $screenName, $count = 5)
    {

        $this->oauthAccessToken = $oauthAccessToken;
        $this->oauthAccessTokenSecret = $oauthAccessTokenSecret;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->userId = $userId;
        $this->screenName = $screenName;
        $this->config = array_merge($this->config, compact('oauthAccessToken', 'oauthAccessTokenSecret', 'consumerKey', 'consumerSecret', 'userId', 'screenName', 'count'));

        $this->whitelist['statuses/user_timeline.json?user_id=' . $this->config['userId'] . '&screenName=' . $this->config['screenName'] . '&count=' . $this->config['count']] = true;
    }

    private function buildBaseString($baseURI, $method, $params)
    {
        $r = array();
        ksort($params);
        foreach ($params as $key=>$value){
            $r[] = "$key=" . rawurlencode($value);
        }

        return $method.'&'.rawurlencode($baseURI).'&'.rawurlencode(implode('&', $r));
    }

    private function buildAuthorizationHeader($oauth)
    {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }
        $r .= implode(', ', $values);

        return $r;
    }

    public function get($url)
    {
        if (! isset($url)){
            die('No URL set');
        }

        if ($this->config['useWhitelist'] && ! isset($this->whitelist[$url])){
            die('URL is not authorised');
        }

        // Figure out the URL parameters
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $urlArguments);

        $fullUrl = $this->config['baseUrl'].$url; // URL with the query on it
        $baseUrl = $this->config['baseUrl'].$urlParts['path']; // URL without the query

        // Set up the OAuth Authorization array
        $oauth = array(
            'oauth_consumer_key' => $this->config['consumerKey'],
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->config['oauthAccessToken'],
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );

        $baseInfo = $this->buildBaseString($baseUrl, 'GET', array_merge($oauth, $urlArguments));

        $compositeKey = rawurlencode($this->config['consumerSecret']).'&'.rawurlencode($this->config['oauthAccessTokenSecret']);

        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseInfo, $compositeKey, true));

        // Make Requests
        $header = array(
            $this->buildAuthorizationHeader($oauth),
            'Expect:'
        );
        $options = array(
            CURLOPT_HTTPHEADER => $header,
            //CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $result = curl_exec($feed);
        $info = curl_getinfo($feed);
        curl_close($feed);

        // Send suitable headers to the end user.
        if (isset($info['content_type']) && isset($info['size_download'])){
            //header('Content-Type: ' . $info['content_type']);
            //header('Content-Length: ' . $info['size_download']);
        }

        return $this->decode_curl_response('json', $result);
    }

    /**
     * Decode the curl response with appropriate method
     *
     * @param  string   $decoder  json/xml
     * @param  string   $response  CURL response text.
     * @return Array    decode response message in PHP array format.
     */
    protected function decode_curl_response($decoder = "json", $response)
    {
        // Decode response message.
        if ($decoder == "json") {
            return json_decode($response, true);
        } else {
            // TODO : Implement to decode xml / other type of reponse.
            return $response;
        }
    }

    public function getComments($url)
    {
        $this->whitelist[$url] = true;

        return $this->get($url);
    }

}