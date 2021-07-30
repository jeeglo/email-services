<?php

namespace Jeeglo\EmailService\Drivers;
use \DrewM\MailChimp\MailChimp as MailchimpAPI;

class Mailchimp 
{
    protected $api_key;
    protected $mailchimp;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->mailchimp = new MailchimpAPI($this->api_key);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            $lists = $this->mailchimp->get('lists',  array('count' => 1000));

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
        // @todo throw exception if email field is empty or not available
        try {
            
            $list_id = $data['list_id'];

            // Call to Maiclhimp API
            $result = $this->mailchimp->post("lists/$list_id/members", [
                'email_address' => $data['email'],
                'status'        => 'subscribed',
                'merge_fields' => [
                    'FNAME' => isset($data['first_name']) ? $data['first_name'] : '',
                    'LNAME' => isset($data['last_name']) ? $data['last_name'] : ''
                ]
            ]);

            // Check result from API and return response accordingly
            if($result['status'] == 'subscribed') {
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

                if(isset($lists['lists'])) {
                    foreach ($lists['lists'] as $list) {
                        $response[] = array(
                            'name' => $list['name'],
                            'id' => $list['id']
                        );
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
        throw new \Exception($this->mailchimp->getLastError(), 1);
    }

    /**
     * Mailchimp test credentials
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