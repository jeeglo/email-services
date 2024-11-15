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
        $page = 1; // Start with the first page

        try {
            while (true) {
                // Send curl call to get the current page of lists
                $response = $this->curl('lists', ['page' => $page, 'limit' => 10], 'GET');

                // Decode the JSON response
                $listsData = json_decode($response, true);

                // Check if we received valid data
                if (is_array($listsData) && isset($listsData['data'])) {
                    // Append the fetched lists to the main $lists array
                    foreach ($listsData['data'] as $data) {
                        $lists[] = [
                            'name' => $data['name'],
                            'id' => $data['id']
                        ];
                    }

                    // Check if there's a next page
                    if (isset($listsData['next_page_url']) && $listsData['next_page_url'] !== null) {
                        $page++;
                    } else {
                        // No more pages, exit the loop
                        break;
                    }
                } else {
                    // If no valid data is returned, exit the loop
                    break;
                }
            }

            return $lists;

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
                CURLOPT_URL => $url . '?' . http_build_query($data),
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

    /**
     * Send Fox test credentials
     */
    public function verifyCredentials()
    {

        $response = $this->getLists();

        if(isset($response['error']) && $response['error']) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }
    }

}