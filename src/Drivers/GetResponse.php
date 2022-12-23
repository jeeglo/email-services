<?php
namespace Jeeglo\EmailService\Drivers;
use Getresponse\Sdk\Operation\Contacts\GetContact\GetContact;
use Getresponse\Sdk\Operation\Contacts\GetContacts\GetContacts;
use Getresponse\Sdk\Operation\Contacts\GetContacts\GetContactsSearchQuery;
use Getresponse\Sdk\Operation\Campaigns\GetCampaigns\GetCampaigns;
use Getresponse\Sdk\Operation\Contacts\CreateContact\CreateContact;
use Getresponse\Sdk\Operation\Contacts\UpdateContact\UpdateContact;
use Getresponse\Sdk\Operation\Model\CampaignReference;
use Getresponse\Sdk\Operation\Model\NewContact;
use Getresponse\Sdk\Operation\Model\UpdateContact as ModelUpdateContact;
use Getresponse\Sdk\Operation\Tags\DeleteTag\DeleteTag;
use Getresponse\Sdk\Operation\Tags\GetTags\GetTags;

use Getresponse\Sdk\GetresponseClientFactory;
class GetResponse

{
    protected $api_key;
    protected $getResponse;
    protected $client;
    protected $domain;
    protected $api_url;
    protected $contact_id = null;

    public function __construct($credentials)
    {
        // @todo Throw exception if API key is not available
        $this->api_key = (isset($credentials['api_key']) ? $credentials['api_key'] : null);
        $this->api_url = (isset($credentials['api_url']) ? $credentials['api_url'] : null);
        $this->domain = (isset($credentials['domain']) ? $credentials['domain'] : null);


        if(!empty($this->domain)) {

            if($this->api_url === 'https://api3.getresponse360.pl/v3') {
                $this->client = GetresponseClientFactory::createEnterprisePLWithApiKey($this->api_key, $this->domain);

            } else if($this->api_url === 'https://api3.getresponse360.com/v3') {
                $this->client = GetresponseClientFactory::createEnterpriseUSWithApiKey($this->api_key, $this->domain);
            }

        }else{
            $this->client = GetresponseClientFactory::createWithApiKey($this->api_key);
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return string [return success or fail]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {

        try {

            $getContactsOperation = new GetContacts();
            $searchQuery = new GetContactsSearchQuery();
            $searchQuery->whereEmail($data['email']);

            //@ todo search from list id
            $getContactsOperation->setQuery($searchQuery);

            $response = $this->apiCall($getContactsOperation);

            // Check if email is exist then only update data if required
            $list = $response->getData();
            $name = (isset($data['first_name']) ? $data['first_name'] : null ). ' ' .(isset($data['last_name']) ? $data['last_name'] : null );

            if (!empty($list)) {

                if(isset($list[0]['contactId'])) {

                    if(isset($response->getData()[0]['contactId'])) {

                        $this->contact_id = $contact_id = $response->getData()[0]['contactId'];

                        $updateContact = new ModelUpdateContact();

                        if (strlen(trim($name)) > 0) {
                            $updateContact->setName($name);
                        }

                        $updateContactOperation = new UpdateContact($updateContact, $contact_id);
                        $this->sync($updateContact, $add_tags, $remove_tags);

                        $response = $this->apiCall($updateContactOperation);

                        if ($response->isSuccess()) {

                            $this->successResponse();

                        } else{
                            $this->failedResponse();
                        }
                    }else{
                        $this->failedResponse();
                    }
                }
                // Create new contact
            } else {

                $newContact = new NewContact(
                    new CampaignReference($data['list_id']),
                    $data['email']
                );

                if (strlen(trim($name)) > 0) {
                    $newContact->setName($name);
                }

                // Set tags
                $this->sync($newContact, $add_tags, $remove_tags);

                $createContact = new CreateContact($newContact);

                $response =  $this->apiCall($createContact);

                if ($response->isSuccess()) {
                    $this->successResponse();

                } else {
                    $this->failedResponse();
                }
            }
        }catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    /**
     * Set tags for contact
     * @param $contactData
     * @param $add_tags
     * @param $remove_tags
     */
    public function sync($contactData, $add_tags, $remove_tags)
    {
        // if the contact already exist
        if($this->contact_id) {
            // get the contact detail by id
            $getContactDetailsOperation = new GetContact($this->contact_id);
            // set API call instance
            $getContactDetailsOperationResponse = $this->apiCall($getContactDetailsOperation);
            // get response from API
            $getContactDetailsOperationData = $getContactDetailsOperationResponse->getData();

            // if we have tags in response then sync the tags according to add_tags and remove_tags array
            if(!empty($getContactDetailsOperationData['tags'])) {
                // get existing tags
                $existingTags = $getContactDetailsOperationData['tags'];
                // loop through existing tags
                foreach ($existingTags as $tag) {
                    // if the current tags is not our add_tags array the push it
                    if(!in_array($tag['tagId'], $add_tags)) {
                        $add_tags[] = $tag['tagId'];
                    }
                }
            }
        }

        // if we have data in add_tags array
        if(is_array($add_tags) && count($add_tags) > 0) {

            // loop through the add_tags array to unset the tagIds we have in our remove_tags array
            foreach ($add_tags as $key => $add_tag) {
                if(in_array($add_tag, $remove_tags)) {
                    // if we have found the match tagId from remove_tags array then unset it from add_tags array
                    unset($add_tags[$key]);
                }
            }

            // set the tags in particular contact
            $contactData->setTags($add_tags);
        }
    }

    /**
     * This function calls api for perform any actions for Add Contact, Get Tags or for the lists fetch
     * @param $operation
     * @return \Getresponse\Sdk\Client\Operation\OperationResponse
     */
    public function apiCall($operation)
    {
        return $this->client->call($operation);
    }

    /**
     * [getLists Fetch List through API]
     * @return array
     */

    public function getLists()
    {

        try {
            $campaignsOperation = new GetCampaigns();
            $data = $this->apiCall($campaignsOperation);

            if($data->isSuccess()) {

                $list = $data->getData();
                $response = [];

                foreach ($list as $list) {
                    $response[] = array(
                        'id' => $list['campaignId'],
                        'name' => $list['name'],
                    );
                }
                return $response;

            } else {

                return $this->failedResponse();
            }

        }catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    /**
     * fetch tags through API
     * @return array
     */
    public function getTags() {
        try {
            $getTagsOperation = new GetTags();

            $getTagsResponse = $this->apiCall($getTagsOperation);
            $tagsList = $getTagsResponse->getData();

            if ($getTagsResponse->isSuccess()) {

                $response = [];

                if(is_array($tagsList) && count($tagsList) > 0) {
                    foreach ($tagsList as $item) {
                        $response[] = [
                            'id' => $item['tagId'],
                            'name' => $item['name']
                        ];
                    }
                }
                return $response;
            }
        }catch (\Exception $e){
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
    public function verifyCredentials()
    {

        $getTagsOperation = new GetTags();

        $getTagsResponse = $this->apiCall($getTagsOperation);
        $tagsList = $getTagsResponse->getData();

        if(isset($tagsList['httpStatus']) && $tagsList['httpStatus'] == 401) {
            return json_encode(['error' => 1, 'message' => $tagsList['message']]);

        }else{
            return json_encode(['error' => 0, 'message' => 'Connection succeeded']);

        }

    }

}
