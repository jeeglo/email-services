<?php

namespace Jeeglo\EmailService\Drivers;
use SendinBlue\Client\Api as SendInBlueApi;
use GuzzleHttp\Client as SendInBlueGuzzleClient;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model as SendInBlueModel;
use SendinBlue\Client\ApiException;

class SendInBlue
{
    /**
     * define teh API Key
     * @var string
     */
    protected $api_key;

    /**
     * Set the construct value
     * SendInBlue constructor.
     * @param $credentials
     */
    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
    }

    /**
     * Get the List through API
     * @return array
     * @throws \Exception
     */
    public function getLists()
    {

        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->api_key);

        // Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
        // $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('api-key', 'Bearer');

        // Configure API key authorization: partner-key
        $config = Configuration::getDefaultConfiguration()->setApiKey('partner-key', $this->api_key);

        // Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
        // $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('partner-key', 'Bearer');

        $apiInstance = new SendInBlueApi\ContactsApi(
        // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
        // This is optional, `GuzzleHttp\Client` will be used as default.
            new SendInBlueGuzzleClient(),
            $config
        );

        $limit = 50; // int | Number of documents per page
        $offset = 0; // int | Index of the first document of the page
        $sort = "desc"; // string | Sort the results in the ascending/descending order of record creation. Default order is **descending** if `sort` is not passed

        $lists = [];// Initialize the lists array to append
        try {
            $result = $apiInstance->getLists($limit, $offset, $sort);
            $list_data = $result->getLists();

            // if we found List data then append in the array
            if (is_array($list_data) && count($list_data) > 0) {
                foreach ($list_data as $data) {
                    $lists[] = array(
                        'id' => $data['id'],
                        'name' => $data['name']
                    );
                }
                return $lists;
            } else {
                return ['error' => true];
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        } catch (ApiException $e) {
            return ['error' => new ApiException($e->getMessage())];
        }

    }

    /**
     * [addContact Add contact to list through API]
     * @return array [return success or fail]
     * @throws \Exception
     */
    public function addContact($data)
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->api_key);

        $apiInstance = new SendInBlueApi\ContactsApi(
            new SendInBlueGuzzleClient(),
            $config
        );
        $createContact = new SendInBlueModel\CreateContact(); // Values to create a contact
        $createContact['email'] = $data['email'];
        $createContact['listIds'] = [intval($data['list_id'])];
        $createContact['attributes'] = [
            'FIRSTNAME' => isset($data['first_name']) ? $data['first_name'] : null,
            'LASTNAME' => isset($data['last_name']) ? $data['last_name'] : null,
        ];

        try {
            $result = $apiInstance->createContact($createContact);
            if($result['id']) {
                return ['success' => 1];
            } else {
                return ['error' => true];
            }

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }


    /**
     * SendInBlue test credentials
     */
    public function verifyCredentials()
    {

        $response = $this->getLists();

        if(isset($response['error']) && $response['error']) {
            return json_encode(['error' => 1, 'message' => 'Connection was failed, please check your Api Key.']);

        } else {
            return json_encode(['error' => 0, 'message' => 'Connection Succeeded.']);
        }
    }
}
