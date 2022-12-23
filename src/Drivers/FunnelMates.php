<?php
namespace Jeeglo\EmailService\Drivers;


class FunnelMates
{
    protected $api_key;

    public function __construct($credentials)
    {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://funnelmates.com/api/";
    }

    /**
     * @return array|bool[]|void
     * @throws \Exception
     * [Get Lists Api]
     */
    public function getLists()
    {
        try {
            $resp = $this->curl('fi?token='.$this->api_key);
            $funnels_list = json_decode($resp, true);

            if($funnels_list['status'] == 1) {
                // Check if the count is great than 0
                if(!empty($funnels_list['data']) && count($funnels_list['data']) > 0) {
                    $lists = [];
                    foreach ($funnels_list['data'] as $data) {
                        $lists[] = array(
                            'id' => $data['list_id'],
                            'name' => $data['post_title'],
                        );
                    }
                    return $lists;
                } else {
                    return ['error' => true];
                }
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * [Add contact to list through API]
     * @return array [return success or fail]
     */
    public function addContact($data)
    {
        try {
            $data['token'] = $this->api_key;
            // send curl request
            $this->curl('su',$data,"POST");

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
     * @param $api_method
     * @param $data
     * @param $method
     * @param $headers
     * @return bool|string
     * Curl Request
     */
    private function curl($api_method, $data = [],$method = 'GET', $headers = [])
    {
        $url = $this->api_url.$api_method;

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
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
     * FunnelMates test credentials
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
