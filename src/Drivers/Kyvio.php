<?php

namespace Jeeglo\EmailService\Drivers;

class Kyvio 
{
    protected $api_key;

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
        try {
            $resp = $this->curl('mailing-list',[],'GET');
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
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            // set param fields
			$contact = array(
			    'api_key' => $this->api_key,
			    'list_id' => $data['list_id'],
			    'email' => $data['email'],
			    'name' => $data['first_name'].' '.$data['last_name']
            );

            // send curl request
            $response =  $this->curl('subscribers/create',$contact,"POST");
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

    /**
     * Request Method
     * @return array for getList
     * @return array for addContact
     */
    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {
        $url = $this->api_url.$api_method;
        $curl = curl_init($url);
        if($method == 'GET')
        {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url."?api_key=".$this->api_key,
            ));
        }

        if($method == 'POST')
		{
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json',
            ));

            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // execute Curl
        $response = curl_exec($curl);
        // close the connection of Curl
        curl_close($curl);

        return $response;
    }

    /**
     * Kyvio test credentials
     */
    public function verifyCredentials()
    {

        $response = $this->getLists();

        if(isset($response['error']) && $response['error']) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection Succeeded.']);

        }
    }
}