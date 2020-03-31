<?php

namespace Jeeglo\EmailService\Drivers;

use OntraportAPI\Ontraport as OntraportAPI;
use OntraportAPI\ObjectType as ObjectType;

class Ontraport
{
    protected $app_id;
    protected $api_key;
    protected $ontraport;

    public function __construct($credentials) {

        // @todo Throw exception if API key is not available
        $this->app_id = $credentials['app_id'];
        $this->api_key = $credentials['api_key'];
        $this->ontraport = new OntraportAPI($this->app_id,$this->api_key);

    }   

    /**
     * fetch tags through API
     * @return array
     */
    public function getTags() {
        //fetch tags
        try {

            $requestParams = array(
                "objectID"   => ObjectType::TAG, // Object type ID: 14
            );

            $response = json_decode($this->ontraport->object()->retrieveMultiple($requestParams));
            $tags = $response->data;
            return $this->response($tags);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $addTags = [] ,$removeTags = [])
    {   
        // Add contacts params
        try {
        
            $requestParams = [
                 "objectID"  => ObjectType::CONTACT, // Object type ID: 
                 "firstname" => (isset($data['first_name']) ? $data['first_name'] : null),
                 "lastname"  => (isset($data['last_name']) ? $data['last_name'] : null),
                 "email"     => $data['email']
            ];

            $response = json_decode($this->ontraport->object()->saveOrUpdate($requestParams));

            if (!empty($response)) {
                
                $data['contact_id'] = isset($response->data->id) ? $response->data->id : (isset($response->data->attrs->id) ? $response->data->attrs->id : null);

                $this->sync($data, $removeTags, $addTags);

                return $this->successResponse();
            }    
           
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

/**
     * [sync add and remove tags]
     * @return [array] [Success true]
     */
    private function sync($data, $addTags,$removeTags) {
        
        try {

            // Remove tags
            if(is_array($removeTags) && count($removeTags) > 0 ) {

                $params = [
                    "objectID" => ObjectType::CONTACT,
                    "ids"      => $data['contact_id'],
                    "remove_list" => implode(',', $removeTags)
                ];

                $this->ontraport->object()->removeTag($params);                
            }

             //Add 

            if(is_array($addTags) && count($addTags) > 0) {

                $params = [
                    "objectID" => ObjectType::CONTACT,
                    "ids"      => $data['contact_id'],
                    "add_list" => implode(',', $addTags)
                ];

                $this->ontraport->object()->addTag($params);
            }

        } catch (\Exception $e) {
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

            if(is_array($data) && count($data) > 0) {
                foreach ($data as $item) {
                    $response[] = [
                        'id' => $item->tag_id,
                        'name' => $item->tag_name
                    ];
                }
            }
        } catch (\Exception $e) {
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
        throw new \Exception("Something went wrong !");
    }
}