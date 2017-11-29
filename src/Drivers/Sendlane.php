<?php

namespace Jeeglo\EmailService\Drivers;

class Sendlane 
{
    protected $api_key;
    protected $hash;
    protected $domain;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->hash = $credentials['hash'];
        $this->domain = $credentials['domain'];
        
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            
            $params = array(
                'api' => $this->api_key,
                'hash' => $this->hash,
            );

            // Curl Post Query
            $ch = curl_init('http://'.$this->domain.'.sendlane.com/api/v1/lists');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            // execute Curl
            $lists_data = curl_exec($ch);

            // close the connection of Curl
            curl_close($ch);

            // Get Response
            $lists = json_decode($lists_data);

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
            
            // set param fields
            $params = array(
                'api' => $this->api_key,
                'hash' => $this->hash,
                'list_id' => $data['list_id'],
                'first_name' => (isset($data['first_name']) ? $data['first_name'] : null),
                'last_name' => (isset($data['last_name']) ? $data['last_name'] : null),
                'email' => $data['email']
            );

            // Curl Post Query
            $ch = curl_init('http://'.$this->domain.'.sendlane.com/api/v1/list-subscriber-add');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            // execute Curl
            $response = curl_exec($ch);
            // close the connection of Curl
            curl_close($ch);

            $decoded_response = json_decode($response);

            if(isset($decoded_response->error->messages)) {
                throw new \Exception($decoded_response->error->messages, 1);
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

                foreach ($lists as $list) {
                    $response[] = array(
                        'name' => $list->list_name,
                        'id' => $list->list_id
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