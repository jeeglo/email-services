<?php

namespace Jeeglo\EmailService\Drivers;
use DrewM\Drip\Drip as DripApi;
use DrewM\Drip\Dataset;

class Drip 
{
    protected $api_token;
    protected $account_id;
    protected $drip;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_token = $credentials['api_token'];
        $this->account_id = $credentials['account_id'];
        
        $this->drip = new DripApi($this->api_token, $this->account_id);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            $lists = $this->drip->get('campaigns');

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

            $data = new Dataset('subscribers', [
                'email' => $data['email'],
                'custom_fields' => [
                    'first_name' => (isset($data['first_name']) ? $data['first_name'] : null),
                    'last_name' => (isset($data['last_name']) ? $data['last_name'] : null),
                ],
            ]);
            
            $response = $this->drip->post('campaigns/'.$list_id.'/subscribers', $data);

            if(isset($response->errors)) {
                throw new \Exception($response->errors[0]['message'], 1);
            } else {
                return $this->successResponse();
            }    

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

                foreach ($lists->campaigns as $list) {
                    $response[] = array(
                        'name' => $list['name'],
                        'id' => $list['id']
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