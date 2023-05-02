<?php

namespace Jeeglo\EmailService\Drivers;

class Acumbamail
{
    /**
     * define teh API Key
     * @var string
     */
    protected $auth_token;
    protected $accumbamail;

    /**
     * Set the construct value
     * SendInBlue constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->auth_token = $credentials['auth_token'];
    }

    /**
     * Get the List through API
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {
        try {
            $endpoint = "getLists";
            $lists = $this->curlRequest($endpoint);

            if(empty($lists) || !count($lists) ) {
                return ['error' => true];
            }

            return $this->response($lists);
        } catch (Exception $e) {
            echo $e->getMessage();
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * [addContact Add contact to list through API]
     * @return array [return success or fail]
     * @throws \Exception
     */
    public function addContact($data)
    {
        $contact_data = [
            'name' => $data['first_name'].' '.$data['last_name'],
            'email' => $data['email']
        ];

        try {
            $endpoint = "addSubscriber";
            $merge_fields_send = array();

            foreach (array_keys($contact_data) as $merge_field) {
                $merge_fields_send['merge_fields[' . $merge_field . ']'] = $contact_data[$merge_field];
            }

            $api_data = array(
                'list_id' => $data['list_id'],
                'double_optin' => '',
                'welcome_email' => '',
            );

            $merged_data = array_merge($api_data, $merge_fields_send);

            return $this->curlRequest($endpoint, $merged_data);

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * SendInBlue test credentials
     */
    public function verifyCredentials()
    {

        $response = $this->getLists();

        if(empty($response)) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your Api Key.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection Succeeded.']);
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

                foreach ($lists as $key => $list) {
                    $response[] = array(
                        'id' => $key,
                        'name' => $list['name']
                    );
                }
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    /**
     * Generic curl request for Acumbamail API call
     * @param $request
     * @param array $data
     * @return array|bool|mixed|string
     */
    function curlRequest($request, $data = array())
    {
        $url = "https://acumbamail.com/api/1/" . $request . '/';

        $fields = array(
            'auth_token' => $this->auth_token,
            'response_type' => 'json',
        );

        if (count($data) != 0) {
            $fields = array_merge($fields, $data);
        }

        $postdata = http_build_query($fields);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        if (is_array($json)) {
            return $json;
        }

        return $response;
    }
}
