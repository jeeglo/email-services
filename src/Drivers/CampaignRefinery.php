<?php

namespace Jeeglo\EmailService\Drivers;

class CampaignRefinery
{
    protected $api_key;
    protected $api_url;

    public function __construct($credentials) {
        $this->api_key = $credentials['api_key'];
        $this->api_url = "https://api.campaignrefinery.com/rest/";
    }

    /**
     * Get ALL Tags (with pagination)
     */
    public function getTags() {
        try {
            $tags = [];
            $page = 1;

            do {
                $res = $this->curl("tags/get_tags?page=".$page);
                if (!$res) break;

                $response = json_decode($res, true);

                if (isset($response['data']['data'])) {
                    foreach ($response['data']['data'] as $tag) {
                        $tags[] = [
                            'id' => $tag['tag_uuid'],
                            'name' => $tag['tag_name']
                        ];
                    }

                    $nextPage = $response['data']['next_page_url'];
                    $page++;
                } else {
                    $nextPage = null;
                }

            } while ($nextPage);

            return $tags;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get ALL Forms (Lists)
     */
    public function getLists()
    {
        try {
            $forms = [];
            $page = 1;

            do {
                $res = $this->curl("forms/get_forms?page=".$page);
                if (!$res) break;

                $response = json_decode($res, true);

                if (isset($response['data']['data'])) {
                    foreach ($response['data']['data'] as $form) {
                        $forms[] = [
                            'id' => $form['form_uuid'],
                            'name' => $form['form_name']
                        ];
                    }

                    $nextPage = $response['data']['next_page_url'];
                    $page++;
                } else {
                    $nextPage = null;
                }

            } while ($nextPage);

            return $forms;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Add Contact + Tag Sync
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        try {
            $payload = [
                "email" => $data['email'],
                "first_name" => $data['first_name'] ?? null,
                "last_name" => $data['last_name'] ?? null,
                "form_id" => $data['list_id'] ?? null
            ];

            $res = $this->curl('contacts/subscribe', $payload, "POST");

            if ($res) {
                $response = json_decode($res, true);

                if (isset($response['data']['contact_uuid'])) {
                    $contact_uuid = $response['data']['contact_uuid'];

                    $this->sync($contact_uuid, $add_tags, $remove_tags);
                }

                return $this->successResponse();
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Add / Remove Tags (FIXED PARAMS)
     */
    private function sync($contact_uuid, $addTags ,$removeTags)
    {
        try {
            // Add Tags
            if (!empty($addTags)) {
                $payload = [
                    "id" => $contact_uuid,
                ];

                if (count($addTags) === 1) {
                    $payload["tag_id"] = reset($addTags); // single
                } else {
                    $payload["tag_ids"] = implode(',', array_values($addTags)); // multiple
                }

                $this->curl('contacts/add_tags', $payload, "POST");
            }

            // Remove Tags
            if (!empty($removeTags)) {
                $payload = [
                    "id" => $contact_uuid,
                ];

                if (count($removeTags) === 1) {
                    $payload["tag_id"] = reset($removeTags);
                } else {
                    $payload["tag_ids"] = implode(',', array_values($removeTags));
                }

                $this->curl('contacts/delete_tags', $payload, "POST");
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * CURL Request Handler
     */
    private function curl($api_method, $data = [], $method = 'GET', $headers = [])
    {
        $url = $this->api_url . $api_method;

        $curl = curl_init();

        $defaultHeaders = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        ]);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl));
        }

        curl_close($curl);

        return $response;
    }

    /**
     * Success Response
     */
    private function successResponse()
    {
        return ['success' => 1];
    }

    /**
     * Verify API Credentials
     */
    public function verifyCredentials()
    {
        $response = $this->curl('forms/get_forms');
        $response = json_decode($response, true);

        if (isset($response['success']) && $response['success'] === true) {
            return json_encode(['error' => 0, 'message' => 'Connection succeeded.']);
        }

        return json_encode([
            'error' => 1,
            'message' => $response['message'] ?? 'Invalid API Key'
        ]);
    }
}