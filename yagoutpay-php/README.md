# YagoutPay PHP SDK

A lightweight, production-ready PHP SDK for YagoutPay Aggregator Hosted integration with AES-256-CBC encryption and SHA-256 hash verification.

## Quick Setup

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. **Clone and navigate to the SDK**

   ```bash
   git clone https://github.com/bisryy/yagoutpay.git
   cd yagoutpay/yagoutpay-php
   ```

2. **Set up environment variables**

   ```bash
   cp .env.example .env
   # Edit .env with your credentials
   ```

3. **Run with Docker**

   ```bash
   docker-compose up -d --build
   ```

4. **Access the demo**
   ```
   http://localhost:8000
   ```

### Local Development

```bash
php -S localhost:8000 -t demo
```

## Environment Variables

Create a `.env` file with:

```env
MERCHANT_ID=your_merchant_id
ENCRYPTION_KEY=your_encryption_key
ENVIRONMENT=test
BASE_URL=http://localhost:8000
```

## Docker Commands

```bash
# Build and start
docker-compose up -d --build

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

## Features

- **AES-256-CBC Encryption** with PKCS#7 padding
- **SHA-256 Hash Verification** for secure transactions
- **Modern API Design** with granular control over request building
- **Production Ready** with comprehensive error handling
- **Complete Demo Application** - Ride booking service with beautiful UI/UX
- **Docker Support** for easy deployment and testing

## Demo Features

- Modern ride booking interface
- Interactive ride selection (Economy, Comfort, Premium)
- Prefilled trip details
- Dynamic pricing calculation
- Seamless payment integration
- Success/failure handling

## API Methods

- `buildEncryptedRequest($data)` - Builds encrypted payment request
- `buildEncryptedHash($data)` - Generates encrypted hash for verification
- `aesEncryptBase64($text, $key)` - AES-256-CBC encryption
- `sha256Hex($text)` - SHA-256 hash generation
- `generatePaymentForm($data)` - Creates auto-submitting payment form

## Support

For support and questions, check the demo application code or contact the YagoutPay team.
