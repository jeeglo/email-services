<?php

namespace Jeeglo\EmailService\Drivers;
use AWeberAPI;
session_start();

class Aweber 
{
    protected $consumer_key;
    protected $consumer_secret_key;
    protected $access_token;
    protected $access_token_secret;
    protected $aweber;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->redirect_url = (isset($credentials['redirect_url']) ? $credentials['redirect_url'] : 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $this->consumer_key = $credentials['consumer_key'];
        $this->consumer_secret_key = $credentials['consumer_secret_key'];
        $this->access_token = (isset($credentials['access_token']) ? $credentials['access_token'] : null) ;
        $this->access_token_secret = (isset($credentials['access_token_secret']) ? $credentials['access_token_secret'] : null);
        $this->aweber = new AWeberAPI($this->consumer_key, $this->consumer_secret_key);

    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            $lists = $this->aweber->getAccount($this->access_token, $this->access_token_secret);
            return $this->response($lists);
        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data)
    {   
        // @todo throw exception if email field is empty or list id or not available
        try {
            
            $list_id = $data['list_id'];

            $account = $this->aweber->getAccount($this->access_token, $this->access_token_secret);
            $listURL = "/accounts/{$account->data['id']}/lists/{$list_id}";
            
            $list = $account->loadFromUrl($listURL);
            
            # create a subscriber
            $params = array(
                'email' => $data['email'],
                'name' => trim( (isset($data['first_name']) ? $data['first_name'] : null) . ' ' . (isset($data['last_name']) ? $data['last_name'] : null))
            );
            
            $subscribers = $list->subscribers;
            $result = $subscribers->create($params);

        } catch (Exception $e) { // Catch any exceptions
            throw new \Exception($e->getMessage(), 1);
            
        }
    }

    /**
     * Call to Email service and get OAuth Url
     * @return [array]
     */
    public function connect()
    {
        // Make Call to Aweber and return OAuth Url
        if (empty($_GET['oauth_token'])) {
            $callbackUrl = $this->redirect_url;
            list($requestToken, $requestTokenSecret) = $this->aweber->getRequestToken($callbackUrl);
            $_SESSION['requestTokenSecret'] = $requestTokenSecret; 
            return ['url' => $this->aweber->getAuthorizeUrl()] ;
        }

    }

    /**
     * Get response from Email Service after user Allow access to our application
     * @return [array]
     */
    public function getConnectData()
    {
        // Get Connect Data from Aweber
        $this->aweber->user->tokenSecret = $_SESSION['requestTokenSecret'];
        $this->aweber->user->requestToken = $_GET['oauth_token'];
        $this->aweber->user->verifier = $_GET['oauth_verifier'];
        
        list($accessToken, $accessTokenSecret) = $this->aweber->getAccessToken();
        
        // Return Access Token and Access Token Secret
        return ['access_token' => $accessToken, 'access_token_secret' => $accessTokenSecret];
    }

    /**
     * [response description]
     * @return  array [return response as key value]
     */
    private function response($lists)
    {
        $response = [];

        try {
            if(!empty($lists)) {

                foreach ($lists->lists as $offset => $list) {
                    $response[] = array(
                        'name' => $list->data['name'],
                        'id' => $list->data['id']
                    );
                }
            }
        } catch (Exception $e) {
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
}