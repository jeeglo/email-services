<?php

namespace Jeeglo\EmailService\Drivers;

use Exception;

class GoHighLevel
{
    protected $client_id;
    protected $client_secret;
    protected $redirect_url;
    protected $access_token;
    protected $base_url = "https://services.leadconnectorhq.com";

    public function __construct($credentials)
    {
        $this->client_id     = $credentials['client_id'];
        $this->client_secret = $credentials['client_secret'];
        $this->redirect_url  = $credentials['redirect_url'];
        $this->access_token  = $credentials['access_token'] ?? null;
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
            'scope'         => 'locations/tags.write locations/tags.readonly contacts.write contacts.readonly',
            'version_id'    => '68d665695426945316ca23ad',
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

            print_r('Getting token below is the response');
            print_r($response);
            $accessToken = $response['access_token'] ?? null;

            if (!$accessToken) {
                throw new Exception("No access token returned.");
            }

//            // Fetch locations
//            $profile = $this->curl("/users/profile", "GET", [], true, $accessToken);
//            $locations = $profile['locations'] ?? [];

            return [
                'access_token' => $accessToken,
                'refresh_token' => $response['refresh_token'] ?? null,
//                'locations' => $locations
            ];

        } catch (Exception $e) {
            print_r($e->getMessage());
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
    public function getTags($data)
    {
        try {
            $res = $this->curl("/locations/{$data['location_id']}/tags", "GET");

            $tags = [];
            if (isset($res['tags'])) {
                foreach ($res['tags'] as $data) {
                    $tags[] = [
                        'id'   => $data['id'],
                        'name' => $data['name']
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
            if (empty($data['email']) || empty($data['location_id'])) {
                throw new \Exception("Email and location_id are required");
            }

            // Build upsert payload (locationId must be included in body)
            $payload = [
                'locationId' => $data['location_id'],
                'email'      => $data['email'],
                'firstName'  => $data['first_name'] ?? '',
                'lastName'   => $data['last_name'] ?? '',
                // optional phone / customFields if you have them:
                //'phone' => $data['phone'] ?? null,
                //'customFields' => $data['custom_fields'] ?? []
            ];

            // DON'T rely only on tags inside the upsert if you want to *add* tags.
            // Some implementations overwrite tags when upserting. Safer to upsert then call add-tags.
            if (!empty($add_tags)) {
                // you may pass tags to upsert, but we'll add them after upsert to avoid overwriting.
                $payload['tags'] = $add_tags;
            }

            // call upsert
            $res = $this->curl('/contacts/upsert', 'POST', $payload);

            // defensive extraction of contactId (response varies)
            $contactId = $res['id'] ?? $res['contact']['id'] ?? $res['data'][0]['id'] ?? null;
            if (!$contactId && isset($res['contactId'])) {
                $contactId = $res['contactId'];
            }

            if (!$contactId) {
                // best-effort: if upsert didn't return id, try a search by email
                $found = $this->searchContactByEmail($data['email'], $data['location_id']);
                $contactId = $found['id'] ?? null;
            }

            if (!$contactId) {
                throw new \Exception("Contact created but ID not returned.");
            }

            // Add tags (use add tags endpoint to avoid overwriting)
            if (!empty($add_tags)) {
                $this->curl("/contacts/{$contactId}/tags", 'POST', ['tags' => array_values($add_tags)]);
            }

            // Remove tags (use remove tags endpoint)
            if (!empty($remove_tags)) {
                // DELETE with JSON body; your curl helper already supports sending JSON on DELETE/PUT via CURLOPT_POSTFIELDS
                $this->curl("/contacts/{$contactId}/tags", 'DELETE', ['tags' => array_values($remove_tags)]);
            }

            return $this->successResponse();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

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
            $this->getLocations();
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        } catch (Exception $e) {
            return json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
    }

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

        print_r($response);
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
