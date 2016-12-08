<?php

// NLP API Interface Class
class nlpApiInterface
{
    private $apiHost;
    private $apiKey;

    public function __construct()
    {
        $this->apiHost = NLP_API_HOST;
        $this->apiKey = NLP_API_KEY;
    }

    public function getSentiment($comment)
    {
        $url = $this->apiHost . 'calls/text/TextGetCombinedData?apikey=' . $this->apiKey;
        $parameters = array(
            'extract' => 'entities,keywords',
            'outputMode' => 'json',
            'sentiment' => '1',
            'maxRetrive' => 50,
            'text' => $comment
        );

        return $this->curlCall($url, $parameters);
    }

    private function curlCall($url, $parameters, $method = 'POST')
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
                $data = '';
                foreach ($parameters as $key => $val) {
                    $data .= $key . '=' . urlencode($val) . '&';
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }

            $headers = array('Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded');
            $curlHeaders[CURLOPT_HTTPHEADER] = array();
            foreach ($headers as $key => $value) {
                $curlHeaders[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
            }
            curl_setopt_array($curl, $curlHeaders);

            $result = curl_exec($curl);
            $curlInfo = (object)curl_getinfo($curl);
            $curlError = curl_error($curl);

            if ($curlError !== '') {
                throw new Exception("Error occured while calling NLP API => " . $curlError);
            }

            return json_decode($result, true);

        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}
