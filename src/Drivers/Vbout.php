<?php


namespace Jeeglo\EmailService\Drivers;
require_once dirname(dirname(__FILE__)) . '/VboutAPI/services/EmailMarketingWS.php';
use EmailMarketingWS;

class Vbout
{
    protected $vboutAPI;

    public function __construct($credentials)
    {
        $this->vboutAPI = (new EmailMarketingWS($credentials));
    }

    /**
     * get lists form vbout
     * @return mixed
     */
    public function getLists()
    {
        try {
            $results = $this->vboutAPI->getMyLists();
            // Pass the $results array to the response() method to generate the formatted response
            return $this->response($results);
        } catch (\Exception $e) {
            // Handle the exception if it occurs
            throw new \Exception("Error while getting lists: " . $e->getMessage());
        }
    }

    /**
     * Prepare response for list data
     * @param $results
     * @return array
     * @throws \Exception
     */
    private function response($results)
    {
        $response = [];

        try {
            if (isset($results['items']) && !empty($results['items'])) {
                foreach ($results['items'] as $result) {
                    $response[] = array(
                        'id' => $result['id'],
                        'name' => $result['name']
                    );
                }
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    /**
     * Add contact of vbout
     * @param $data
     * @return array
     */
    public function addContact($data)
    {
        try {
            $list_detail = $this->vboutAPI->getMyList($data['list_id']);

            $first_name_id = null;
            $last_name_id = null;
            $full_name_id = null;

            if (isset($list_detail['fields'])) {
                $fields = $list_detail['fields'];
                $first_name_id = array_search('First Name', $fields);
                $last_name_id = array_search('Last Name', $fields);
                $full_name_id = array_search('Full Name', $fields);
            }

            $fields_data = [];

            if($first_name_id) {
                $fields_data[$first_name_id] = $data['first_name'];
            }

            if($last_name_id) {
                $fields_data[$last_name_id] = $data['last_name'];
            }

            if($full_name_id) {
                $fields_data[$full_name_id] = $data['first_name'].' '.$data['last_name'];
            }

            $contact_data = array(
                'email' => $data['email'],
                'listid' => $data['list_id'],
                'status' => 'Active',
                'fields' => $fields_data
            );

            // check if contact exist
            $contact = $this->vboutAPI->searchContact($contact_data['email'], $contact_data['listid']);
            if(!isset($contact['errorCode'])) {
                // No error means we found the contact
                if(isset($contact[0]['id'])) {
                    // get the contact id
                    $contact_data['id'] = $contact[0]['id'];
                    return $this->vboutAPI->updateContact($contact_data);
                }
            } else {
                // add new contact
                return $this->vboutAPI->addNewContact($contact_data);
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * Vbout test credentials
     */
    public function verifyCredentials()
    {
        try {
            $results = $this->vboutAPI->getMyLists();

            if(isset($results['errorCode'])) {
                return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);
            }

            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);

        } catch (\Exception $e) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your keys.']);
        }
    }

}
