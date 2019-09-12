<?php

namespace Jeeglo\EmailService\Drivers;

class Kyvio 
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
        $api_key = $this->api_key;
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://kyvio.com/api/v1/mailing-list?api_key='.$api_key,
            ));
            $resp = curl_exec($curl);
            curl_close($curl);
            $lists = [];
            $lists_data = json_decode($resp, true);
            $error = (isset($lists_data['success']) && $lists_data['success'] == true ? 0 : 1);
            $lists = [];
            if(count($lists_data) > 0 && !$error) {
                foreach ($lists_data['payload'] as $data) {
                    $lists[] = array(
                        'name' => $data['name'],
                        'id' => $data['listId']
                    );
                }
                return $lists;
            
            } else {
                return ['error' => true];
            }

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
        
    }

    // /**
    //  * [addContact Add contact to list through API]
    //  * @return string [return success or fail]
    //  */
    public function addContact($data)
    {
        $api_key =$this->api_key;
		try {
            // set param fields
			$contact = array(
			    'api_key' => $api_key,
			    'list_id' => $data['list_id'],
			    'email' => $data['email'],
			    'name' => $data['first_name'].' '.$data['last_name']
            );
			// Curl Post Query
			$contact_sync = curl_init('https://kyvio.com/api/v1/subscribers/create');

			curl_setopt($contact_sync, CURLOPT_HTTPHEADER, array(                                                                          
			    'Content-Type: application/json',                                                                                
			));
			curl_setopt($contact_sync, CURLOPT_POSTFIELDS, json_encode($contact));

			// execute Curl
			$response = curl_exec($contact_sync);
			// close the connection of Curl
			curl_close($contact_sync);
            return $this->successResponse();
            
        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    /**
     * [response description]
     * @return  array [return response as key value]
     */
    private function response($lists)
    {
       
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