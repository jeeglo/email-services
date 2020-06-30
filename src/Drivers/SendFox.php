<?php

namespace Jeeglo\EmailService\Drivers;

class SendFox
{
    protected $access_token;

    /**
     * Prepare the constructor and set the  values accordingly
     * SendFox constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->access_token = $credentials['access_token'];
        $this->api_url = "https://api.sendfox.com/";
    }

    /**
     * Get all the list from service
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {
        $lists = [];
        $limit = 10000;
        $offset = 0;

        try {
            // Send curl call to get the lists
            $response = $this->curl('lists', ['offset' => $offset, 'limit' => $limit],'GET');

            // Decoded the json response
            $lists_data = json_decode($response, true);

            // if we found the lists data then we need to make the response data
            if(is_array($lists_data) && count($lists_data) > 0) {
                if(isset($lists_data['data'])) {
                    foreach ($lists_data['data'] as $data) {
                        $lists[] = array(
                            'name' => $data['name'],
                            'id' => $data['id']
                        );
                    }
                } else {
                    return ['error' => true];
                }

                // return the lists data
                return $lists;

            } else {
                return ['error' => true];
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Add contact in email service
     * [addContact Add contact to list through API]
     * @return array [return success or fail]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            // set param fields to send email service
            $contact = array(
                'email' => $data['email'],
                'first_name' => isset($data['first_name']) ? $data['first_name'] : '',
                'last_name'  => isset($data['last_name']) ? $data['last_name'] : '',
                'lists' => [ intval($data['list_id'])],
            );

            // send curl request to add contact
            $response =  $this->curl('contacts', $contact,"POST");

            // if we get the response
            if($response) {

                // decoded the response
                $response = json_decode($response, true);

                // if user has been added successfully
                if(isset($response['id'])) {
                    return $this->successResponse();
                } else {
                    return ['error' => true];
                }
            }

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
        // Set the API call url
        $url = $this->api_url.$api_method;
        $offset = isset( $data['offset']) ? $data['offset'] : '';
        $limit = isset( $data['limit']) ? $data['limit'] : '';

        // Initialize curl
        $curl = curl_init($url);

        // set curl setting params
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->access_token,
        ));

        if($method == 'GET')
        {
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url."?limit=.'$limit'.&offset=".$offset,
            ));
        }

        if($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_POST, 1);
        }

        // execute Curl
        $response = curl_exec($curl);

        // close the connection of Curl
        curl_close($curl);

        // return the response
        return $response;
    }

}