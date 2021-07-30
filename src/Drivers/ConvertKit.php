<?php

namespace Jeeglo\EmailService\Drivers;

class ConvertKit 
{
    protected $api_key;
    protected $api_secret;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->api_secret = (isset($credentials['api_secret']) ? $credentials['api_secret'] : null);
        
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {

            $apiPath = 'forms?api_key=' . $this->api_key;
            $response = $this->apiCall($apiPath);

            return $this->response($response);

        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    /**
     * [getTags Fetch Tags through API]
     * @return array
     */
    public function getTags()
    {
        try {

            $apiPath = 'tags?api_key=' . $this->api_key;
            $response = $this->apiCall($apiPath);

            return $this->response($response);

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
                'api_key' => $this->api_key,
                'email' => $data['email'],
                'first_name' => (isset($data['first_name']) ? $data['first_name'] : null) ,

                'fields' => array(
                    'last_name' => (isset($data['last_name']) ? $data['last_name'] : null)
                )
            );

            $apiPath = 'forms/' . $data['list_id'] . '/subscribe';

            $response = $this->apiCall($apiPath, $params);

            $this->sync($data, $removeTags, $addTags);

            return $this->successResponse();

        } catch (Exception $e) { // Catch any exceptions
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

            $type = !empty($data->forms) ? 'forms' : (!empty($data->tags) ? 'tags' : null);

            if($type) {
                foreach ($data->$type as $value) {
                    $response[] = array(
                        'name' => $value->name,
                        'id' => $value->id
                    );
                }
            }

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    
    /**
     * [sync add and remove tags]
     * @return [array] [Success true]
     */
    private function sync($data, $removeTags, $addTags) {
        
        try {
            // Remove the tags
            if(is_array($removeTags) && count($removeTags) > 0) {
                foreach($removeTags as $removeTag) {
                    // Set param fields
                    $params = array(
                        'api_secret' => $this->api_secret,
                        'email' => $data['email']
                    );

                    $apiPath = 'tags/'.$removeTag.'/unsubscribe';
                    $response = $this->apiCall($apiPath, $params);
                }
            }

            if(is_array($addTags) && count($addTags) > 0 ) {             

                    // Set param fields
                    $tag_id = $addTags[0];
                    $params = array(
                        'api_key' => $this->api_key,
                        'email' => $data['email'],
                        'tags' => $addTags
                    );

                    $apiPath = 'tags/'.$tag_id.'/subscribe';
                    $response = $this->apiCall($apiPath, $params);
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

    private function apiCall($apiPath, $params = false) {

        try {
            $apiBasePath = 'https://api.convertkit.com/v3/';

            $ch = curl_init($apiBasePath . $apiPath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
            ));

            if($params) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }

            // execute Curl
            $response = curl_exec($ch);

            // close the connection of Curl
            curl_close($ch);

            $decoded_response = json_decode($response);

            if(isset($decoded_response->error)) {
                throw new \Exception($decoded_response->message, 1);
            }

            return $decoded_response;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Convert Kit test credentials
     */
    public function verifyCredentials()
    {
        try {
            $apiPath = 'forms?api_key=' . $this->api_key;
            $response = $this->apiCall($apiPath);

            if(isset($response->forms)) {
                return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
            }

        } catch (\Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }
}