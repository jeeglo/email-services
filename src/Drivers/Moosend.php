<?php

namespace Jeeglo\EmailService\Drivers;

class Moosend
{
    protected $api_key;
    /**
     * Prepare the constructor and set the  values accordingly
     * SendFox constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://api.moosend.com/v3/";
    }

    /**
     * Get all the list from service
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {
        $lists = [];

        try {
            // Send curl call to get the lists
            $response = $this->curl('lists.json',  'GET');

            // Decoded the json response
            $lists_data = json_decode($response, true);

            // if we found the lists data then we need to make the response data
            if(is_array($lists_data["Context"]["MailingLists"]) && count($lists_data) > 0) {
                foreach ($lists_data["Context"]["MailingLists"] as $data) {
                    $lists[] = array(
                        'name' => $data["Name"],
                        'id' => $data['ID']
                    );
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
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data)
    {
        try {
            // set param fields to send email service
            $contact = array(
                    'Email' => $data['email'],
                    'Name' => (isset($data['first_name']) ? $data['first_name'] : null).' '.(isset($data['last_name']) ? $data['last_name'] : null),
                    'MailingListId' => $data['list_id']
            );

            // send curl request to add contact
            $response =  $this->curl("subscribers/".$contact["MailingListId"]."/subscribe.json", $contact,"POST");

            // if we get the response
            if($response) {
                // decoded the response
                $response = json_decode($response, true);
                // if user has been added successfully
                if(isset($response['Context']['ID'])) {
                    // Success Message after add contact
                    return $this->successResponse();
                } else {
                    // Return False on fail to addContact
                    return ['error' => true];
                }
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
        // Initialize curl
        $ch = curl_init();

        // Set the API call url
        $url = $this->api_url.$api_method.'?apikey='.$this->api_key;

        // set curl setting params
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if($method == 'GET')
        {
            curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
            ));
        }

        if($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Accept: application/json"
            ));
        }

        // execute Curl
        $response = curl_exec($ch);
        curl_close($ch);

        // return the response
        return $response;
    }
}
