<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

final class FCM
{

    // ... [Previous constants remains] ...
    const FCM_V1_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    private string $project_id;

    private string $bearer_token;

    // For OAuth2
    private array $message = [];

    private array $header = [];

    /**
     *
     * @param string $project_id
     *            Your Firebase Project ID (required for v1)
     * @param string $bearer_token
     *            OAuth2 Access Token
     */
    function __construct(string $project_id, string $bearer_token = '')
    {
        $this->project_id = $project_id;
        $this->bearer_token = $bearer_token;
        $this->prepare_v1_headers();
    }

    private function prepare_v1_headers(): void
    {
        $this->header = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->bearer_token
        ];
    }

    /**
     * Extension: Enhanced Android/iOS specific configurations
     */
    public function send_v1_notification(string $token, string $title, string $body, array &$extra_data = [])
    {
        $url = sprintf(self::FCM_V1_URL, $this->project_id);

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $extra_data,
                // Extension: Platform specific overrides
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'default_importance_channel',
                        'click_action' => 'OPEN_ACTIVITY_1'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];

        return $this->execute_curl($url, $payload);
    }

    /**
     * Extension: Batch Sending Logic
     * Useful for sending unique data to multiple users in one execution
     */
    public function send_batch(array $tokens, string $title, string $body): array
    {
        $results = [];
        foreach ($tokens as $token) {
            $results[] = $this->send_v1_notification($token, $title, $body);
        }
        return $results;
    }

    private function execute_curl(string $url, array $payload)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $this->header,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $http_code,
            'response' => json_decode($result, true)
        ];
    }
}

