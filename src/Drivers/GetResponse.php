<?php

namespace Jeeglo\EmailService\Drivers;
use GetResponse as GetResponseAPI;

class GetResponse 
{
    protected $api_key;
    protected $getResponse;

    public function __construct($credentials) {
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->getResponse = new GetResponseAPI($this->api_key);
    }   

    /**
     * [getLists Fetch List through API]
     * @return array
     */
    public function getLists()
    {
        try {

            $lists = $this->getResponse->getCampaigns();

            return $this->response($lists);
        } catch (Exception $e) {
           throw new \Exception($e->getMessage());
        }
    }

    // /**
    //  * [addContact Add contact to list through API]
    //  * @return string [return success or fail]
    //  */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {   
        // @todo throw exception if email field is empty or list id or Firstname or last name not available
        try {
            
            $list_id = $data['list_id'];    

            $response = $this->getResponse->addContact(array(
                'name'              => (isset($data['first_name']) ? $data['first_name'] : null) . ' '. (isset($data['last_name']) ? $data['last_name'] : null),
                'email'             => $data['email'],
                'dayOfCycle'        => 0,
                'campaign'          => array('campaignId' => $list_id)
            ));

            if(isset($response->httpStatus) && ($response->httpStatus == 400 || $response->httpStatus == 409)) {
                throw new \Exception($response->message, 1);
            } else {
                return $this->successResponse();
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

                $error = isset($lists->httpStatus) ? true : false;

                if(!$error) {

                    foreach ($lists as $list_id => $list) {
                        $response[] = [
                            'name' => $list->name,
                            'id' => $list->campaignId
                        ];
                    }

                } elseif (isset($lists->message)) {
                    throw new \Exception($lists->message);
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
     * Mailer lite test credentials
     */
    public function verifyCredentials()
    {

        $responce = $this->getResponse->getCampaigns();

        if(isset($responce->httpStatus) && $responce->httpStatus === 400) {
            return json_encode(['error' => 1, 'message' => $responce->codeDescription]);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

    }
}