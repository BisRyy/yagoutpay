using System.Security.Cryptography;
using System.Text;

namespace YagoutPay;

/// <summary>
/// Cryptographic utilities for YagoutPay integration
/// </summary>
public static class Crypto
{
    private static readonly byte[] StaticIv = Encoding.UTF8.GetBytes("0123456789abcdef"); // 16 bytes

    /// <summary>
    /// AES-256-CBC encryption with base64 key and PKCS#7 padding
    /// </summary>
    /// <param name="plaintext">UTF8 string to encrypt</param>
    /// <param name="base64Key">Base64-encoded 32-byte key</param>
    /// <returns>Base64 ciphertext</returns>
    /// <exception cref="ArgumentException">Thrown when key length is invalid</exception>
    public static string AesEncryptBase64(string plaintext, string base64Key)
    {
        var key = Convert.FromBase64String(base64Key);
        if (key.Length != 32)
        {
            throw new ArgumentException($"Invalid key length: expected 32 bytes after base64 decode, got {key.Length}");
        }

        using var aes = Aes.Create();
        aes.Key = key;
        aes.IV = StaticIv;
        aes.Mode = CipherMode.CBC;
        aes.Padding = PaddingMode.PKCS7;

        using var encryptor = aes.CreateEncryptor();
        var plaintextBytes = Encoding.UTF8.GetBytes(plaintext);
        var ciphertextBytes = encryptor.TransformFinalBlock(plaintextBytes, 0, plaintextBytes.Length);
        
        return Convert.ToBase64String(ciphertextBytes);
    }

    /// <summary>
    /// AES-256-CBC decryption with base64 key and PKCS#7 padding
    /// </summary>
    /// <param name="base64Ciphertext">Base64 ciphertext to decrypt</param>
    /// <param name="base64Key">Base64-encoded 32-byte key</param>
    /// <returns>Decrypted UTF8 string</returns>
    /// <exception cref="ArgumentException">Thrown when key length is invalid</exception>
    public static string AesDecryptBase64(string base64Ciphertext, string base64Key)
    {
        var key = Convert.FromBase64String(base64Key);
        if (key.Length != 32)
        {
            throw new ArgumentException($"Invalid key length: expected 32 bytes after base64 decode, got {key.Length}");
        }

        using var aes = Aes.Create();
        aes.Key = key;
        aes.IV = StaticIv;
        aes.Mode = CipherMode.CBC;
        aes.Padding = PaddingMode.PKCS7;

        using var decryptor = aes.CreateDecryptor();
        var ciphertextBytes = Convert.FromBase64String(base64Ciphertext);
        var plaintextBytes = decryptor.TransformFinalBlock(ciphertextBytes, 0, ciphertextBytes.Length);
        
        return Encoding.UTF8.GetString(plaintextBytes);
    }

    /// <summary>
    /// Generate SHA256 hash of input string
    /// </summary>
    /// <param name="input">Input string to hash</param>
    /// <returns>Hexadecimal hash string</returns>
    public static string Sha256Hex(string input)
    {
        using var sha256 = SHA256.Create();
        var inputBytes = Encoding.UTF8.GetBytes(input);
        var hashBytes = sha256.ComputeHash(inputBytes);
        
        return Convert.ToHexString(hashBytes).ToLowerInvariant();
    }

    /// <summary>
    /// Stringify a section object with ordered keys
    /// </summary>
    /// <param name="obj">Object to stringify</param>
    /// <param name="orderedKeys">Ordered list of keys</param>
    /// <returns>Pipe-delimited string of values</returns>
    public static string StringifySection(Dictionary<string, string> obj, string[] orderedKeys)
    {
        if (orderedKeys == null || orderedKeys.Length == 0)
            return string.Empty;

        return string.Join("|", orderedKeys.Select(k => obj.GetValueOrDefault(k, string.Empty)));
    }
}


