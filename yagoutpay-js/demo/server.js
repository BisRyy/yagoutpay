// demo/server.js
import express from "express";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";
import PDFDocument from "pdfkit";
import { buildEncryptedRequest, buildEncryptedHash } from "../src/yagoutpay.js";

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

// Health check endpoint
app.get("/health", (req, res) => {
  res.status(200).json({
    status: "healthy",
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV || "development",
  });
});

const MERCHANT_ID = process.env.MERCHANT_ID;
const MERCHANT_KEY = process.env.MERCHANT_KEY;
const YAGOUT_UAT_URL =
  process.env.YAGOUT_UAT_URL ||
  "https://uatcheckout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage";
const BASE_URL = process.env.BASE_URL || "http://localhost:3000";

// Home (Ride Booking)
app.get("/", (_req, res) => {
  res.sendFile(path.join(__dirname, "public", "views", "index.html"));
});

// Products (redirect to home for backward compatibility)
app.get("/products", (_req, res) => {
  res.redirect("/");
});

// Pay
app.post("/pay", (req, res) => {
  const {
    product_id = "ride_comfort",
    product_name = "RideYagout - Comfort",
    amount = "670",
    email_id,
    mobile_no,
    cust_name,
    pickup_address,
    dropoff_address,
    distance,
  } = req.body || {};

  const order_no = `RIDE_${Date.now()}_${
    Math.floor(Math.random() * 9000) + 1000
  }`;
  const success_url = `${BASE_URL}/success?order_no=${order_no}&product_name=${encodeURIComponent(
    product_name
  )}&amount=${amount}`;
  const failure_url = `${BASE_URL}/failure?order_no=${order_no}&reason=payment_failed`;

  const { me_id, merchant_request } = buildEncryptedRequest({
    merchantId: MERCHANT_ID,
    merchantKey: MERCHANT_KEY,
    txnDetails: {
      ag_id: "yagout",
      me_id: MERCHANT_ID,
      order_no,
      amount: String(amount),
      country: "ETH",
      currency: "ETB",
      txn_type: "SALE",
      success_url,
      failure_url,
      channel: "WEB",
    },
    custDetails: {
      cust_name: cust_name || "",
      email_id: email_id || "",
      mobile_no: mobile_no || "",
      unique_id: "",
      is_logged_in: "Y",
    },
    billDetails: {
      bill_address: pickup_address || "",
      bill_city: "Addis Ababa",
      bill_state: "Addis Ababa",
      bill_country: "Ethiopia",
      bill_zip: "",
    },
  });

  const { hash } = buildEncryptedHash({
    merchantId: MERCHANT_ID,
    merchantKey: MERCHANT_KEY,
    order_no,
    amount: String(amount),
    currencyFrom: "ETH",
    currencyTo: "ETB",
  });

  res.set("Content-Type", "text/html");
  res.send(`
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment Gateway</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .loading-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .loading-subtext {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">Redirecting to Payment Gateway</div>
        <div class="loading-subtext">Please wait while we process your request...</div>
    </div>
    
    <form id="gatewayForm" method="POST" action="${YAGOUT_UAT_URL}" style="display: none;">
        <input type="hidden" name="me_id" value="${me_id}" />
        <input type="hidden" name="merchant_request" value="${merchant_request}" />
        <input type="hidden" name="hash" value="${hash}" />
    </form>
    <script>
        // Auto-submit form after a brief delay to show loading
        setTimeout(function() {
            document.getElementById('gatewayForm').submit();
        }, 1500);
    </script>
</body>
</html>
  `);
});

// Success page (works for GET & POST)
app.all("/success", (req, res) => {
  const order_no = req.query.order_no || req.body.order_no;
  const product_name = req.query.product_name || req.body.product_name;
  const amount = req.query.amount || req.body.amount;

  // Redirect to success page with query params if they came in POST
  if (req.method === "POST" && !req.query.order_no) {
    const params = new URLSearchParams({
      order_no: order_no || "",
      product_name: product_name || "",
      amount: amount || "",
    });
    return res.redirect(`/success?${params.toString()}`);
  }

  res.sendFile(path.join(__dirname, "public", "views", "success.html"));
});

// Failure page (works for GET & POST)
app.all("/failure", (req, res) => {
  const order_no = req.query.order_no || req.body.order_no;
  const reason = req.query.reason || req.body.reason || "Payment failed";

  if (req.method === "POST" && !req.query.reason) {
    const params = new URLSearchParams({
      order_no: order_no || "",
      reason: reason,
    });
    return res.redirect(`/failure?${params.toString()}`);
  }

  res.sendFile(path.join(__dirname, "public", "views", "failure.html"));
});

// PDF download
app.get("/receipt.pdf", (req, res) => {
  const { order_no, product_name, amount } = req.query;
  if (!order_no || !product_name || !amount) return res.redirect("/products");

  const doc = new PDFDocument({ size: "A4", margin: 50 });
  res.setHeader("Content-Type", "application/pdf");
  res.setHeader(
    "Content-Disposition",
    `attachment; filename="receipt_${order_no}.pdf"`
  );
  doc.pipe(res);

  // Header
  doc.rect(0, 0, 612, 80).fill("#0b1220");
  doc.fillColor("white").fontSize(20).text("YagoutPay Demo Shop", 50, 30);

  // Body
  doc.fillColor("#111").fontSize(24).text("Payment Receipt", 50, 120);
  doc.moveDown();
  doc
    .fontSize(12)
    .fillColor("#333")
    .text(`Order No: ${order_no}`)
    .text(`Product: ${product_name}`)
    .text(`Amount: ${amount} ETB`)
    .text(`Currency: ETB`)
    .text(`Status: SUCCESS`);

  doc.end();
});

const port = 3000;
app.listen(port, () => console.log(`Demo running on http://localhost:${port}`));
