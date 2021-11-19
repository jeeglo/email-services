<?php


namespace Jeeglo\EmailService\Drivers;


class Mailvio
{
    protected $api_key;

    public function __construct($credentials)
    {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://apiv2.mailvio.com/";
    }

    public function getLists()
    {
        try {
            $resp = $this->curl('group');
            $lists_data = json_decode($resp, true);
            // Getting count if count node is set
            $count = (isset($lists_data['countTotal']) ? $lists_data['countTotal'] : 0);

            // Check if the count is great than 0
            if($count > 0) {

                $lists = [];
                if (isset($lists_data['Groups'])) {
                    foreach ($lists_data['Groups'] as $data) {
                        $lists[] = array(
                            'name' => $data['groupName'],
                            'id' => $data['id']
                        );
                    }
                    return $lists;

                } else {
                    return $this->failedResponse();
                }

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
                "emailAddress" => $data['email']
            );

            // send curl request
            $this->curl('group/'.$data['list_id'].'/subscriber',$contact,"POST");

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


    private function curl($api_method, $data = [],$method = 'GET', $headers = [])
    {
        $url = $this->api_url.$api_method;

        $curl = curl_init($url);
        if($method == 'GET') {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
            ));
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'x-access-token:'.$this->api_key,
        ));

        if($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }


        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * Mailvio test credentials
     */
    public function verifyCredentials()
    {
        $response = $this->getLists();

        if(!$response) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

    }

}