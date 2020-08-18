<?php

namespace Jeeglo\EmailService\Drivers;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class Mautic
{
    protected $apiUrl;
    protected $mauticAuth;
    protected $mauticApi;

    /**
     * Prepare the constructor and set the  values accordingly
     * Mautic constructor.
     * @param $credentials
     */
    public function __construct($credentials) {

        try {
            // set the variables from credentials data
            $this->apiUrl = $credentials['api_url'];

            // Connecting Mautic
            $initAuth = new ApiAuth();
            $this->mauticAuth = $initAuth->newAuth($credentials, 'BasicAuth');

            // initialize the Mautic API instance
            $this->mauticApi = new MauticApi();

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * fetch tags through API
     * @return array
     */
    public function getTags() {
        try {
            // getting the all tags of Mautic
            $tagApi = $this->mauticApi->newApi('tags', $this->mauticAuth, $this->apiUrl);

            // Get tags list
            $response = $tagApi->getList();

            if ($response) {
                // if response has error then stop the further execution of code
                if(isset($response['error'])) {
                    return $this->failedResponse();
                }

                // if tags are available then return all tags
                if (isset($response["tags"])) {
                    $tags = $response['tags'];
                    return $this->response($tags);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to against the tags through API]
     * @param $data
     * @param array $removeTags
     * @param array $addTags
     * @return string [return success or fail]
     * @throws \Mautic\Exception\ContextNotFoundException
     */
    public function addContact($data, $removeTags = [], $addTags = [])
    {
        // Set param fields to send email service
        $contact = array(
            'firstname' => $data['first_name'],
            'lastname' => $data['last_name'],
            'email' => $data['email']
        );

        // Send request to add the contact
        $contactApi = $this->mauticApi->newApi('contacts', $this->mauticAuth, $this->apiUrl);
        $response = $contactApi->create($contact);

        if($response) {
            // if response has error then return error
            if(isset($response['error'])) {
                return $this->failedResponse();
            }

            // if response has contact id then assign it into data
            if(isset($response['contact'])) {
                $id = $response["contact"]["id"];

                if ($id) {
                    $data['contact_id'] = $id;
                    $this->sync($data, $removeTags, $addTags);

                    return $this->successResponse();
                }
            }
        }
    }

    /**
     * [sync add and remove tags]
     * @param $data
     * @param $removeTags
     * @param $addTags
     * @return void [array] [Success true]
     * @throws \Exception
     */
    private function sync($data, $removeTags ,$addTags)
    {
        try {
            // Initialize the contact API
            $contactApi = $this->mauticApi->newApi('contacts', $this->mauticAuth, $this->apiUrl);

            // Adding the tags
            if(is_array($addTags) && count($addTags) > 0) {
                // Preparing the tags node for adding tags
                $addTags['tags'] = $addTags;

                // Merging the contact data array with add tags array to prevent lost the previous contact data like name, email etc
                $add_tag_data = array_merge($data, $addTags);

                // Send request to add tags
                $contactApi->edit($data['contact_id'], $add_tag_data);
            }

            // Removing the tags
            if(is_array($removeTags) && count($removeTags) > 0 ) {

                // Preparing the tags node for adding tags - Appending the (-) symbol to against the tag values to removing the tags
                $removeTags['tags'] = preg_filter('/^/', '-', $removeTags);

                // Merging the contact data array with add tags array to prevent lost the previous contact data like name, email etc
                $remove_tag_data = array_merge($data, $removeTags);
                $contactApi->edit($data['contact_id'], $remove_tag_data);
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
        throw new \Exception('Something went wrong!');
    }

    /**
     * [successResponse description]
     * @param $data
     * @return array [array] [Success true]
     * @throws \Exception
     */
    private function response($data)
    {
        $response = [];
        try {

            if(is_array($data) && count($data) > 0) {
                foreach ($data as $item) {
                    $response[] = [
                        'id' => $item['tag'],
                        'name' => $item['tag']
                    ];
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }
}