using System.Text;

namespace YagoutPay;

/// <summary>
/// Main YagoutPay client for payment integration
/// </summary>
public class YagoutPayClient
{
    /// <summary>
    /// Test environment payment gateway URL
    /// </summary>
    public const string TestPostUrl = "https://uatcheckout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage";
    
    /// <summary>
    /// Production environment payment gateway URL
    /// </summary>
    public const string ProdPostUrl = "https://checkout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage";

    private readonly string _merchantId;
    private readonly string _encryptionKey;
    private readonly string _environment;
    private readonly string _postUrl;

    /// <summary>
    /// Initialize YagoutPay client
    /// </summary>
    public YagoutPayClient(string merchantId, string encryptionKey, string environment = "test")
    {
        _merchantId = merchantId ?? throw new ArgumentNullException(nameof(merchantId));
        _encryptionKey = encryptionKey ?? throw new ArgumentNullException(nameof(encryptionKey));
        _environment = environment?.ToLower() ?? "test";
        _postUrl = _environment == "test" ? TestPostUrl : ProdPostUrl;
    }

    /// <summary>
    /// Create a payment request
    /// </summary>
    public PaymentResponse CreatePayment(PaymentRequest paymentRequest)
    {
        if (paymentRequest == null || !paymentRequest.IsValid())
            throw new ArgumentException("Invalid payment request");

        var txnDetails = new Dictionary<string, string>
        {
            ["ag_id"] = "yagout",
            ["me_id"] = _merchantId,
            ["order_no"] = paymentRequest.Transaction.OrderNo,
            ["amount"] = paymentRequest.Transaction.Amount.ToString(),
            ["country"] = paymentRequest.Transaction.Country,
            ["currency"] = paymentRequest.Transaction.Currency,
            ["txn_type"] = paymentRequest.Transaction.TxnType,
            ["success_url"] = paymentRequest.Transaction.SuccessUrl,
            ["failure_url"] = paymentRequest.Transaction.FailureUrl,
            ["channel"] = paymentRequest.Transaction.Channel,
        };

        var custDetails = new Dictionary<string, string>
        {
            ["cust_name"] = paymentRequest.Customer.CustName,
            ["email_id"] = paymentRequest.Customer.EmailId,
            ["mobile_no"] = paymentRequest.Customer.MobileNo,
            ["unique_id"] = paymentRequest.Customer.UniqueId ?? string.Empty,
            ["is_logged_in"] = paymentRequest.Customer.IsLoggedIn,
        };

        var billDetails = new Dictionary<string, string>();
        if (paymentRequest.Billing != null)
        {
            billDetails = new Dictionary<string, string>
            {
                ["bill_address"] = paymentRequest.Billing.BillAddress ?? string.Empty,
                ["bill_city"] = paymentRequest.Billing.BillCity ?? string.Empty,
                ["bill_state"] = paymentRequest.Billing.BillState ?? string.Empty,
                ["bill_country"] = paymentRequest.Billing.BillCountry ?? string.Empty,
                ["bill_zip"] = paymentRequest.Billing.BillZip ?? string.Empty,
            };
        }

        var requestData = new Dictionary<string, object>
        {
            ["merchantId"] = _merchantId,
            ["merchantKey"] = _encryptionKey,
            ["txnDetails"] = txnDetails,
            ["pgDetails"] = new Dictionary<string, string>(),
            ["cardDetails"] = new Dictionary<string, string>(),
            ["custDetails"] = custDetails,
            ["billDetails"] = billDetails,
            ["shipDetails"] = new Dictionary<string, string>(),
            ["itemDetails"] = new Dictionary<string, string>(),
            ["upiDetails"] = new Dictionary<string, string>(),
            ["otherDetails"] = new Dictionary<string, string>(),
        };

        var hashData = new Dictionary<string, object>
        {
            ["merchantId"] = _merchantId,
            ["merchantKey"] = _encryptionKey,
            ["order_no"] = paymentRequest.Transaction.OrderNo,
            ["amount"] = paymentRequest.Transaction.Amount.ToString(),
            ["currencyFrom"] = paymentRequest.Transaction.Country,
            ["currencyTo"] = paymentRequest.Transaction.Currency,
        };

        var encryptedRequest = BuildEncryptedRequest(requestData);
        var encryptedHash = BuildEncryptedHash(hashData);

        return new PaymentResponse
        {
            MeId = encryptedRequest["me_id"],
            MerchantRequest = encryptedRequest["merchant_request"],
            Hash = encryptedHash["hash"],
            PostUrl = _postUrl,
        };
    }

    /// <summary>
    /// Generate HTML form for payment redirection
    /// </summary>
    public string CreatePaymentForm(PaymentRequest paymentRequest, string formId = "paymentForm")
    {
        var paymentResponse = CreatePayment(paymentRequest);

        return $@"<!DOCTYPE html>
<html>
<head>
    <meta charset=""UTF-8"">
    <title>Redirecting to Payment Gateway</title>
    <style>
        body {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }}
        .loading-container {{
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
        }}
        .spinner {{
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }}
        @keyframes spin {{
            0% {{ transform: rotate(0deg); }}
            100% {{ transform: rotate(360deg); }}
        }}
        .loading-text {{
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }}
        .loading-subtext {{
            color: #666;
            font-size: 14px;
        }}
    </style>
</head>
<body>
    <div class=""loading-container"">
        <div class=""spinner""></div>
        <div class=""loading-text"">Redirecting to Payment Gateway</div>
        <div class=""loading-subtext"">Please wait while we process your request...</div>
    </div>
    
    <form name=""{formId}"" method=""POST"" enctype=""application/x-www-form-urlencoded"" action=""{paymentResponse.PostUrl}"" style=""display: none;"">
        <input name=""me_id"" value=""{paymentResponse.MeId}"" type=""hidden"">
        <input name=""merchant_request"" value=""{paymentResponse.MerchantRequest}"" type=""hidden"">
        <input name=""hash"" value=""{paymentResponse.Hash}"" type=""hidden"">
    </form>
    <script>
        setTimeout(function() {{
            document.forms[""{formId}""].submit();
        }}, 1500);
    </script>
</body>
</html>";
    }

    /// <summary>
    /// Generate a unique order number
    /// </summary>
    public static string GenerateOrderNumber(string prefix = "ORDER")
    {
        var timestamp = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds();
        var randomSuffix = Random.Shared.Next(1000, 10000);
        return $"{prefix}_{timestamp}_{randomSuffix}";
    }

    /// <summary>
    /// Build encrypted request according to YagoutPay specifications
    /// </summary>
    private Dictionary<string, string> BuildEncryptedRequest(Dictionary<string, object> requestData)
    {
        var merchantId = (string)requestData["merchantId"];
        var merchantKey = (string)requestData["merchantKey"];

        var txnDetails = (Dictionary<string, string>)requestData["txnDetails"];
        var pgDetails = (Dictionary<string, string>)requestData["pgDetails"];
        var cardDetails = (Dictionary<string, string>)requestData["cardDetails"];
        var custDetails = (Dictionary<string, string>)requestData["custDetails"];
        var billDetails = (Dictionary<string, string>)requestData["billDetails"];
        var shipDetails = (Dictionary<string, string>)requestData["shipDetails"];
        var itemDetails = (Dictionary<string, string>)requestData["itemDetails"];
        var upiDetails = (Dictionary<string, string>)requestData["upiDetails"];
        var otherDetails = (Dictionary<string, string>)requestData["otherDetails"];

        var txnStr = Crypto.StringifySection(txnDetails, ["ag_id", "me_id", "order_no", "amount", "country", "currency", "txn_type", "success_url", "failure_url", "channel"]);
        var pgStr = Crypto.StringifySection(pgDetails, ["pg_id", "paymode", "scheme", "wallet_type"]);
        var cardStr = Crypto.StringifySection(cardDetails, ["card_no", "exp_month", "exp_year", "cvv", "card_name"]);
        var custStr = Crypto.StringifySection(custDetails, ["cust_name", "email_id", "mobile_no", "unique_id", "is_logged_in"]);
        var billStr = Crypto.StringifySection(billDetails, ["bill_address", "bill_city", "bill_state", "bill_country", "bill_zip"]);
        var shipStr = Crypto.StringifySection(shipDetails, ["ship_address", "ship_city", "ship_state", "ship_country", "ship_zip", "ship_days", "address_count"]);
        var itemStr = Crypto.StringifySection(itemDetails, ["item_count", "item_value", "item_category"]);
        var upiStr = Crypto.StringifySection(upiDetails, []);
        var otherStr = Crypto.StringifySection(otherDetails, ["udf_1", "udf_2", "udf_3", "udf_4", "udf_5"]);

        var fullMessage = string.Join("~", [txnStr, pgStr, cardStr, custStr, billStr, shipStr, itemStr, upiStr, otherStr]);
        var merchantRequest = Crypto.AesEncryptBase64(fullMessage, merchantKey);

        return new Dictionary<string, string>
        {
            ["me_id"] = merchantId,
            ["merchant_request"] = merchantRequest,
            ["full_message"] = fullMessage
        };
    }

    /// <summary>
    /// Build encrypted hash according to YagoutPay specifications
    /// </summary>
    private Dictionary<string, string> BuildEncryptedHash(Dictionary<string, object> hashData)
    {
        var merchantId = (string)hashData["merchantId"];
        var merchantKey = (string)hashData["merchantKey"];
        var orderNo = (string)hashData["order_no"];
        var amount = (string)hashData["amount"];
        var currencyFrom = (string)hashData["currencyFrom"];
        var currencyTo = (string)hashData["currencyTo"];

        var hashInput = $"{merchantId}~{orderNo}~{amount}~{currencyFrom}~{currencyTo}";
        var sha = Crypto.Sha256Hex(hashInput);
        var encryptedHash = Crypto.AesEncryptBase64(sha, merchantKey);

        return new Dictionary<string, string>
        {
            ["hash"] = encryptedHash,
            ["hash_input"] = hashInput,
            ["sha256"] = sha
        };
    }
}
