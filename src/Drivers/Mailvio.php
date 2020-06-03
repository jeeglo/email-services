<?php


namespace Jeeglo\EmailService\Drivers;


class Mailvio
{
    protected $api_key;

    public function __construct($credentials)
    {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://api.mailvio.com/v3/";
    }

    public function getLists()
    {
        $lists = [];
        $limit = 10;
        $offset = 0;
        try {
            $resp = $this->curl('contacts/lists', ['offset' => $offset, 'limit' => $limit],'GET');
            $lists_data = json_decode($resp, true);

          /*  $count = $lists_data['count'];
            $total_fetched_records = count($lists_data['lists']);
            if($count > $limit)
            {
               do {
                   $offset += $limit;
                   $resp = $this->curl('contacts/lists', ['offset' => $offset, 'limit' => $limit],'GET');
                   $lists_data = json_decode($resp, true);
                   $lists[] = $lists_data['lists'];
                   $total_fetched_records += count($lists_data['lists']);
               } while($total_fetched_records !== $count);
            }*/

            if($lists_data['count']> 0) {
                foreach ($lists_data['lists'] as $data) {
                    $lists[] = array(
                        'name' => $data['name'],
                        'id' => $data['id']
                    );
                }
                return $lists;

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
                'email' => $data['email'],
                'attributes' => [
                    'FIRSTNAME' => isset($data['first_name']) ? $data['first_name'] : '',
                    'LASTNAME'  => isset($data['last_name']) ? $data['last_name'] : '',
                ],
                'listIds' => [ intval($data['list_id'])],
            );

            // send curl request
            $response =  $this->curl('contacts',$contact,"POST");
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

    /**
     * Request Method
     * @return array for getList
     * @return array for addContact
     */
    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {
        $url = $this->api_url.$api_method;
        $curl = curl_init($url);
        $offset = isset( $data['offset']) ? $data['offset'] : '';
        $limit = isset( $data['limit']) ? $data['limit'] : '';
        if($method == 'GET')
        {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url."?limit=.'$limit'.&offset=".$offset,
            ));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'api-key:'.$this->api_key,
        ));

        if($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // execute Curl
        $response = curl_exec($curl);
        // close the connection of Curl
        curl_close($curl);

        return $response;
    }

}