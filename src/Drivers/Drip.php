<?php

namespace Jeeglo\EmailService\Drivers;
use DrewM\Drip\Drip as DripApi;
use DrewM\Drip\Dataset;

class Drip 
{
    protected $api_token;
    protected $account_id;
    protected $drip;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_token = $credentials['api_token'];
        $this->account_id = $credentials['account_id'];
        
        $this->drip = new DripApi($this->api_token, $this->account_id);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
             $lists['lists'] = $this->drip->get('campaigns')->campaigns;

            return $this->response($lists);
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
             $tags['tags'] = $this->drip->get('tags')->tags;
            
             return $this->response($tags);
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
             
            $list_id = $data['list_id']; 
            
            $data_subscriber = new Dataset('subscribers', [
                    'email' => $data['email'],
                    'custom_fields' => [
                        'first_name' => (isset($data['first_name']) ? $data['first_name'] : null),
                        'last_name' => (isset($data['last_name']) ? $data['last_name'] : null),
                    ],
            ]);
                
            $response = $this->drip->post('campaigns/'.$list_id.'/subscribers', $data_subscriber);

            if(isset($response->errors)) {
                throw new \Exception($response->errors[0]['message'], 1);
            } else {
                $this->sync($data,$removeTags,$addTags);

                return $this->successResponse();
            }    

        } catch (Exception $e) { // Catch any exceptions
            throw new \Exception($e->getMessage(), 1);
            
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
                // return $data['lists'];
                foreach ($data['lists'] as $list) {
                    $response[] = array(
                        'name' => $list['name'],
                        'id' => $list['id']
                    );
                }
            }

            if(!empty($data['tags'])) {

                foreach ($data['tags'] as $tag) {
                    $response[] = array(
                        'name' => $tag,
                    );
                }
                    
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    private function sync($data, $removeTags, $addTags){

        try {

            // Remove Tags
            if(is_array($removeTags) && count($removeTags) > 0) {

                foreach( $removeTags as $tag) {

                    $response = $this->drip->delete("subscribers/{$data['email']}/tags/{$tag}");
                }
            }

            // Add Tags
            if(is_array($addTags) && count($addTags) > 0) {
                
                foreach($addTags as $tag) {
                    
                    $data_tag = new Dataset('tags', [
                        'email' => $data['email'], 
                        'tag' => $tag 
                    ]);

                    $response = $this->drip->post('tags', $data_tag);

                }
                
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

    /**
     * Drip test credentials
     */
    public function verifyCredentials()
    {
        $response = $this->getLists();

        if(!$response) {
            return json_encode(['error' => 1, 'message' => 'Api key is not valid']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

    }
}