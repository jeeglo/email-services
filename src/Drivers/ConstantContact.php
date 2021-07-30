<?php

namespace Jeeglo\EmailService\Drivers;
use Ctct\ConstantContact as ConstantContactAPI;
use Ctct\Exceptions\CtctException;
use Ctct\Components\Contacts\Contact;
use Ctct\Auth\CtctOAuth2;
use Ctct\Exceptions\OAuth2Exception;

class ConstantContact 
{
    protected $api_key;
    protected $secret_key;
    protected $access_token;
    protected $redirect_url;
    protected $cc;
    protected $oauth;

    public function __construct($credentials) {
        
        // @todo Throw exception if API key is not available
        $this->api_key = $credentials['api_key'];
        $this->secret_key = $credentials['secret_key'];
        $this->redirect_url = $credentials['redirect_url'];
        $this->access_token = (isset($credentials['access_token']) ? $credentials['access_token'] : null);
        $this->cc = new ConstantContactAPI($this->api_key);
        $this->oauth = new CtctOAuth2($this->api_key, $this->secret_key, $this->redirect_url);
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

            if(count($lists) > 0 && !$error) {
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
    public function addContact($data, $remove_tags = [], $add_tags = [])
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
     * Call to Email service and get OAuth Url
     * @return [array]
     */
    public function connect()
    {
        // Make Call to ConstantContact and return OAuth Url
        return ['url' => $this->oauth->getAuthorizationUrl()] ;
    }

    /**
     * Get response from Email Service after user Allow access to our application
     * @return [array]
     */
    public function getConnectData()
    {
        // If error found, return
        if (isset($_GET['error'])) {
            throw new \Exception($_GET['error']);
        }

        // If user has successfully allowed access to our application then we have code on callback
        if (isset($_GET['code'])) {
            
            try {
                // Get Access token from OAuth
                $accessToken = $this->oauth->getAccessToken($_GET['code']);
                $accessToken = $accessToken['access_token'];
                
                // return access token
                return ['access_token' => $accessToken];

            } catch (OAuth2Exception $ex) {
                throw new \Exception($ex->getMessage());
            }

        } else {
            // if no code found in Parameter, return failed response
            return $this->failedResponse();
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

    /**
     * ContantContact test credentials
     */
    public function verifyCredentials()
    {
        try {
            $this->cc->getLists($this->access_token);

            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);

        } catch (\Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }
}