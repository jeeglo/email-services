<?php

namespace Jeeglo\EmailService\Drivers;
use AweberApi;

class Aweber 
{
    protected $consumer_key;
    protected $consumer_secret_key;
    protected $access_token;
    protected $access_token_secret;
    protected $aweber;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->access_token = $credentials['access_token'];
        $this->aweber = new AWeberAPI($consumerKey, $consumerSecret);

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