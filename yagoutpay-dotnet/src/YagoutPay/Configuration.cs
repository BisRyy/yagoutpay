namespace YagoutPay;

/// <summary>
/// Configuration settings for YagoutPay
/// </summary>
public class YagoutPayConfig
{
    /// <summary>
    /// Merchant ID from YagoutPay
    /// </summary>
    public string MerchantId { get; set; } = string.Empty;

    /// <summary>
    /// Encryption key from YagoutPay
    /// </summary>
    public string EncryptionKey { get; set; } = string.Empty;

    /// <summary>
    /// Environment: "test" or "production"
    /// </summary>
    public string Environment { get; set; } = "test";

    /// <summary>
    /// Base URL for callbacks
    /// </summary>
    public string BaseUrl { get; set; } = "http://localhost:5001";
}


