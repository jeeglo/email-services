<?php

namespace Jeeglo\EmailService\Drivers;
use MailerLiteApi\MailerLite as MailerliteApi;
use MailerLiteApi\Api\Groups;

class Mailerlite 
{
    protected $api_key;
    protected $mailerlite;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        
        $this->mailerlite = new MailerLiteApi($this->api_key);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            $lists = $this->mailerlite->groups()->orderBy('name', 'ASC')->get();

            if(empty($lists) || !count($lists) ) {
                return $this->failedResponse();
            }

            return $this->response($lists);
        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {   
        // @todo throw exception if email field is empty or list id or not available
        try {
            
            $list_id = $data['list_id'];    

            $subscriber = [
                'type' => 'subscribed',
                'email' => $data['email'],
                'name' => (isset($data['first_name']) ? $data['first_name'] : null) .' '. (isset($data['last_name']) ? $data['last_name'] : null )
            ];

            $result = $this->mailerlite->groups()->addSubscriber($list_id, $subscriber);    

            if(isset($result->error)) {
                throw new \Exception($result->error->message, 1);
            } else {
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
    private function response($lists)
    {
        $response = [];

        try {
            if(!empty($lists)) {

                foreach ($lists as $list) {
                    $response[] = array(
                        'name' => $list->name,
                        'id' => $list->id
                    );
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