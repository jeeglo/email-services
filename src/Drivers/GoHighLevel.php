<?php

namespace Jeeglo\EmailService\Drivers;

use Exception;

class GoHighLevel
{
    protected $client_id;
    protected $client_secret;
    protected $redirect_url;
    protected $access_token;
    protected $location_id;
    protected $base_url = "https://services.leadconnectorhq.com";

    public function __construct($credentials)
    {
        $this->client_id     = $credentials['client_id'];
        $this->client_secret = $credentials['client_secret'];
        $this->redirect_url  = $credentials['redirect_url'];
        $this->access_token  = $credentials['access_token'] ?? null;
        $this->location_id  = $credentials['location_id'] ?? null;
    }

    /**
     * Generate OAuth URL for user connection
     */
    public function connect()
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_url,
            'scope'         => 'locations/tags.readonly contacts.write contacts.readonly',
        ]);

        $url = "https://marketplace.gohighlevel.com/oauth/chooselocation?$query";

        return ['url' => $url];
    }

    /**
     * Handle OAuth callback and get access token + locations
     */
    public function getConnectData()
    {
        if (isset($_GET['error'])) {
            throw new Exception($_GET['error']);
        }

        if (!isset($_GET['code'])) {
            $this->failedResponse();
        }

        try {
            $code = $_GET['code'];

            // Exchange code for token
            $response = $this->curl("/oauth/token", "POST", [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->redirect_url,
            ], false, null, true);

            $accessToken = $response['access_token'] ?? null;

            if (!$accessToken) {
                throw new Exception("No access token returned.");
            }

            return [
                'access_token' => $accessToken,
                'refresh_token' => $response['refresh_token'] ?? null,
                'location_id' => $response['locationId'] ?? null
            ];

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get lists of locations
     */
    public function getLocations()
    {
        try {
            $profile = $this->curl("/users/profile", "GET");
            return $profile['locations'] ?? [];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get tags for a location
     */
    public function getTags()
    {
        try {
            $res = $this->curl("/locations/{$this->location_id}/tags", "GET");

            $tags = [];
            if (!empty($res['tags'])) {
                foreach ($res['tags'] as $tag) {
                    $tags[] = [
                        'id'   => $tag['name'],
                        'name' => $tag['name']
                    ];
                }
            }

            return $tags;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add or update a contact
     */
    // inside your GoHighLevel class; follows your style
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            if (empty($data['email']) || empty($this->location_id)) {
                throw new \Exception("Email and location_id are required");
            }

            // Build upsert payload (locationId must be included in body)
            $payload = [
                'locationId' => $this->location_id,
                'email'      => $data['email'],
                'firstName'  => $data['first_name'] ?? '',
                'lastName'   => $data['last_name'] ?? '',
            ];

            if (!empty($add_tags)) {
                // you may pass tags to upsert, but we'll add them after upsert to avoid overwriting.
                $payload['tags'] = $add_tags;
            }

            // call upsert
            $res = $this->curl('/contacts/upsert', 'POST', $payload);

            // defensive extraction of contactId
            $contactId = $res['id'] ?? $res['contact']['id'] ?? $res['data'][0]['id'] ?? null;
            if (!$contactId && isset($res['contactId'])) {
                $contactId = $res['contactId'];
            }

            if (!$contactId) {
                // best-effort: if upsert didn't return id, try a search by email
                $found = $this->searchContactByEmail($data['email'], $this->location_id);
                $contactId = $found['id'] ?? null;
            }

            if (!$contactId) {
                throw new \Exception("Contact created but ID not returned.");
            }

            // sync contact tags
            $this->updateContactTags($contactId, $add_tags, $remove_tags);

            return $this->successResponse();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Update/remove tags from a contact
     * @param $contactId
     * @param array $add_tags
     * @param array $remove_tags
     * @return int[]
     * @throws Exception
     */
    private function updateContactTags($contactId, $add_tags = [], $remove_tags = [])
    {
        try {
            // 1. Get current contact
            $contact = $this->curl("/contacts/{$contactId}", "GET");

            if (empty($contact['contact'])) {
                throw new Exception("Contact not found");
            }

            $currentTags = $contact['contact']['tags'] ?? [];

            // 2. Merge tags
            $finalTags = array_unique(array_merge($currentTags, $add_tags));

            if (!empty($remove_tags)) {
                $finalTags = array_diff($finalTags, $remove_tags);
            }

            // 3. Update contact with final tag list
            $payload = [
                'tags' => array_values($finalTags)
            ];

            $this->curl("/contacts/{$contactId}", "PUT", $payload);

            return $this->successResponse();

        } catch (Exception $e) {
            throw new Exception("Failed to update tags: " . $e->getMessage());
        }
    }

    /**
     * Search contact by email and location ID
     * @param $email
     * @param $locationId
     * @return mixed
     * @throws Exception
     */
    private function searchContactByEmail($email, $locationId)
    {
        $payload = [
            'locationId' => $locationId,
            'filters' => [
                [
                    'field' => 'email',
                    'operator' => 'eq',
                    'value' => $email
                ]
            ],
            'limit' => 1
        ];

        $res = $this->curl('/contacts/search', 'POST', $payload);
        // parse response according to returned structure (res.contact(s) etc)
        return $res;
    }

    /**
     * Verify credentials by fetching locations
     */
    public function verifyCredentials()
    {
        try {
            $this->getTags();
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        } catch (Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Regenerate a new access token using the refresh token
     * @param string $refreshToken
     * @return array
     * @throws Exception
     */
    public function regenrateAccessToken($refreshToken)
    {
        try {
            $response = $this->curl(
                "/oauth/token",
                "POST",
                [
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
                false,   // no auth header
                null,
                true     // send as form data
            );

            if (empty($response['access_token'])) {
                throw new Exception("Failed to regenerate access token");
            }

            return $response;

        } catch (Exception $e) {
            throw new Exception("Token refresh failed: " . $e->getMessage());
        }
    }

    /**
     * Generic curl request for API calls
     * @param $endpoint
     * @param string $method
     * @param array $data
     * @param bool $auth
     * @param null $overrideToken
     * @param false $form
     * @return mixed
     * @throws Exception
     */
    private function curl($endpoint, $method = "GET", $data = [], $auth = true, $overrideToken = null, $form = false)
    {
        $url = $this->base_url . $endpoint;

        $headers = ["Accept: application/json"];

        if ($form) {
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
        } else {
            $headers[] = "Content-Type: application/json";
        }

        if ($auth) {
            $token = $overrideToken ?? $this->access_token;
            $headers[] = "Authorization: Bearer {$token}";
            $headers[] = "Version: 2021-07-28";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                $form ? http_build_query($data) : json_encode($data)
            );
        } elseif (in_array($method, ["PUT", "PATCH"])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                $form ? http_build_query($data) : json_encode($data)
            );
        }

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            $msg = $decoded['error_description'] ?? $decoded['message'] ?? "Unknown error";
            throw new Exception("API Error ({$status}): {$msg}");
        }

        return $decoded;
    }


    private function successResponse()
    {
        return ['success' => 1];
    }

    private function failedResponse()
    {
        throw new Exception('Something went wrong!');
    }
}
