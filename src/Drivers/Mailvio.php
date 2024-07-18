<?php


namespace Jeeglo\EmailService\Drivers;


class Mailvio
{
    protected $access_token;
    protected $redirect_url;
    protected $client_id;
    protected $credentials;
    private $api_url;

    public function __construct($credentials)
    {
        $this->credentials = $credentials;
        $this->access_token = (isset($credentials['access_token']) ? $credentials['access_token'] : null);
        $this->redirect_url = $credentials['redirect_uri'];
        $this->client_id = $credentials['client_id'];
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
                    $this->failedResponse();
                }

            } else {
                return ['error' => true];
            }

        } catch (\Exception $e) {
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
                "emailAddress" => $data['email'],
                "customFields" => [
                    'FIRSTNAME' => $data['first_name'],
                    'LASTNAME' => $data['last_name']
                ]
            );

            // send curl request
            $this->curl('group/'.$data['list_id'].'/subscriber',$contact,"POST");

            return $this->successResponse();

        } catch (\Exception $e) {
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
            'x-access-token:'.$this->access_token,
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
     * Call to Email service and get OAuth Url
     * @return [array]
     */
    public function connect()
    {
        // Make Call to Mailvio and return OAuth Url
        return ['url' => "https://mail.mailvio.com/oauth2/authorise?response_type=code&appid=$this->client_id&redirect_uri=$this->redirect_url"];
    }

    /**
     * Get response from Email Service after user Allow access to our application
     * @return [array]
     * @throws \Exception
     */
    public function getConnectData()
    {
        // If user has successfully allowed access to our application then we have code on callback
        if (isset($_GET['code'])) {

            try {
                $this->credentials['grant_type'] = 'authorization_code';
                $this->credentials['code'] = $_GET['code'];

                // Get Access token from OAuth
                $response = $this->curl('api/oauth/token', $this->credentials, $method = 'POST', $headers = []);

                $accessToken = null;
                if($response) {
                    $response = json_decode($response, true);
                    $accessToken = (isset( $response['access_token']) ?  $response['access_token'] : null);
                }


                // return access token
                return ['access_token' => $accessToken];

            } catch (\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }

        } else {
            // if no code found in Parameter, return failed response
            $this->failedResponse();
        }
    }

    public function getTags()
    {
        try {
            $resp = $this->curl('tags');

            if($resp) {

                $tags_data = json_decode($resp, true);

                $tags = [];
                foreach ($tags_data as $data) {
                    $tags[] = array(
                        'name' => $data['tagName'],
                        'id' => $data['id']
                    );
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
        $response = $this->getLists();

        if(!$response) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

    }

}