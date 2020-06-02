<?php

namespace Jeeglo\EmailService\Drivers;


class Sendiio
{
    protected $api_token;
    protected $api_secret;
    protected $api_url;

    public function __construct($credentials) {
        // @todo Throw exception if API key is not available
        $this->api_token = $credentials['api_token'];
        $this->api_secret = $credentials['api_secret'];
        $this->api_url = "https://sendiio.com/api/v1/";
    }

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {

            $res = $this->curl('lists/email');
            $lists_data = json_decode($res, true);
            $lists = [];
            if (isset($lists_data['data'])) {
                foreach ($lists_data['data']['lists'] as $data) {
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
                'email_list_id' => $data['list_id'],
                'email' => $data['email'],
                'First_Name' => $data['first_name'],
                'Last_Name'  => $data['last_name']
            );

            // insert
            $res =  $this->curl('lists/subscribe/json',$contact,"POST");
            $response = json_decode($res);

            if($response->msg == "You were successfully subscribed")
            {
                return $this->successResponse();
            }


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
        if($method == 'GET')
        {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url
            ));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "token: ".$this->api_token,
                "secret:".$this->api_secret
            ));
        }

        if($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'token:' .$this->api_token,
                'secret:' .$this->api_secret,
            ));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        }
        // execute Curl
        $response = curl_exec($curl);
        // close the connection of Curl
        curl_close($curl);

        return $response;
    }
}