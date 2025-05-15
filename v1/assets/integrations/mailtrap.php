<?php
namespace Mailtrap;

use cUtils\cUtils;

class Mailtrap
{

    private static $API_URL;
    private static $API_TOKEN;
    private static $FROM_EMAIL;
    private static $FROM_NAME;

    private static function init()
    {
        if (self::$API_URL === null) {
            self::$API_URL     = cUtils::config('MAILTRAP_API_URL');
            self::$API_TOKEN   = cUtils::config('MAILTRAP_API_TOKEN');
            self::$FROM_EMAIL  = cUtils::config('MAIL_FROM_ADDRESS');
            self::$FROM_NAME   = cUtils::config('MAIL_FROM_NAME');
        }
    }

    public static function send($toEmail, $toName, $subject, $textBody, $htmlBody = null)
    {
        self::init();

        $body = [
            'from' => [
                'email' => self::$FROM_EMAIL,
                'name'  => self::$FROM_NAME
            ],
            'to' => [[
                'email' => $toEmail,
                'name'  => $toName
            ]],
            'subject' => $subject,
            'text'    => $textBody,
        ];

        if ($htmlBody) {
            $body['html'] = $htmlBody;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::$API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::$API_TOKEN,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body)
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        // Handle cURL error
        if ($response === false || $curlError) {
            return cUtils::returnData(true, null, 'Failed to send email: ' . $curlError, false, $httpCode ?: 500);
        }

        $decoded = json_decode($response, true);
        $success = $httpCode >= 200 && $httpCode < 300;

        return cUtils::returnData($success, $success ? 'Email sent successfully.' : 'Failed to send email.', $decoded, true, $httpCode);

    }

}
