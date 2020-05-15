<?php

namespace Jeeglo\EmailService\Drivers;

class EmailOctopus
{
    protected $api_key;

    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://emailoctopus.com/api/1.5/";
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
           
            $res = $this->curl('lists');
            $lists_data = json_decode($res, true);
            $lists = [];
            
            if (isset($lists_data['data'])) {
                foreach ($lists_data['data'] as $data) {
                    $lists[] = array(
                        'name' => $data['name'],
                        'id' => $data['id']
                    );
                }
                
                return $lists;
        
            } else {
                return $this->failedResponse();
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
                'email_address' => $data['email'],
                'fields' => [
                    'FirstName' => $data['first_name'],
                    'LastName'  => $data['last_name']
                ],
                "status" => "SUBSCRIBED",
            );

            // Inster member in the list
            $res =  $this->curl('lists/'.$data['list_id'].'/contacts', $contact, "POST");
            $response = json_decode($res);

            // If member already added in the list, we need to call update method
            if($response->error->code === "MEMBER_EXISTS_WITH_EMAIL_ADDRESS")
            {
                // convert email in md5
                $email_hash = md5(strtolower($data['email']));
                
                // send update call
                $res =  $this->curl('lists/'.$data['list_id'].'/contacts/'.$email_hash, $contact, "PUT");
            }

            return $this->successResponse();

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
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
        
        if($method == 'GET') {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url."?api_key=".$this->api_key,
            ));
        }

        if($method == 'POST') {  
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
            ));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        }
       
        if ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS,http_build_query($data));
            $url."?api_key=".$this->api_key;
        }

        // execute Curl
        $response = curl_exec($curl);
        
        // close the connection of Curl
        curl_close($curl);
            
        return $response;  
    }
}