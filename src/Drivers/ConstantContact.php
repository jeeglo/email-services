<?php

namespace Jeeglo\EmailService\Drivers;
use Ctct\ConstantContact;

class ConstantContact 
{
    protected $api_key;
    protected $access_token;
    protected $cc;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->access_token = $credentials['access_token'];
        $cc = new ConstantContact($this->api_key);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {
            $lists = $this->cc->getLists($this->access_token);

            $error = isset($lists->error) ? true : false; 

            if(count($list_data) > 0 && !$error) {
                return $this->response($lists);
            } else {
                throw new \Exception("Error Processing Request", 1);
            }

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
            
            $response = $this->cc->getContactByEmail( $this->access_token , $data['email'] );
            
            // If contact not found, add new one
            if (empty($response->results)) {
                $contact = new Contact();
                $contact->addEmail( $data['email'] );
                $contact->addList( $data['list_id'] );
                $contact->first_name = $data['first_name'] ;
                $contact->last_name = $data['last_name'] ;
                $returnContact = $this->cc->addContact( $this->access_token, $contact, true);
                return $this->successResponse();
            } else {
                // If contact found, update entry
                $contact = $response->results[0];
                $contact->addList( $data['list_id'] );
                $contact->first_name = $data['first_name'] ;
                $contact->last_name = $data['last_name'] ;
                $returnContact = $this->cc->updateContact( $this->access_token , $contact, true);
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
                    $response[] = [
                        'name' => $list->name,
                        'id' => $list->id
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
}