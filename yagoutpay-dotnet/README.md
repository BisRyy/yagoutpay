# YagoutPay .NET SDK

A modern .NET SDK for integrating YagoutPay payment gateway into your applications.

## Quick Setup

### Prerequisites

- Docker and Docker Compose
- Git
- .NET 8 SDK

### Installation

1. Clone and navigate to the SDK

```bash
git clone https://github.com/bisryy/yagoutpay.git
cd yagoutpay/yagoutpay-dotnet
```

2. Set up environment variables

```bash
cp env.example .env
# Edit .env with your credentials
```

3. Run with Docker

```bash
docker-compose up -d --build
```

4. Access the demo

```
http://localhost:5001
```

### Local Development

```bash
cd demo
dotnet run
```

## Environment Variables

Create a `.env` file with:

```env
MERCHANT_ID=your_merchant_id
ENCRYPTION_KEY=your_base64_32byte_key
ENVIRONMENT=test
BASE_URL=http://localhost:5001
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

- **Secure Encryption**: AES-256-CBC (PKCS#7) with base64 key and static IV
- **Hash Verification**: SHA-256 hashing with additional AES encryption layer
- **Type Safety**: Strongly typed C# models
- **Docker Support**: Production-ready Docker configuration
- **Modern Demo**: Ride booking service with beautiful UI/UX
- **Auto-submission**: Seamless payment flow with loading screens

## API Methods

- `CreatePayment(PaymentRequest request)` - Builds encrypted request and hash
- `CreatePaymentForm(PaymentRequest request, string formId = "paymentForm")` - Creates auto-submitting payment form
- `GenerateOrderNumber(string prefix = "ORDER")` - Helper for unique order IDs

## Demo Features

- Modern ride booking interface
- Interactive ride selection (Economy, Comfort, Premium)
- Dynamic pricing calculation
- Seamless payment integration
- Success/failure handling

## Usage Example

```csharp
using YagoutPay;

var client = new YagoutPayClient(
    merchantId: Environment.GetEnvironmentVariable("MERCHANT_ID"),
    encryptionKey: Environment.GetEnvironmentVariable("ENCRYPTION_KEY"),
    environment: Environment.GetEnvironmentVariable("ENVIRONMENT") ?? "test"
);

var orderNo = YagoutPayClient.GenerateOrderNumber("ORDER");

var request = new PaymentRequest
{
    Transaction = new TransactionDetails
    {
        OrderNo = orderNo,
        Amount = 1000.00m,
        Currency = "ETB",
        TxnType = "SALE",
        SuccessUrl = $"{Environment.GetEnvironmentVariable("BASE_URL")}/success",
        FailureUrl = $"{Environment.GetEnvironmentVariable("BASE_URL")}/failure"
    },
    Customer = new CustomerDetails
    {
        CustName = "",
        EmailId = "",
        MobileNo = ""
    }
};

var htmlForm = client.CreatePaymentForm(request);
```

## Support

For support and questions, check the demo application code or contact the YagoutPay team.
