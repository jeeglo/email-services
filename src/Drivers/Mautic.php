<?php

namespace Jeeglo\EmailService\Drivers;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class Mautic
{
    protected $username;
    protected $password;
    protected $api_url;
    protected $mautic_auth;
    protected $mautic_api;
    protected $mautic;

    /**
     * Prepare the constructor and set the  values accordingly
     * SendFox constructor.
     * @param $credentials
     */
    public function __construct($credentials) {

        try {
            $this->username = $credentials['name'];
            $this->password = $credentials['password'];
            $this->api_url = $credentials['api_url'];

            $initAuth = new ApiAuth();
            $this->mautic_auth = $initAuth->newAuth($credentials, 'BasicAuth');

            if ($this->mautic_auth) {


                if(isset($this->mautic_auth->errors)) {
                    echo 'ERROR';
                }

                //check the error if credentials are invalid.. if invalid then return

                // initialize the Mautic API instance
               $test= $this->mautic_api = new MauticApi();

            }

        } catch (\Exception $e) {
echo "Faild";
               echo 'Exception: '. $e->getMessage();
        }
    }

    /**
     * fetch tags through API
     * @return array
     */
    public function getTags() {
        try {
if($this->mautic_auth) { // This condition is not working

    $tagApi = $this->mautic_api->newApi('tags', $this->mautic_auth, $this->api_url);

    // Get Contact list

    $results = $tagApi->getList();
    echo "<pre>";
    if (isset($results["error"]["message"])) { // This Condtion is working when credtionals are invalid
        return $this->failedResponse();
    } else {
        return $results;
    }
}else{
    return "Given Credtionals are Invalid ";
}

        } catch (\Exception $e) {
            echo "Failed";
//            throw new \Exception($e->getMessage());
            $this->failedResponse();
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $addTags = [] ,$removeTags = [])
    {


            // set param fields to send email service
            $contact = array(
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'ipAddress' => $data['ipAddress'],
                'ipAddress' => $data['ipAddress'],
//                'tags'=>$data["tags"]


            );
        if ($this->mautic_auth) {
            $contactApi = $this->mautic_api->newApi('contacts', $this->mautic_auth, $this->api_url);
            $response = $contactApi->create($contact);

            echo "<pre>";

            if(isset($response["error"]["message"]) && !$response["error"]["message"]){


            if (isset($response["contact"]["id"])) {
                $id = $response["contact"]["id"];

                // If contact added successfully then add/remove the tags
                if ($id) {
                    $data['contact_id'] = $id;
                    $this->sync($data, $removeTags, $addTags);

                    return $this->successResponse();
                }
            }
            }else{
                echo isset($response["error"]["message"]) ? $response["error"]["message"] : " ";
            }
        }else{
            echo "Invlid Credionals";
        }
    }
    private function sync($data, $removeTags ,$addTags)
    {

      try {
            // Add the Tags
          // Create new a contact of ID 1 is not found?
          $createIfNotFound = true;
          $remove_tag_data = array_merge($data, $removeTags);


          $add_tag_data = array_merge($data, $addTags);
          $contactApi = $this->mautic_api->newApi('contacts', $this->mautic_auth, $this->api_url);

          // add tags
          if(is_array($add_tag_data) && count($add_tag_data) > 0) {
              $contactApi->edit($data['contact_id'], $add_tag_data, $createIfNotFound);
          }

          // remove tags
          if(is_array($remove_tag_data) && count($remove_tag_data) > 0 ) {
              $contactApi->edit($data['contact_id'], $remove_tag_data, '');
          }


        } catch (\Exception $e) {

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
        echo "Something went wrong";
        //throw new \Exception('Something went wrong!');
    }
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
}
