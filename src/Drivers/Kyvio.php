<?php

namespace Jeeglo\EmailService\Drivers;

class Kyvio 
{
    protected $api_key;
    protected $api_url;

    public function __construct($credentials) {
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://kyvio.com/api/v1/";
        
    }     

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        $api_key = $this->api_key;
        $link="mailing-list";
        try {
            $contact = null;
            $resp = $this->curl($api_key,$contact,$link);
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

    /**
    * [addContact Add contact to list through API]
    * @return array [return success or fail]
    */
    public function addContact($data)
    {
        $api_key = $this->api_key;
        $link="subscribers/create";
		try {
            // set param fields
			$contact = array(
			    'api_key' => $api_key,
			    'list_id' => $data['list_id'],
			    'email' => $data['email'],
			    'name' => $data['first_name'].' '.$data['last_name']
            );
            $response = $this->curl($api_key,$contact,$link);
            
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


    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {
        $curl = curl_init($link);
        if($contact==null)
        {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $link.$api_key,
            ));
        }
        else
			{   
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
			    'Content-Type: application/json',                                                                                
                ));
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($contact));
            }

			// execute Curl
			$response = curl_exec($curl);
			// close the connection of Curl
            curl_close($curl);
            
            return $response;
    }
}