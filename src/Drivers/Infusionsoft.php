<?php

namespace Jeeglo\EmailService\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Infusionsoft
{
    protected string $client_id;
    protected string $client_secret_key;
    protected ?string $access_token;
    protected ?string $refresh_token;
    protected ?string $redirect_uri;

    protected Client $http;
    protected string $api_url = 'https://api.infusionsoft.com/crm/rest/v1';
    protected string $token_url = 'https://api.infusionsoft.com/token';

    public function __construct($credentials)
    {
        $this->client_id = $credentials['client_id'];
        $this->client_secret_key = $credentials['client_secret'];
        $this->redirect_uri = $credentials['redirect_uri'] ?? null;
        $this->access_token = $credentials['access_token'] ?? null;
        $this->refresh_token = $credentials['refresh_token'] ?? null;

        $this->http = new Client([
            'timeout' => 30,
        ]);
    }

    /* -------------------------------------------------
     | TAGS
     |-------------------------------------------------*/
    public function getTags()
    {
        try {
            $tags = [];
            $offset = 0;

            do {
                $response = $this->request('GET', "/tags", [
                    'query' => [
                        'limit'  => 1000,
                        'offset' => $offset,
                    ]
                ]);

                if (!empty($response['tags'])) {
                    foreach ($response['tags'] as $tag) {
                        $tags[] = [
                            'id'   => $tag['id'],
                            'name' => $tag['name'],
                        ];
                    }
                }

                $offset += 1000;
                $hasMore = isset($response['count']) && $response['count'] > $offset;

            } while ($hasMore);

            return $tags;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /* -------------------------------------------------
     | CONTACTS
     |-------------------------------------------------*/
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            $payload = [
                'email_addresses' => [
                    [
                        'email' => $data['email'],
                        'field' => 'EMAIL1',
                    ]
                ],
                'given_name'        => trim($data['first_name'] ?? ''),
                'family_name'       => trim($data['last_name'] ?? ''),
                'duplicate_option'  => 'Email',
                'opt_in_reason'     => 'Member of a Product/Collection (ProductDyno)',
            ];

            $contact = $this->request('POST', '/contacts', [
                'json' => $payload
            ]);

            if (!empty($contact['id'])) {
                $this->sync($contact['id'], $remove_tags, $add_tags);
                return ['success' => 1];
            }

            throw new \Exception('Contact creation failed.');

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function sync($contactId, $removeTags, $addTags)
    {
        if (!empty($addTags)) {
            $this->request('POST', "/contacts/{$contactId}/tags", [
                'json' => ['tagIds' => array_values($addTags)]
            ]);
        }

        if (!empty($removeTags)) {
            $this->request('DELETE', "/contacts/{$contactId}/tags", [
                'json' => ['tagIds' => array_values($removeTags)]
            ]);
        }
    }

    /* -------------------------------------------------
     | OAUTH
     |-------------------------------------------------*/
    public function connect()
    {
        $query = http_build_query([
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => 'code',
            'scope'         => 'full',
        ]);

        return [
            'url' => "https://accounts.infusionsoft.com/app/oauth/authorize?$query"
        ];
    }

    public function getConnectData()
    {
        if (!isset($_GET['code'])) {
            throw new \Exception('Authorization code missing.');
        }

        $response = $this->http->post($this->token_url, [
            'auth' => [$this->client_id, $this->client_secret_key],
            'form_params' => [
                'grant_type'   => 'authorization_code',
                'code'         => $_GET['code'],
                'redirect_uri' => $this->redirect_uri,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        return [
            'access_token'  => $data['access_token'] ?? null,
            'refresh_token' => $data['refresh_token'] ?? null,
        ];
    }

    public function regenrateAccessToken($refresh_token)
    {
        return $this->_regenrateAccessTokenAgainstRefreshToken($refresh_token);
    }

    private function _regenrateAccessTokenAgainstRefreshToken($refresh_token)
    {
        $response = $this->http->post($this->token_url, [
            'auth' => [$this->client_id, $this->client_secret_key],
            'form_params' => [
                'grant_type'    => 'refresh_token',
                'refresh_token'=> $refresh_token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /* -------------------------------------------------
     | HELPERS
     |-------------------------------------------------*/
    private function request($method, $uri, $options = [])
    {
        try {
            $options['headers']['Authorization'] = "Bearer {$this->access_token}";
            $options['headers']['Accept'] = 'application/json';

            $response = $this->http->request(
                $method,
                $this->api_url . $uri,
                $options
            );

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new \Exception(
                $e->getResponse()
                    ? $e->getResponse()->getBody()->getContents()
                    : $e->getMessage()
            );
        }
    }

    public function verifyCredentials()
    {
        try {
            $this->getTags();
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        } catch (\Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }
}
