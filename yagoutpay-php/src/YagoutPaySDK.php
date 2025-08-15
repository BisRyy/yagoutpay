<?php

namespace YagoutPay;

class YagoutPaySDK
{
    private $merchantId;
    private $encryptionKey;
    private $aggregatorId;
    private $postUrl;
    private $environment;

    const ENV_TEST = 'test';
    const ENV_PRODUCTION = 'production';

    const TEST_POST_URL = 'https://uatcheckout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage';

    public function __construct($merchantId, $encryptionKey, $environment = self::ENV_TEST, $postUrl = null)
    {
        $this->merchantId = $merchantId;
        $this->encryptionKey = $encryptionKey;
        $this->aggregatorId = 'yagout';
        $this->environment = $environment;
        
        if ($postUrl) {
            $this->postUrl = $postUrl;
        } else {
            $this->postUrl = $environment === self::ENV_TEST ? self::TEST_POST_URL : '';
        }
    }

    /**
     * AES-256-CBC encryption with PKCS#7 padding, static IV "0123456789abcdef"
     */
    public function aesEncryptBase64($plaintext, $base64Key = null)
    {
        $key = $base64Key ?: $this->encryptionKey;
        $decodedKey = base64_decode($key);
        
        if (strlen($decodedKey) !== 32) {
            throw new \Exception("Invalid key length: expected 32 bytes after base64 decode, got " . strlen($decodedKey));
        }
        
        $iv = "0123456789abcdef"; // 16 bytes
        $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $decodedKey, OPENSSL_RAW_DATA, $iv);
        
        if ($cipher === false) {
            throw new \Exception("Encryption failed: " . openssl_error_string());
        }
        
        return base64_encode($cipher);
    }

    /**
     * SHA-256 hash generation
     */
    public function sha256Hex($input)
    {
        return hash('sha256', $input);
    }

    /**
     * Build full merchant_request (sections joined by '~') and encrypt it.
     * Only me_id is sent in plain text separately.
     */
    public function buildEncryptedRequest($options)
    {
        $merchantId = $options['merchantId'] ?? $this->merchantId;
        $merchantKey = $options['merchantKey'] ?? $this->encryptionKey;
        $txnDetails = $options['txnDetails'] ?? [];
        $pgDetails = $options['pgDetails'] ?? [];
        $cardDetails = $options['cardDetails'] ?? [];
        $custDetails = $options['custDetails'] ?? [];
        $billDetails = $options['billDetails'] ?? [];
        $shipDetails = $options['shipDetails'] ?? [];
        $itemDetails = $options['itemDetails'] ?? [];
        $upiDetails = $options['upiDetails'] ?? [];
        $otherDetails = $options['otherDetails'] ?? [];

        // Defaults per docs; keep strict order of fields within each section
        $txn_defaults = [
            'ag_id' => 'yagout',
            'me_id' => $merchantId,
            'order_no' => '',
            'amount' => '',
            'country' => 'ETH',
            'currency' => 'ETB',
            'txn_type' => 'SALE',
            'success_url' => '',
            'failure_url' => '',
            'channel' => 'WEB',
        ];

        $pg_defaults = ['pg_id' => '', 'paymode' => '', 'scheme' => '', 'wallet_type' => ''];
        $card_defaults = ['card_no' => '', 'exp_month' => '', 'exp_year' => '', 'cvv' => '', 'card_name' => ''];
        $cust_defaults = ['cust_name' => '', 'email_id' => '', 'mobile_no' => '', 'unique_id' => '', 'is_logged_in' => 'Y'];
        $bill_defaults = ['bill_address' => '', 'bill_city' => '', 'bill_state' => '', 'bill_country' => '', 'bill_zip' => ''];
        $ship_defaults = ['ship_address' => '', 'ship_city' => '', 'ship_state' => '', 'ship_country' => '', 'ship_zip' => '', 'ship_days' => '', 'address_count' => ''];
        $item_defaults = ['item_count' => '', 'item_value' => '', 'item_category' => ''];
        $upi_defaults = []; // UPI details not specified in the guide
        $other_defaults = ['udf_1' => '', 'udf_2' => '', 'udf_3' => '', 'udf_4' => '', 'udf_5' => ''];

        $txn = array_merge($txn_defaults, $txnDetails);
        $pg = array_merge($pg_defaults, $pgDetails);
        $card = array_merge($card_defaults, $cardDetails);
        $cust = array_merge($cust_defaults, $custDetails);
        $bill = array_merge($bill_defaults, $billDetails);
        $ship = array_merge($ship_defaults, $shipDetails);
        $item = array_merge($item_defaults, $itemDetails);
        $upi = array_merge($upi_defaults, $upiDetails);
        $other = array_merge($other_defaults, $otherDetails);

        // Build section strings in documented order (fields pipe-delimited)
        $txn_str = $this->stringifySection($txn, ['ag_id', 'me_id', 'order_no', 'amount', 'country', 'currency', 'txn_type', 'success_url', 'failure_url', 'channel']);
        $pg_str = $this->stringifySection($pg, ['pg_id', 'paymode', 'scheme', 'wallet_type']);
        $card_str = $this->stringifySection($card, ['card_no', 'exp_month', 'exp_year', 'cvv', 'card_name']);
        $cust_str = $this->stringifySection($cust, ['cust_name', 'email_id', 'mobile_no', 'unique_id', 'is_logged_in']);
        $bill_str = $this->stringifySection($bill, ['bill_address', 'bill_city', 'bill_state', 'bill_country', 'bill_zip']);
        $ship_str = $this->stringifySection($ship, ['ship_address', 'ship_city', 'ship_state', 'ship_country', 'ship_zip', 'ship_days', 'address_count']);
        $item_str = $this->stringifySection($item, ['item_count', 'item_value', 'item_category']);
        $upi_str = $this->stringifySection($upi, []); // UPI details (unspecified): send empty string
        $other_str = $this->stringifySection($other, ['udf_1', 'udf_2', 'udf_3', 'udf_4', 'udf_5']);

        $full_message = implode('~', [$txn_str, $pg_str, $card_str, $cust_str, $bill_str, $ship_str, $item_str, $upi_str, $other_str]);

        $merchant_request = $this->aesEncryptBase64($full_message, $merchantKey);

        return [
            'me_id' => $merchantId,
            'merchant_request' => $merchant_request,
            'full_message' => $full_message
        ];
    }

    /**
     * Build encrypted hash according to instructions:
     * sha256Hex(`${merchantId}~${order_no}~${amount}~${currencyFrom}~${currencyTo}`) then AES encrypt.
     */
    public function buildEncryptedHash($options)
    {
        $merchantId = $options['merchantId'] ?? $this->merchantId;
        $merchantKey = $options['merchantKey'] ?? $this->encryptionKey;
        $order_no = $options['order_no'];
        $amount = $options['amount'];
        $currencyFrom = $options['currencyFrom'] ?? 'ETH';
        $currencyTo = $options['currencyTo'] ?? 'ETB';

        $hash_input = "{$merchantId}~{$order_no}~{$amount}~{$currencyFrom}~{$currencyTo}";
        $sha = $this->sha256Hex($hash_input);
        $encrypted_hash = $this->aesEncryptBase64($sha, $merchantKey);
        
        return [
            'hash' => $encrypted_hash,
            'hash_input' => $hash_input,
            'sha256' => $sha
        ];
    }

    /**
     * Utility function to stringify a section with ordered keys
     */
    private function stringifySection($obj, $orderedKeys)
    {
        // If orderedKeys is empty, return empty string (blank section placeholder)
        if (empty($orderedKeys)) {
            return '';
        }
        
        $values = [];
        foreach ($orderedKeys as $key) {
            $values[] = $obj[$key] ?? '';
        }
        
        return implode('|', $values);
    }

    /**
     * Generate HTML form for payment redirection
     */
    public function generatePaymentForm($orderData, $formId = 'paymentForm', $submitButtonText = 'Pay Now')
    {
        $paymentRequest = $this->createPaymentRequest($orderData);
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment Gateway</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .loading-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .loading-subtext {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">Redirecting to Payment Gateway</div>
        <div class="loading-subtext">Please wait while we process your request...</div>
    </div>
    
    <form name="' . $formId . '" method="POST" enctype="application/x-www-form-urlencoded" action="' . $paymentRequest['post_url'] . '" style="display: none;">
        <input name="me_id" value="' . htmlspecialchars($paymentRequest['me_id']) . '" type="hidden">
        <input name="merchant_request" value="' . htmlspecialchars($paymentRequest['merchant_request']) . '" type="hidden">
        <input name="hash" value="' . htmlspecialchars($paymentRequest['hash']) . '" type="hidden">
    </form>
    <script>
        // Auto-submit form after a brief delay to show loading
        setTimeout(function() {
            document.forms["' . $formId . '"].submit();
        }, 1500);
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Create payment request (legacy method for backward compatibility)
     */
    public function createPaymentRequest($orderData)
    {
        // Validate required fields
        $this->validateOrderData($orderData);

        // Build the encrypted request
        $encryptedRequest = $this->buildEncryptedRequest([
            'merchantId' => $this->merchantId,
            'merchantKey' => $this->encryptionKey,
            'txnDetails' => [
                'ag_id' => 'yagout',
                'me_id' => $this->merchantId,
                'order_no' => $orderData['order_no'],
                'amount' => $orderData['amount'],
                'country' => $orderData['country'] ?? 'ETH',
                'currency' => $orderData['currency'] ?? 'ETB',
                'txn_type' => $orderData['txn_type'] ?? 'SALE',
                'success_url' => $orderData['success_url'],
                'failure_url' => $orderData['failure_url'],
                'channel' => $orderData['channel'] ?? 'WEB'
            ],
            'custDetails' => [
                'cust_name' => $orderData['cust_name'] ?? '',
                'email_id' => $orderData['email_id'] ?? '',
                'mobile_no' => $orderData['mobile_no'] ?? '',
                'unique_id' => $orderData['unique_id'] ?? '',
                'is_logged_in' => $orderData['is_logged_in'] ?? 'Y'
            ],
            'billDetails' => [
                'bill_address' => $orderData['bill_address'] ?? '',
                'bill_city' => $orderData['bill_city'] ?? '',
                'bill_state' => $orderData['bill_state'] ?? '',
                'bill_country' => $orderData['bill_country'] ?? '',
                'bill_zip' => $orderData['bill_zip'] ?? ''
            ]
        ]);

        // Generate hash
        $hash = $this->buildEncryptedHash([
            'merchantId' => $this->merchantId,
            'merchantKey' => $this->encryptionKey,
            'order_no' => $orderData['order_no'],
            'amount' => $orderData['amount'],
            'currencyFrom' => $orderData['country'] ?? 'ETH',
            'currencyTo' => $orderData['currency'] ?? 'ETB'
        ]);

        return [
            'me_id' => $encryptedRequest['me_id'],
            'merchant_request' => $encryptedRequest['merchant_request'],
            'hash' => $hash['hash'],
            'post_url' => $this->postUrl
        ];
    }

    private function validateOrderData($orderData)
    {
        $required = ['order_no', 'amount', 'success_url', 'failure_url', 'email_id', 'mobile_no'];
        
        foreach ($required as $field) {
            if (!isset($orderData[$field]) || empty($orderData[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }
    }

    /**
     * Parse response from YagoutPay
     */
    public function parseResponse($responseData)
    {
        if (!isset($responseData['merchant_response']) || !isset($responseData['hash'])) {
            throw new \InvalidArgumentException('Invalid response data');
        }

        // Verify hash
        $expectedHash = $this->generateResponseHash($responseData['merchant_response']);
        if ($expectedHash !== $responseData['hash']) {
            throw new \Exception('Hash verification failed');
        }

        // Decrypt response
        $decryptedResponse = $this->decrypt($responseData['merchant_response']);
        if ($decryptedResponse === false || $decryptedResponse === 'Error') {
            throw new \Exception('Failed to decrypt response');
        }

        return $this->parseDecryptedResponse($decryptedResponse);
    }

    public function generateResponseHash($encryptedResponse)
    {
        $hashString = $this->merchantId . $encryptedResponse . $this->encryptionKey;
        return base64_encode(hash('sha256', $hashString, true));
    }

    private function decrypt($crypt, $key = null, $type = 256)
    {
        $key = $key ?: $this->encryptionKey;
        $iv = "0123456789abcdef";
        $crypt = base64_decode($crypt);
        $decrypted = openssl_decrypt($crypt, 'AES-256-CBC', base64_decode($key), OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            return false;
        }
        
        return $decrypted;
    }

    private function parseDecryptedResponse($decryptedResponse)
    {
        $parts = explode('|', $decryptedResponse);
        
        return [
            'status' => $parts[0] ?? '',
            'order_no' => $parts[1] ?? '',
            'amount' => $parts[2] ?? '',
            'txn_id' => $parts[3] ?? '',
            'bank_ref_no' => $parts[4] ?? '',
            'response_code' => $parts[5] ?? '',
            'response_message' => $parts[6] ?? '',
            'payment_mode' => $parts[7] ?? '',
            'card_type' => $parts[8] ?? '',
            'masked_card_no' => $parts[9] ?? '',
            'udf_1' => $parts[10] ?? '',
            'udf_2' => $parts[11] ?? '',
            'udf_3' => $parts[12] ?? '',
            'udf_4' => $parts[13] ?? '',
            'udf_5' => $parts[14] ?? ''
        ];
    }
}
