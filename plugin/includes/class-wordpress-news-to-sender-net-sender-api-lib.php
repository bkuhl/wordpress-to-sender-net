<?php

class WordpressToSender_Sender_Net_Lib {

    public static function apiToken(): string
    {
        return self::decryptApiToken(get_option(WordpressToSender::OPTION_API_TOKEN));
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
        $response = wp_remote_get('https://api.sender.net/v2/groups', array(
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ));

        if ($response['response']['code'] === 401) {
            throw new RuntimeException('Unable to authenticate with Sender.net using that API token, please try again.');
        } elseif ($response['response']['code'] !== 200) {
            throw new RuntimeException('Unknown error occurred, response code '.$response['response']['code'].' received');
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

    function createCampaign(
        string $subject,
        string $content
    ): string
    {
        $groups = get_option(WordpressToSender::OPTION_SELECTED_GROUPS, []);

        $response = wp_safe_remote_post('https://api.sender.net/v2/campaigns', array(
            'headers' => [
                'Authorization' => 'Bearer ' . self::apiToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => wp_json_encode([
                'title' => $subject,
                'subject' => $subject,
                'from' => get_option(WordpressToSender::OPTION_REPLY_TO),
                'reply_to' => get_option(WordpressToSender::OPTION_REPLY_TO),
                'content_type' => 'html',
                'groups' => $groups,
                'content' => $content,
            ]),
        ));

        if ($response['response']['code'] === 401) {
            throw new RuntimeException('Unable to authenticate with Sender.net using that API token, please try again.');
        }

        if ($response['response']['code'] === 422) {
            throw new RuntimeException('Failed to create: '.$response['response']['message']);
        }

        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return $data['data']['id'];
    }

    function sendCampaign(
        string $id
    ): bool
    {
        $response = wp_safe_remote_post('https://api.sender.net/v2/campaigns/'.$id.'/send', array(
            'headers' => [
                'Authorization' => 'Bearer ' . self::apiToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ));

        if ($response['response']['code'] === 401) {
            throw new RuntimeException('Unable to authenticate with Sender.net using that API token, please try again.');
        }

        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }

    function getCampaignDetails(
        string $campaignId
    ): string
    {
        $response = wp_remote_get('https://api.sender.net/v2/campaigns/'.$campaignId, array(
            'headers' => [
                'Authorization' => 'Bearer ' . self::apiToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ));

        if ($response['response']['code'] === 401) {
            throw new RuntimeException('Unable to authenticate with Sender.net using that API token, please try again.');
        }

        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return $data['data'];
    }

}
