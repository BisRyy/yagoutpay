using System.ComponentModel.DataAnnotations;
using System.Text.RegularExpressions;

namespace YagoutPay;

/// <summary>
/// Customer details for payment
/// </summary>
public class CustomerDetails
{
    /// <summary>
    /// Customer full name
    /// </summary>
    [Required]
    public string CustName { get; set; } = string.Empty;

    /// <summary>
    /// Customer email address
    /// </summary>
    [Required]
    [EmailAddress]
    public string EmailId { get; set; } = string.Empty;

    /// <summary>
    /// Customer mobile number (Ethiopian format: 09XXXXXXXX)
    /// </summary>
    [Required]
    public string MobileNo { get; set; } = string.Empty;

    /// <summary>
    /// Unique customer identifier
    /// </summary>
    public string? UniqueId { get; set; }

    /// <summary>
    /// Login status (default: Y)
    /// </summary>
    public string IsLoggedIn { get; set; } = "Y";

    /// <summary>
    /// Validates the customer details
    /// </summary>
    /// <returns>True if valid, false otherwise</returns>
    public bool IsValid()
    {
        if (string.IsNullOrWhiteSpace(CustName) || string.IsNullOrWhiteSpace(EmailId) || string.IsNullOrWhiteSpace(MobileNo))
            return false;

        // Validate email format
        if (!Regex.IsMatch(EmailId, @"^[^@]+@[^@]+\.[^@]+$"))
            return false;

        // Validate Ethiopian mobile number format: 09XXXXXXXX
        if (!Regex.IsMatch(MobileNo, @"^09\d{8}$"))
            return false;

        return true;
    }
}

/// <summary>
/// Billing address details
/// </summary>
public class BillingDetails
{
    /// <summary>
    /// Billing address
    /// </summary>
    public string? BillAddress { get; set; }

    /// <summary>
    /// Billing city
    /// </summary>
    public string? BillCity { get; set; }

    /// <summary>
    /// Billing state
    /// </summary>
    public string? BillState { get; set; }

    /// <summary>
    /// Billing country
    /// </summary>
    public string? BillCountry { get; set; }

    /// <summary>
    /// Billing zip code
    /// </summary>
    public string? BillZip { get; set; }
}

/// <summary>
/// Transaction details for payment
/// </summary>
public class TransactionDetails
{
    /// <summary>
    /// Unique order number
    /// </summary>
    [Required]
    public string OrderNo { get; set; } = string.Empty;

    /// <summary>
    /// Payment amount (must be greater than 0)
    /// </summary>
    [Range(0.01, double.MaxValue, ErrorMessage = "Amount must be greater than 0")]
    public decimal Amount { get; set; }

    /// <summary>
    /// Country code (default: ETH)
    /// </summary>
    public string Country { get; set; } = "ETH";

    /// <summary>
    /// Currency code (default: ETB)
    /// </summary>
    public string Currency { get; set; } = "ETB";

    /// <summary>
    /// Transaction type (default: SALE)
    /// </summary>
    public string TxnType { get; set; } = "SALE";

    /// <summary>
    /// Success callback URL
    /// </summary>
    [Required]
    [Url]
    public string SuccessUrl { get; set; } = string.Empty;

    /// <summary>
    /// Failure callback URL
    /// </summary>
    [Required]
    [Url]
    public string FailureUrl { get; set; } = string.Empty;

    /// <summary>
    /// Payment channel (default: WEB)
    /// </summary>
    public string Channel { get; set; } = "WEB";

    /// <summary>
    /// Validates the transaction details
    /// </summary>
    /// <returns>True if valid, false otherwise</returns>
    public bool IsValid()
    {
        if (string.IsNullOrWhiteSpace(OrderNo) || Amount <= 0 || 
            string.IsNullOrWhiteSpace(SuccessUrl) || string.IsNullOrWhiteSpace(FailureUrl))
            return false;

        return true;
    }
}

/// <summary>
/// Complete payment request
/// </summary>
public class PaymentRequest
{
    /// <summary>
    /// Transaction details
    /// </summary>
    [Required]
    public TransactionDetails Transaction { get; set; } = new();

    /// <summary>
    /// Customer details
    /// </summary>
    [Required]
    public CustomerDetails Customer { get; set; } = new();

    /// <summary>
    /// Billing details (optional)
    /// </summary>
    public BillingDetails? Billing { get; set; }

    /// <summary>
    /// Validates the payment request
    /// </summary>
    /// <returns>True if valid, false otherwise</returns>
    public bool IsValid()
    {
        return Transaction.IsValid() && Customer.IsValid();
    }
}

/// <summary>
/// Payment gateway response
/// </summary>
public class PaymentResponse
{
    /// <summary>
    /// Merchant ID
    /// </summary>
    public string MeId { get; set; } = string.Empty;

    /// <summary>
    /// Encrypted merchant request
    /// </summary>
    public string MerchantRequest { get; set; } = string.Empty;

    /// <summary>
    /// Request hash
    /// </summary>
    public string Hash { get; set; } = string.Empty;

    /// <summary>
    /// Payment gateway URL
    /// </summary>
    public string PostUrl { get; set; } = string.Empty;
}

/// <summary>
/// Payment callback from gateway
/// </summary>
public class PaymentCallback
{
    /// <summary>
    /// Order number
    /// </summary>
    public string OrderNo { get; set; } = string.Empty;

    /// <summary>
    /// Payment amount
    /// </summary>
    public string Amount { get; set; } = string.Empty;

    /// <summary>
    /// Payment status
    /// </summary>
    public string Status { get; set; } = string.Empty;

    /// <summary>
    /// Response hash
    /// </summary>
    public string Hash { get; set; } = string.Empty;

    /// <summary>
    /// Encrypted response
    /// </summary>
    public string MerchantRequest { get; set; } = string.Empty;
}


