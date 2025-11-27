<?php
// whatsapp_helper.php
// دالة عامة لإرسال رسالة واتساب باستخدام WhatsApp Cloud API من Meta

function sendWhatsAppMessage($toNumber, $text)
{
    // رقم المستلم بصيغة دولية بدون + (مثال: 9665XXXXXXXX)
    $accessToken   = 'YOUR_WHATSAPP_ACCESS_TOKEN'; // ضع التوكن من Meta
    $phoneNumberId = 'YOUR_PHONE_NUMBER_ID';       // ضع رقم الواتساب ID من Meta

    $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

    $payload = [
        "messaging_product" => "whatsapp",
        "to"   => $toNumber,
        "type" => "text",
        "text" => [
            "body" => $text
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    // لو حاب تشوف الرد أو الأخطاء:
    /*
    if ($response === false) {
        error_log('WhatsApp cURL error: ' . curl_error($ch));
    } else {
        error_log('WhatsApp response: ' . $response);
    }
    */

    curl_close($ch);
    return $response;
}
