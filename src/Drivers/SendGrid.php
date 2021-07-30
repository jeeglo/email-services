<?php

namespace Jeeglo\EmailService\Drivers;

class SendGrid
{
    /**
     * define teh API Key
     * @var string
     */
    protected $api_key;

    /**
     * define teh API Base URL
     * @var string
     */
    protected $api_url;

    /**
     * Set the construct value
     * CampaignRefinery constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://api.sendgrid.com/v3/marketing/";
    }

    /**
     * Get the Forms through API (it's called Forms in CR instead of Lists)
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {
        try {
            // Initialize the lists array to append
            $lists = [];

            //  send the call to API to fetch
            $res = $this->curl('lists?page_size=1000');

            if($res) {
                // decode the response
                $list_data = json_decode($res, true);

                // if we found the forms key data then append in the array
                if (isset($list_data['result']) && !empty($list_data['result'])) {
                    foreach ($list_data['result'] as $data) {

                        $lists[] = array(
                            'id' => $data['id'],
                            'name' => $data['name']
                        );
                    }

                    return $lists;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return array [return success or fail]
     * @throws \Exception
     */
    public function addContact($data,  $remove_tags = [] , $add_tags = [])
    {
        try {

            $contact_data = array(
                'list_ids' => array($data['list_id']),
                'contacts' => array($data)
            );

            // send call to API to add contact
            $res =  $this->curl('contacts', $contact_data, "PUT");
            $res = json_decode($res);

            if(isset($res->job_id)) {
                $this->successResponse();
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Request Method
     * @return array for getList
     * @return array for addContact
     */
    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {

        // set the url
        $url = $this->api_url.$api_method;

        // initialize the curl
        $curl = curl_init($url);

        // set the curl To call the api
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $this->api_key",
                "content-type: application/json"
            ),
            CURLOPT_TIMEOUT => 30,
        ));

        // set the curl PUT params if request type is PUT
        if($method == "PUT") {
            curl_setopt_array($curl, array(
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => json_encode($data),

            ));
        }

        // execute Curl
        $response = curl_exec($curl);

        // close the connection of Curl
        curl_close($curl);

        // return the response
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
     * SendGrid test credentials
     */
    public function verifyCredentials()
    {

        $response = $this->curl('lists?page_size=1000');

        $response = json_decode($response);

        if(isset($response->errors)) {
            return json_encode(['error' => 1, 'message' => 'Api key not valid']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }
    }
}