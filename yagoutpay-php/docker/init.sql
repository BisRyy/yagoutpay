-- YagoutPay Demo Database Schema

CREATE DATABASE IF NOT EXISTS yagoutpay_demo;
USE yagoutpay_demo;

-- Transactions table to store payment records
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(70) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ETB',
    customer_name VARCHAR(100),
    email VARCHAR(100),
    mobile VARCHAR(15),
    status VARCHAR(20) DEFAULT 'PENDING',
    txn_id VARCHAR(100),
    bank_ref_no VARCHAR(100),
    payment_mode VARCHAR(50),
    response_code VARCHAR(10),
    response_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_order_no ON transactions(order_no);
CREATE INDEX idx_status ON transactions(status);
CREATE INDEX idx_created_at ON transactions(created_at);

-- Insert sample data for testing
INSERT INTO transactions (order_no, amount, customer_name, email, mobile, status) VALUES 
('ORDER_TEST_001', 100.00, 'John Doe', 'john@example.com', '1234567890', 'SUCCESS'),
('ORDER_TEST_002', 250.50, 'Jane Smith', 'jane@example.com', '0987654321', 'FAILED'),
('ORDER_TEST_003', 75.25, 'Bob Johnson', 'bob@example.com', '5555555555', 'PENDING');
