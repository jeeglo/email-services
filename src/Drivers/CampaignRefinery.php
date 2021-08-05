<?php

namespace Jeeglo\EmailService\Drivers;

class CampaignRefinery
{
    /**
     * define teh API Key
     * @var string
     */
    protected $api_key;

    /**
     * define teh API Base URL
     * @var string
     */
    protected $api_url;

    /**
     * Set the construct value
     * CampaignRefinery constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://app.campaignrefinery.com/rest/";
    }

    /**
     * fetch tags through API
     * @return array
     * @throws \Exception
     */
    public function getTags() {
        try {
            $res = $this->curl('tags/get-tags');

            if($res) {
                $tags = [];

                // decode the response
                $tags_data = json_decode($res, true);

                // if we found the tags key data then append in the array
                if (isset($tags_data['tags'])) {
                    foreach ($tags_data['tags'] as $data) {
                        $tags[] = array(
                            'id' => $data['tag_uuid'],
                            'name' => $data['tag_name']
                        );
                    }

                    return $tags;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get the Forms through API (it's called Forms in CR instead of Lists)
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {
        try {
            // Initialize the forms array to append
            $forms = [];

            //  send the call to API to fetch
            $res = $this->curl('forms/get-forms');

            if($res) {
                // decode the response
                $forms_data = json_decode($res, true);

                // if we found the forms key data then append in the array
                if (isset($forms_data['forms'])) {
                    foreach ($forms_data['forms'] as $data) {
                        $forms[] = array(
                            'id' => $data['form_uuid'],
                            'name' => $data['form_name']
                        );
                    }

                    return $forms;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * [addContact Add contact to list through API]
     * @return array [return success or fail]
     * @throws \Exception
     */
    public function addContact($data,  $remove_tags = [] , $add_tags = [])
    {
        try {
            // modify the data to add contact according to CR API requirement
            $data['key'] = $this->api_key;
            $data['form_id'] = $data['list_id'];

            // send call to API to add contact
            $res =  $this->curl('contacts/subscribe', $data, "POST");

            if($res) {
                // decode the response
                $contact = json_decode($res);

                // if response has contact id then assign it into data
                if(isset($contact->id)) {
                    // if successfully added contact the call add tag api to add contact - then call the remove tag API to remove tags from contact
                    $data['id'] = $contact->id;
                    $this->sync($data, $add_tags, $remove_tags);
                }

                return $this->successResponse();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
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
    private function sync($data, $addTags ,$removeTags)
    {
        try {
            // check if we have tags data to add
            if(is_array($addTags) && count($addTags) > 0) {
                // Preparing the tags node for adding tags
                $data['tag_ids'] = implode(",", $addTags);
                // Call the API to add tags
                $this->curl('contacts/add-tag', $data, "POST");
            }

            // check if we have tags data to remove
            if(is_array($removeTags) && count($removeTags) > 0 ) {;
                // Preparing the tags node for removing tags
                $data['tag_ids'] = implode(",", $removeTags);

                // Call the API to remove tags
                $this->curl('contacts/delete-tag', $data, "POST");
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Request Method
     * @return array for getList
     * @return array for addContact
     */
    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {
        $url = $this->api_url.$api_method;
        $curl = curl_init($url);

        // set the curl GET params is request type is GET
        if($method == 'GET') {
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url."?key=".$this->api_key,
            ));
        }

        // set the curl POST params is request type is POST
        if($method == 'POST') {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: multipart/form-data',
            ));

            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        // execute Curl
        $response = curl_exec($curl);

        // close the connection of Curl
        curl_close($curl);

        // return the response
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
     * CampaignRefinery test credentials
     */
    public function verifyCredentials()
    {

        $response = $this->curl('forms/get-forms');

        $response = json_decode($response, TRUE);

        if (isset($response['error'])) {
            return json_encode(['error' => 1, 'message' => $response['error']]);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);

        }
    }
}