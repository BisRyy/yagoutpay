# Changelog

All notable changes to the YagoutPay .NET SDK will be documented in this file.

## [1.0.0] - 2024-01-30

### Added

- Initial release of YagoutPay .NET SDK
- AES-256-CBC encryption with PKCS#7 padding
- SHA-256 hash verification
- Type-safe models with validation
- YagoutPayClient for payment integration
- Configuration support with dependency injection
- Docker support for easy deployment
- Comprehensive documentation and examples

### Features

- **Crypto Utilities**: AES encryption/decryption and SHA256 hashing
- **Payment Models**: CustomerDetails, TransactionDetails, PaymentRequest, etc.
- **Client Integration**: Easy-to-use YagoutPayClient class
- **Validation**: Built-in validation for Ethiopian mobile numbers, emails, amounts
- **Order Generation**: Automatic unique order number generation
- **HTML Form Generation**: Ready-to-use payment form HTML

### Technical Details

- Target Framework: .NET 8.0
- Dependencies: System.Security.Cryptography.Algorithms, System.Text.Encoding.CodePages
- License: MIT
- Documentation: XML comments for IntelliSense support


