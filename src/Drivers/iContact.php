<?php

namespace Jeeglo\EmailService\Drivers;
use iContact\iContactApi as iContactApi;

class iContact 
{
    protected $app_id;
    protected $api_password;
    protected $api_username;
    protected $iContact;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->app_id = $credentials['appId'];
        $this->api_password = $credentials['apiPassword'];
        $this->api_username = $credentials['apiUsername'];
        
        iContactApi::getInstance()->setConfig(array(
            'appId'       => $this->app_id, 
            'apiPassword' => $this->api_password, 
            'apiUsername' => $this->api_username
        ));

        $this->iContact = iContactApi::getInstance();
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {

            $lists = $this->iContact->getLists();
            
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

            $contact = $this->iContact->addContact($data['email'], 'normal', null, (isset($data['first_name']) ? $data['first_name'] : null) , (isset($data['last_name']) ? $data['last_name'] : null));
                
            if($contact) {
                $this->iContact->subscribeContactToList($contact->contactId, $list_id, 'normal');
                return $this->successResponse();
            } else {
                // Grab the last raw response data
                throw new \Exception($this->iContact->getLastResponse(), 1);
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
                        'id' => $list->listId
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

    /**
     * iContact test credentials
     */
    public function verifyCredentials()
    {
        $response = $this->iContact->getLists();

        if(!$response) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

    }
}