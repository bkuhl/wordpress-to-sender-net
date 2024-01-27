<?php

class Plugin_Name_Sender_Net_Lib {

    public static function apiToken(): string
    {
        return self::decryptApiToken(get_option('api_token'));
    }

    public static function decryptApiToken(string $token): string
    {
        return openssl_decrypt($token, 'AES-256-CBC', NONCE_KEY);
    }

    public static function encryptApiToken(string $token): string
    {
        return openssl_encrypt($token, 'AES-256-CBC', NONCE_KEY);
    }

    function getGroups(string $apiToken): array
    {
        $url = 'https://api.sender.net/v2/groups';

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
        ));

        if ($response['response']['code'] === 401) {
            throw new RuntimeException('Unable to authenticate with Sender.net using that API token, please try again.');
        }

        if (is_wp_error($response)) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $groups = [];
        foreach ($data['data'] as $group) {
            $groups[] = [
                'id' => $group['id'],
                'name' => $group['title'],
            ];
        }
        return $groups;
    }

}
