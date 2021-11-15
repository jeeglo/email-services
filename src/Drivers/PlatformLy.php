<?php

namespace Jeeglo\EmailService\Drivers;

class PlatformLy
{
    protected $api_key;
    /**
     * Prepare the constructor and set the  values accordingly
     * SendFox constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://api.platform.ly";
    }

    /**
     * Get all the list from service
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {
        try {

            // set param fields to send curl and get all projects(list)
            $params = array(
                'api_key' => $this->api_key,
                'action' => 'list_projects',
                'value' => []
            );

            $lists['lists'] = $this->curl($params);
            if(isset($lists['lists']->status)) {
                return ['error' => true,'message'=>$lists['lists']->message];
            }

            return $this->response($lists);

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * fetch tags through API
     * @return array
     */
    public function getTags($data = []) {
        try {

            // set param fields to send curl and get Tags
            $params = array(
                'api_key' => $this->api_key,
                'action' => 'list_tags',
                'value' => ["project_id" => $data['projectId']]
            );

            $tags['tags'] = $this->curl($params);

            // if error show status field
            if(isset($tags['tags']->status)) {
                return ['error' => true,'message'=>$tags['tags']->message];
            }

            return $this->response($tags);

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * [response description]
     * @return  array [return response as key value]
     */
    private function response($data)
    {
        $response = [];

        try {
            if(!empty($data['lists'])) {
                foreach ($data['lists'] as $key => $list) {
                    if( (int)$key || $key == '0' ) {
                        $response[] = array(
                            'name' => $list->name,
                            'id' => $list->id
                        );
                    }
                }
            }

            if(!empty($data['tags'])) {

                foreach($data['tags'] as $tag){

                    $response[] = [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'project_id' => $tag->project_id
                    ];
                }
            }

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $removeTags, $addTags)
    {
        try {

            // set param fields
            $params = array(
                'api_key' => $this->api_key,
                'action' => 'add_contact',
                'value' => [
                    "project_id" => $data['projectId'],
                    "email" => $data['email'],
                    'first_name' => (isset($data['first_name']) ? $data['first_name'] : null),
                    'last_name' => (isset($data['last_name']) ? $data['last_name'] : null)
                ]
            );

            $addContact = $this->curl($params);

            $this->sync($addContact, $removeTags, $addTags);

            return $this->successResponse();

        } catch (Exception $e) {
            // Catch any exceptions
            throw new \Exception($e->getMessage(), 1);

        }
    }

    /**
     * [sync add and remove tags]
     * @return [array] [Success true]
     */
    private function sync($data, $removeTags, $addTags) {

        try {
            $arrData = json_decode(json_encode($data), TRUE);
            // Add tags
            if(is_array($addTags) && count($addTags) > 0) {
                $params = [
                    'api_key' => $this->api_key,
                    'action' => 'contact_tag_add',
                    'value' => [
                        "tag" => implode(',', $addTags),
                        "contact_id" => $arrData['data']['cc_id']
                    ],
                ];

                $this->curl($params);
            }
            //Remove tags
            if(is_array($removeTags) && count($removeTags) > 0 ) {

                $params = [
                    'api_key' => $this->api_key,
                    'action' => 'contact_tag_remove',
                    'value' => [
                        "tag" => implode(',', $removeTags),
                        "contact_id" => $arrData['data']['cc_id']
                    ],
                ];

                $this->curl($params);
            }

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Request Method
     * @return array for getList
     * @return array for addContact
     */
    private function curl($params = [])
    {
        // Initialize curl
        $ch = curl_init();
        // Set the API call url
        $url = $this->api_url;

        // set curl setting params
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded"
        ));

        // execute Curl
        $response = curl_exec($ch);
        curl_close($ch);

        $decoded_response = json_decode($response);
        return $decoded_response;
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
     *  Moosend test credentials
     */
    public function verifyCredentials()
    {
        $response = $this->getLists();

        if(isset($response['error']) && $response['error']) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection Succeeded.']);
        }
    }
}
