<?php

namespace Jeeglo\EmailService\Drivers;
use Infusionsoft\Infusionsoft as InfusionsoftAPI;
use Infusionsoft\Token;

class Infusionsoft
{
    protected $client_id;
    protected $client_secret_key;
    protected $access_token;
    protected $refresh_token;
    protected $redirect_uri;
    protected $infusionsoft;
    protected $api_url;

    public function __construct($credentials) {

        // set the property from credentials data
        $this->redirect_uri = (isset($credentials['redirect_uri']) ? $credentials['redirect_uri'] : null);
        $this->client_id = $credentials['client_id'];
        $this->client_secret_key = $credentials['client_secret'];
        $this->access_token = (isset($credentials['access_token']) ? $credentials['access_token'] : null) ;
        $this->refresh_token = (isset($credentials['refresh_token']) ? $credentials['refresh_token'] : null);

        // Initialize service
        $this->infusionsoft = new InfusionsoftAPI(array(
            'clientId'     => $this->client_id,
            'clientSecret' => $this->client_secret_key,
            'redirectUri'  => $this->redirect_uri,
        ));

        // prepare token params to make a token
        $token_params = array(
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => 86400
        );

        // set new tokens from params to make calls to infusionsoft API's
        $token = new Token($token_params);
        $this->infusionsoft->setToken($token);

        $this->api_url = 'https://api.infusionsoft.com/crm/rest/v1';
    }

    /**
     * fetch tags through API
     * @return array
     */
    public function getTags() {
        try {
            // set the default as false
            $isLoadMore = false;

            // initialized the empty array
            $tags_data = [];

            // set the first request url
            $url = $this->api_url.'/tags/?access_token='.$this->access_token.'&limit=1000&offset=0';

            // do while loop to fetch all the tags - by default respnse contain 1000 per page record
            do {
                // call to API
                $tags = $this->curlRequest($url);

                // if we have tags data then check the next page is available and push the data to our initialized array variable
                if(!empty($tags['tags'])) {

                    // push the current call data in our defined variable
                    foreach ($tags['tags'] as $tag) {
                        array_push($tags_data, $tag);
                    }

                    // if the count is greater than 1000 then make another call with new next page URL
                    if(isset($tags['count']) && $tags['count'] > 1000) {
                        if(isset($tags['next'])) {
                            // until we have next page or the tags data is not empty we need to loop through them to fetch all the tags
                            $isLoadMore = true;
                            $url = $tags['next'];
                            // get query params from url to append the access token
                            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'access_token='.$this->access_token;
                        }  else {
                            $isLoadMore = false;
                        }
                    }
                } else {
                    // if the tags are empty we do not need to loop further so set the variable as false
                    $isLoadMore = false;
                }
            } while ($isLoadMore);

            return $this->response($tags_data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            // Prepare the contact data to add in service
            $contact_data = array(
                'email_addresses' => array(
                    array(
                        'email' => $data['email'],
                        'field' => 'EMAIL1'
                    )
                ),
                'given_name' => trim((isset($data['first_name']) ? $data['first_name'] : null)),
                'family_name' => trim((isset($data['last_name']) ? $data['last_name'] : null)),
                'duplicate_option' => 'Email',
                'opt_in_reason' => 'Member of a Product/Collection (ProductDyno)'
            );

            // Send call to add contact
            $response = $this->infusionsoft->contacts()->create($contact_data, true);

            // If contact added successfully then add/remove the tags
            if($response->id) {
                $data['id'] = $response->id;
                $this->sync($data, $remove_tags, $add_tags);

                return $this->successResponse();
            }

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * [sync add and remove tags]
     * @return [array] [Success true]
     */
    private function sync($data, $removeTags ,$addTags)
    {
        try {
            // Add the Tags
            if(is_array($addTags) && count($addTags) > 0) {
                $this->infusionsoft->contacts()->find($data['id'])->addTags($addTags);
            }

            // Remove the tags
            if(is_array($removeTags) && count($removeTags) > 0 ) {
                $this->infusionsoft->contacts()->find($data['id'])->removeTags($removeTags);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Call to Email service and get OAuth Url
     * @return [array]
     */
    public function connect()
    {
        return ['url' => $this->infusionsoft->getAuthorizationUrl()] ;
    }

    /**
     * Get response from Email Service after user Allow access to our application
     * @return [array]
     */
    public function getConnectData()
    {
        // If user has successfully allowed access to our application then we have code on callback
        if (isset($_GET['code'])) {

            try {
                // If we are returning from Infusionsoft we need to exchange the code for an access token.
                $tokenList = serialize($this->infusionsoft->requestAccessToken($_GET['code']));

                // unserialize the token
                $tokenList = unserialize($tokenList);

                // return access token and refresh token
                return ['access_token' => $tokenList->accessToken, 'refresh_token' => $tokenList->refreshToken];

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else {
            // if no code found in Parameter, return failed response
            return $this->failedResponse();
        }
    }

    /**
     * Call to Email service to regenrate the access token and refresh token
     * @return [array]
     */
    public function regenrateAccessToken($refresh_token)
    {
        return $this->_regenrateAccessTokenAgainstRefreshToken($refresh_token);
    }

    /**
     * Regenrate access token againt the refresh token
     * @param $refresh_token
     * @return mixed
     */
    private function _regenrateAccessTokenAgainstRefreshToken($refresh_token)
    {
        try {
            // Initialize the empty array
            $new_token = [];

            // Set Headers
            $headers = array(
                'Authorization' => 'Basic ' . base64_encode($this->infusionsoft->getClientId() . ':' . $this->infusionsoft->getClientSecret()),
                'Content-Type'  => 'application/x-www-form-urlencoded'
            );

            // Set the params
            $params = array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh_token,
            );

            // Call the client
            $client = $this->infusionsoft->getHttpClient();

            // Send call to fetch the new access token and refresh token
            $tokenInfo = $client->request('POST', $this->infusionsoft->getTokenUri(),
                ['body' => http_build_query($params), 'headers' => $headers]);

            if($tokenInfo) {
                $new_token = json_decode($tokenInfo, true);
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $new_token;
    }

    /**
     * [response description]
     * @return  array [return response as key value]
     */
    private function response($tags_data)
    {
        $response = [];

        try {
            if(!empty($tags_data)) {

                foreach ($tags_data as $tag) {
                    $response[] = array(
                        'name' => $tag['name'],
                        'id' => $tag['id']
                    );
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    /**
     * [successResponse description]
     * @return [array] [Success true]
     */
    private function successResponse()
    {
        return ['success' => 1];
    }

    /**
     * [failedResponse description]
     * @return [array] [Success false]
     */
    private function failedResponse()
    {
        throw new \Exception('Something went wrong!');
    }

    /**
     * Infusionsoft test credentials
     */
    public function verifyCredentials()
    {
        try {
            $response = $this->getTags();
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);

        } catch (\Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Generic curl request for fetching all the tags via curl API call
     * @param string
     * @param array $data
     * @return array|bool|mixed|string
     */
    function curlRequest($request_url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        if (is_array($json)) {
            return $json;
        }

        return $response;
    }
}