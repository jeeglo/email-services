<?php


namespace Jeeglo\EmailService\Drivers;

class Grawt
{
    protected $api_key;
    private $api_url;

    public function __construct($credentials)
    {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://grawt.app/api/v1/";
    }

    public function getLists()
    {
    }

    /**
     * [addContact Add contact to list through API]
     * @return void [return success or fail]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            // set param fields
            $contact = array(
                "email" => $data['email'],
                "fields" => [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name']
                ],
                'add_tags' => $add_tags,
                'remove_tags' => $remove_tags
            );

            // send curl request
            $response = $this->curl('leads/sync',$contact,"POST");

            if($response) {
                $decoded_response = json_decode($response, true);

                if(isset($decoded_response['success'])) {
                    return $this->successResponse();
                }

                return $this->failedResponse();
            }

        } catch (\Exception $e) {
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
     * Generic method for API calls
     * @param $api_method
     * @param array $data
     * @param string $method
     * @param array $headers
     * @return bool|string
     * @throws \Exception
     */
    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {
        $url = $this->api_url . $api_method;

        // Append query parameters for GET requests
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $curl = curl_init($url);

        // Common CURL options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json',
            'x-api-key: ' . $this->api_key,
        ], $headers));

        // Additional options for POST requests
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);

        // Handle CURL errors
        if (curl_errno($curl)) {
            $error_message = curl_error($curl);
            curl_close($curl);
            throw new \Exception('CURL Error: ' . $error_message);
        }

        curl_close($curl);

        return $response;
    }

    /**
     * Get tags
     * @return array
     * @throws \Exception
     */
    public function getTags()
    {
        try {
            $resp = $this->curl('tags');

            if($resp) {

                $tags_data = json_decode($resp, true);

                $tags = [];
                if(isset($tags_data['tags'])) {

                    foreach ($tags_data['tags'] as $data) {
                        $tags[] = array(
                            'name' => $data['name'],
                            'id' => $data['id']
                        );
                    }
                }

                return $tags;
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Mailvio test credentials
     */
    public function verifyCredentials()
    {
        $response = $this->getTags();

        if(!$response) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

    }

}