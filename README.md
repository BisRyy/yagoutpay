# YagoutPay SDK Collection

A comprehensive collection of SDKs for integrating YagoutPay payment gateway into your applications. This repository contains official SDKs for JavaScript, PHP, Python, and .NET, each with modern implementations, secure encryption, and beautiful demo applications.

## Quick Setup

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/bisryy/yagoutpay.git
   cd yagoutpay
   ```

2. **Choose your SDK and set up environment**

   ```bash
   # For JavaScript SDK
   cd yagoutpay-js
   cp env.example .env
   # Edit .env with your credentials
   docker-compose up -d --build
   # Access at http://localhost:3000

   # For PHP SDK
   cd ../yagoutpay-php
   cp env.example .env
   # Edit .env with your credentials
   docker-compose up -d --build
   # Access at http://localhost:8000

   # For Python SDK
   cd ../yagoutpay-python
   cp env.example .env
   # Edit .env with your credentials
   docker-compose up -d --build
   # Access at http://localhost:8080

   # For .NET SDK
   cd ../yagoutpay-dotnet
   cp env.example .env
   # Edit .env with your credentials
   docker-compose up -d --build
   # Access at http://localhost:5001
   ```

3. **Environment Variables**
   Each SDK requires these environment variables in `.env`:
   ```env
   MERCHANT_ID=your_merchant_id
   ENCRYPTION_KEY=your_encryption_key
   ENVIRONMENT=test
   BASE_URL=http://localhost:PORT
   ```

## SDKs Overview

| SDK                               | Language   | Port | Demo         |
| --------------------------------- | ---------- | ---- | ------------ |
| [JavaScript SDK](./yagoutpay-js/) | JavaScript | 3000 | Ride Booking |
| [PHP SDK](./yagoutpay-php/)       | PHP        | 8000 | Ride Booking |
| [Python SDK](./yagoutpay-python/) | Python     | 8080 | Ride Booking |
| [.NET SDK](./yagoutpay-dotnet/)   | .NET       | 5001 | Ride Booking |

## Features

- **Secure Encryption**: AES-256-CBC encryption with PKCS#7 padding
- **Hash Verification**: SHA-256 hashing with additional AES encryption layer
- **Docker Support**: Production-ready Docker configurations
- **Modern Demos**: Beautiful ride booking service demos
- **Seamless Flow**: Auto-submission with loading screens

## Docker Commands

```bash
# Build and start
docker-compose up -d --build

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Rebuild and restart
docker-compose down && docker-compose up -d --build
```

## Documentation

- [JavaScript SDK Documentation](./yagoutpay-js/README.md)
- [PHP SDK Documentation](./yagoutpay-php/README.md)
- [Python SDK Documentation](./yagoutpay-python/README.md)
- [.NET SDK Documentation](./yagoutpay-dotnet/README.md)

## Support

For support and questions, check the individual SDK documentation or contact the YagoutPay team.
