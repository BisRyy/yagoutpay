# YagoutPay JavaScript SDK

A modern JavaScript SDK for integrating YagoutPay payment gateway into your applications.

## Quick Setup

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. **Clone and navigate to the SDK**

   ```bash
   git clone https://github.com/bisryy/yagoutpay.git
   cd yagoutpay/yagoutpay-js
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
   http://localhost:3000
   ```

### Local Development

```bash
cd demo
npm install
npm start
```

## Environment Variables

Create a `.env` file with:

```env
MERCHANT_ID=your_merchant_id
ENCRYPTION_KEY=your_encryption_key
ENVIRONMENT=test
BASE_URL=http://localhost:3000
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

- **Secure Encryption**: AES-256-CBC encryption with PKCS#7 padding
- **Hash Verification**: SHA-256 hashing with additional AES encryption layer
- **Docker Support**: Production-ready Docker configuration
- **Modern Demo**: Ride booking service with beautiful UI/UX
- **Auto-submission**: Seamless payment flow with loading screens

## API Methods

- `buildEncryptedRequest(data)` - Builds encrypted payment request
- `buildEncryptedHash(data)` - Generates encrypted hash for verification
- `aesEncryptBase64(text, key)` - AES-256-CBC encryption
- `sha256Hex(text)` - SHA-256 hash generation

## Demo Features

- Modern ride booking interface
- Interactive ride selection (Economy, Comfort, Premium)
- Prefilled trip details
- Dynamic pricing calculation
- Seamless payment integration
- Success/failure handling

## Support

For support and questions, check the demo application code or contact the YagoutPay team.
