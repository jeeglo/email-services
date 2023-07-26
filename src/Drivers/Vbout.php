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
                foreach ($results as $result) {
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
        $contact_data = array(
            'email' => $data['email'],
            'listid' => $data['list_id'],
            'status' => 'Active',
        );

        $result = $this->vboutAPI->addNewContact($contact_data);
        return $result;
    }

}
