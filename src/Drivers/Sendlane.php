<?php

namespace Jeeglo\EmailService\Drivers;

class Sendlane 
{
    protected $api_key;
    protected $hash;
    protected $domain;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->hash = $credentials['hash'];
        $this->domain = $credentials['domain'];
        
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            
            $lists = $this->apiCall('lists', [ 'limit' => 1000 ]);

            return $this->response($lists, 'list');
        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    /**
     * fetch tags through API
     * @return array
     */
    public function getTags() {
        try {
            $tags = $this->apiCall('tags', [ 'limit' => 1000 ]);

            return $this->response($tags, 'tag');
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $removeTags, $addTags)
    {   
        // @todo throw exception if email field is empty or list id or not available
        try {
            
            // set param fields
            $params = array(
                'list_id' => $data['list_id'],
                'first_name' => (isset($data['first_name']) ? $data['first_name'] : null),
                'last_name' => (isset($data['last_name']) ? $data['last_name'] : null),
                'email' => $data['email'],
            );

            if(is_array($addTags) && count($addTags) > 0) {
                $params['tag_ids'] = implode(',', $addTags);
            }

            $this->apiCall('list-subscriber-add', $params);

            $this->sync($data, $removeTags, $addTags);

            return $this->successResponse();

        } catch (Exception $e) { // Catch any exceptions
            throw new \Exception($e->getMessage(), 1);
            
        }
    }

/**
     * [sync add and remove tags]
     * @return [array] [Success true]
     */
    private function sync($data, $removeTags, $addTags) {
        
        try {
            // Remove the tags
            if(is_array($addTags) && count($addTags) > 0) {

                $params = [
                    'email' => $data['email'],
                    'tag_ids' => implode(',', $addTags)
                ];

                $this->apiCall('tag-subscriber-add', $params);
            }

            //Add tags
            if(is_array($removeTags) && count($removeTags) > 0 ) {             

                $params = [
                    'email' => $data['email'],
                    'tag_ids' => implode(',', $removeTags)
                ];

                $this->apiCall('tag-subscriber-remove', $params);
            }       

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    /**
     * [response description]
     * @return  array [return response as key value]
     */
    private function response($data, $prefix)
    {
        $response = [];

        try {

            if(is_array($data) && count($data) > 0) {
                foreach ($data as $item) {
                    $response[] = [
                        'name' => $item->{$prefix . '_name'},
                        'id' => $item->{$prefix . '_id'}
                    ];
                }
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

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
     * [failedResponse description]
     * @return [array] [Success false]
     */
    private function failedResponse()
    {
        throw new \Exception('Something went wrong!');
    }

    private function apiCall($apiPath, $params = []) {

        try {
            $params['api'] = $this->api_key;
            $params['hash'] = $this->hash;

            $apiBasePath = 'https://'.$this->domain.'.sendlane.com/api/v1/';

            $ch = curl_init($apiBasePath . $apiPath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
            ));

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

            // execute Curl
            $response = curl_exec($ch);


            // close the connection of Curl
            curl_close($ch);

            $decoded_response = json_decode($response);

            //comment out becz of different response of getTags and getLists
            // if(!isset($decoded_response->success)) {
            //     throw new \Exception('Last operation was not successfull', 1);
            // }

            return $decoded_response;

        } catch (\Exception $e) {
            throw new \Exception($e, 1);
        }
    }
    /**
     * SandLane test credentials
     */
    public function verifyCredentials()
    {
        $response = $this->getLists();

        $response = json_decode($response);

        if(isset($response->errors)) {
            return json_encode(['error' => 1, 'message' => 'Api key not valid']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }
    }
}