<?php
namespace Jeeglo\EmailService\Drivers;

use GuzzleHttp\Client;
session_start();
class AweberOauth2
{
    protected $accessToken;
    protected $client;
    protected $api_url;
    protected $provider;
    protected $redirect_url;
    protected $clientId;
    protected $clientSecret;

    public function __construct($credentials)
    {
        $this->accessToken = (isset($credentials['access_token']) ? $credentials['access_token'] : null) ;
        $this->redirect_url = (isset($credentials['redirect_url']) ? $credentials['redirect_url'] : null);
        $this->clientId = (isset($credentials['clientId']) ? $credentials['clientId'] : null);
        $this->clientSecret = (isset($credentials['clientSecret']) ? $credentials['clientSecret'] : null);
        $urlAuthorize = 'https://auth.aweber.com/oauth2/authorize';
        $urlAccessToken = 'https://auth.aweber.com/oauth2/token';
        $this->api_url = 'https://api.aweber.com/1.0/accounts';
        $this->client = new Client();

        $scopes = ['account.read','list.read','list.write','subscriber.read','subscriber.write'];

        $this->provider = new \League\OAuth2\Client\Provider\GenericProvider([

            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirect_url' =>(isset($credentials['redirect_url']) ? $credentials['redirect_url'] : null),
            'scopes' => $scopes,
            'scopeSeparator' => ' ',
            'urlAuthorize' => $urlAuthorize,
            'urlAccessToken' =>$urlAccessToken,
            'urlResourceOwnerDetails' => $this->api_url,
        ]);
    }

    public function regenrateAccessToken($refreshToken)
    {

        $response = $this->client->post(
            'https://auth.aweber.com/oauth2/token', [
                'auth' => [
                    $this->clientId, $this->clientSecret
                ],
                'json' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken
                ]
            ]
        );
        $body = $response->getBody();
        $newCredentials = json_decode($body, true);
        return $newCredentials;

    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        $response = $this->genericCurlRequest($this->api_url, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
        $response = json_decode($response, true);
        $listsUrl = $response['entries'][0]['lists_collection_link'];
        $lists = $this->genericCurlRequest($listsUrl, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
        $response = json_decode($lists, true);
        $email = $data['email'];
        $params = array(
            'ws.op' => 'find',
            'email' => $email
        );

        $list_id = $data['list_id'];
        $listsUrl .= '/' . $list_id . '/subscribers';
        $findUrl = $listsUrl . '?' . http_build_query($params);
        $lists = $this->genericCurlRequest($findUrl, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
        $foundSubscribers = json_decode($lists, true);

        // If given entry is exist then update
        if (isset($foundSubscribers['entries'][0]['self_link'])) {
            $subscriberUrl = $foundSubscribers['entries'][0]['self_link'];
            $contactData = array(
                'name' => (isset($data['first_name']) ? $data['first_name'] : null ). ' ' .(isset($data['last_name']) ? $data['last_name'] : null ),
                'email' => $data['email'],

                'tags' =>
                    array(
                        'add' => $add_tags,
                        'remove' => $remove_tags,
                    )
            );

            $subscriberResponse = $this->client->patch($subscriberUrl, [
                'json' => $contactData,
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken]
            ])->getBody();
            $subscriber = json_decode($subscriberResponse, true);

        } else {

            // Add new entry
            $addTags = implode(',', $add_tags);
            $contactData = array(
                'name' => (isset($data['first_name']) ? $data['first_name'] : null ). ' ' .(isset($data['last_name']) ? $data['last_name'] : null ),
                'email' => $data['email'],
                'tags' => [$addTags]
            );
            try {
                $body = $this->client->post($listsUrl, [
                    'json' => $contactData,
                    'headers' => ['Authorization' => 'Bearer ' . $this->accessToken]
                ]);

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), 1);
            }
        }
    }

    /**
     * [response description]
     * @return  array [return response as key value]
     */
    private function response($data)
    {
        if (!empty($data['lists'])) {
            $response = [];

            foreach ($data['lists'] as $list) {
                $response[] = array(
                    'name' => $list['name'],
                    'id' => $list['id']
                );
            }
            return $response;
        }

        if (!empty($data['tags'])) {

            // Remove duplicate tags if exists
            $listTags = [];
            foreach ($data['tags'] as $key => $tagArray) {

                foreach ($tagArray as $tag) {

                    if (!in_array($tag, $listTags)) {
                        $listTags[] = $tag;
                    }
                }
            }

            // get tags and return
            $tags = [];
            foreach ($listTags as $tag) {
                $tags[] = array(
                    'name' => $tag,
                );
            }
            return $tags;
        }
    }

    /**
     * Fetch tags
     * @return array
     */
    public function getTags()
    {
        $response = $this->genericCurlRequest($this->api_url, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
        $response = json_decode($response, true);

        if (isset($response['entries'][0]['lists_collection_link'])) {

            $listsUrl = $response['entries'][0]['lists_collection_link'];
            $lists = $this->genericCurlRequest($listsUrl, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);

            $tags = [];
            foreach (json_decode($lists, true)['entries'] as $list) {

                if (isset($response['entries'][0]['self_link'])) {
                    $tagUrl = $list['self_link'] . '/tags';  // choose the first list
                    $tags[] = json_decode($this->genericCurlRequest($tagUrl, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]), true);
                }
            }
            $data['tags'] = $tags;
            return $this->response($data);
        }
    }

    /**
     * [getLists Fetch List through API]
     */
    public function getLists()
    {

        $response = $this->genericCurlRequest($this->api_url, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
        $response = json_decode($response, true);

        if (isset($response['entries'][0]['lists_collection_link'])) {
            $listsUrl = $response['entries'][0]['lists_collection_link'];
            $lists = $this->genericCurlRequest($listsUrl, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
            $lists_data['lists'] = json_decode($lists, true)['entries'];

            return $this->response($lists_data);
        }

    }

    /**
     * Genral method for connection test
     * @param $url
     * @param string $type
     * @param array $params
     * @param array $headers
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function genericCurlRequest($url, $type = 'POST', $params = [], $headers = [])
    {
        try {
            // CURL Post to the Webhook URL
            $res = $this->client->request($type, trim($url), [
                'form_params' => $params,
                'timeout' => 60,
                'headers' => $headers
            ]);

            $response_body = $res->getBody()->getContents();

            return $response_body;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    /**
     * Connect Aweber
     * @return array
     */
    public function connect()
    {
        $_SESSION['redirect_url'] = $this->redirect_url;
        $authorizationUrl = $this->provider->getAuthorizationUrl();
        return ['url' => $authorizationUrl];

    }

    /**
     * Get response from Email Service after user Allow access to our application
     * @return [array]
     */
    public function getConnectData()
    {

        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $authorizationResponse = rtrim($actual_link, PHP_EOL);
        $parsedUrl = parse_url($authorizationResponse, PHP_URL_QUERY);
        parse_str($parsedUrl, $parsedArray);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $parsedArray['code']
        ]);
        $accessToken = $token->getToken();
        $refreshToken = $token->getRefreshToken();
        return ['access_token' => $accessToken,'redUrl'=>$_SESSION['redirect_url'],'refresh_token' => $refreshToken];
    }

    /**
     * Aweber test credentials
     */
    public function verifyCredentials()
    {
        try {
            $response = $this->genericCurlRequest($this->api_url, 'get', [], ['Authorization' => 'Bearer ' . $this->accessToken]);
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);

        } catch (\Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }

}
