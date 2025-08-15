# YagoutPay Python SDK

A modern Python SDK for integrating with the YagoutPay payment gateway. Built with FastAPI, Pydantic, and cryptography for type safety and security.

## Quick Setup

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. **Clone and navigate to the SDK**

   ```bash
   git clone https://github.com/bisryy/yagoutpay.git
   cd yagoutpay/yagoutpay-python
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
   http://localhost:8080
   ```

### Local Development

```bash
pip install -r requirements.txt
python demo/main.py
```

## Environment Variables

Create a `.env` file with:

```env
MERCHANT_ID=your_merchant_id
ENCRYPTION_KEY=your_encryption_key
ENVIRONMENT=test
BASE_URL=http://localhost:8080
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

- **Secure Encryption**: AES‑256‑CBC (PKCS#7) with base64 key and static IV
- **Hash Verification**: SHA-256 hashing with additional AES encryption
- **Type Safety**: Full Pydantic model validation
- **FastAPI Integration**: Modern async web framework
- **Beautiful UI**: Ride booking demo with modern design
- **Responsive**: Mobile-friendly interface
- **Production Ready**: Comprehensive error handling and validation

## Demo URLs

- **Home**: http://localhost:8080/
- **API Docs**: http://localhost:8080/docs
- **Health Check**: http://localhost:8080/health

## Demo Features

- Modern ride booking interface
- Interactive ride selection (Economy, Comfort, Premium)
- Prefilled trip details
- Dynamic pricing calculation
- Seamless payment integration
- Success/failure handling

## API Methods

- `create_payment(request)` - Creates payment request
- `create_payment_form(request)` - Generates payment form
- `verify_callback(data)` - Verifies payment callback
- `aes_encrypt_base64(text, key)` - AES-256-CBC encryption
- `sha256_hex(text)` - SHA-256 hash generation

## Support

For support and questions, check the demo application code or contact the YagoutPay team.
