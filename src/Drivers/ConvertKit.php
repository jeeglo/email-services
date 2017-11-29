<?php

namespace Jeeglo\EmailService\Drivers;

class ConvertKit 
{
    protected $api_key;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://api.convertkit.com/v3/forms?api_key='.$this->api_key,
            ));
            
            $resp = curl_exec($curl);
            
            curl_close($curl);

            $lists = json_decode($resp);

            $error = isset($lists_data->error) ? true : false; 

            if(count($lists) > 0 && !$error) {
                return $this->response($lists);
            } else {
                throw new \Exception("Error Processing Request", 1);
            }

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
                'api_key' => $this->api_key,
                'email' => $data['email'],
                'first_name' => (isset($data['first_name']) ? $data['first_name'] : null) ,

                'fields' => array(
                    'last_name' => (isset($data['last_name']) ? $data['last_name'] : null)
                ),
            );

            // Curl Post Query
            $ch = curl_init('https://api.convertkit.com/v3/forms/'.$data['list_id'].'/subscribe');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

            // execute Curl
            $response = curl_exec($ch);
            // close the connection of Curl
            curl_close($ch);

            $decoded_response = json_decode($response);

            if(isset($decoded_response->error)) {
                throw new \Exception($decoded_response->message, 1);
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

                foreach ($lists as $key => $item ) {
                    
                    foreach ($item as $value) {
                        $response[] = array(
                            'name' => $value->name,
                            'id' => $value->id
                        );
                    }
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