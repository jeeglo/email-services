<?php

namespace Jeeglo\EmailService\Drivers;
use ActiveCampaign as ActiveCampaignAPI;

class ActiveCampaign 
{
    protected $api_url;
    protected $api_key;
    protected $activeCampaign;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_url = $credentials['api_url'];
        $this->api_key = $credentials['api_key'];
        $this->activeCampaign = new ActiveCampaignAPI($this->api_url, $this->api_key);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            $data = ['ids' => 'all'];
            $lists = $this->activeCampaign->api("list/list", $data);
            
            return $this->response($lists);
        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data)
    {   
        // @todo throw exception if email field is empty or list id or not available
        try {
            
            $list_id = $data['list_id'];    

            $contact = array(
                "email"       => $data['email'],
                "first_name"  => (isset($data['first_name']) ? $data['first_name'] : null),
                "last_name"   => (isset($data['last_name']) ? $data['last_name'] : null),
                "p[$list_id]" => $list_id,
                "status[$list_id]" => 1,
            );

            $contact_sync = $this->activeCampaign->api("contact/sync", $contact);
            if ((int)$contact_sync->success) {
                $contact_id = (int)$contact_sync->subscriber_id;
                return $this->successResponse();
            } else {
                return $this->failedResponse();
            }
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
        $response = [];

        try {
            if(!empty($lists)) {

                foreach ($lists as $key => $value) {
                    if( (int)$key || $key == '0' ) {
                        $response[] = [
                            'name' => $value->name,
                            'id' => $value->id,
                        ];
                    }
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
}