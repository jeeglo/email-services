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
     * @return mixed
     */
    public function getLists()
    {

        try {
            $results = $this->vboutAPI->getMyLists();

            // Pass the $results array to the response() method to generate the formatted response
            return $this->response($results['items']);
        } catch (\Exception $e) {
            // Handle the exception if it occurs
            // For example, you can log the error or rethrow the exception
            throw new \Exception("Error while getting lists: " . $e->getMessage());
        }

    }


    private function response($lists)
    {
        $response = [];

        try {
            if (!empty($lists)) {
                foreach ($lists as $key => $list) {
                    $response[] = array(
                        'id' => $key,
                        'name' => $list['name']
                    );
                }
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    public function addContact($data)
    {
        $contact_data = array(
            'email' => $data['email'],
            'listid' => $data['list_id'],
            'FIRSTNAME' => $data['first_name'],
            'LASTNAME' => $data['last_name'],
            'status' => 'Active',
            'fields' => array('first_name' => 'John')
            );

            $result = $this->vboutAPI->addNewContact($contact_data);
            return $result;

    }


}