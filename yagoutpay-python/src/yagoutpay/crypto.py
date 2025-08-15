"""
Cryptography utilities for YagoutPay SDK
"""

import base64
import hashlib
from typing import Dict, Any
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import padding


class YagoutPayCrypto:
    """Cryptography utilities for YagoutPay integration"""
    
    def __init__(self, encryption_key: str):
        """
        Initialize with encryption key
        
        Args:
            encryption_key: Base64-encoded 32-byte encryption key
        """
        try:
            decoded_key = base64.b64decode(encryption_key)
        except Exception as exc:
            raise ValueError("Encryption key must be base64-encoded") from exc

        if len(decoded_key) != 32:
            raise ValueError(
                f"Invalid key length: expected 32 bytes after base64 decode, got {len(decoded_key)}"
            )

        self.encryption_key = decoded_key
        self.iv = b"0123456789abcdef"  # Static IV as per YagoutPay spec
    
    def aes_encrypt_base64(self, data: str) -> str:
        """
        Encrypt data using AES-256-CBC and return base64 encoded
        
        Args:
            data: String data to encrypt
            
        Returns:
            Base64 encoded encrypted data
        """
        # Convert string to bytes
        data_bytes = data.encode('utf-8')
        
        # Create cipher
        cipher = Cipher(
            algorithms.AES(self.encryption_key),
            modes.CBC(self.iv),
            backend=default_backend()
        )
        
        # Create encryptor with PKCS7 padding
        encryptor = cipher.encryptor()
        padder = padding.PKCS7(128).padder()
        
        # Pad and encrypt
        padded_data = padder.update(data_bytes) + padder.finalize()
        encrypted_data = encryptor.update(padded_data) + encryptor.finalize()
        
        # Return base64 encoded
        return base64.b64encode(encrypted_data).decode('utf-8')
    
    def aes_decrypt_base64(self, encrypted_data: str) -> str:
        """
        Decrypt base64 encoded AES-256-CBC encrypted data
        
        Args:
            encrypted_data: Base64 encoded encrypted data
            
        Returns:
            Decrypted string data
        """
        # Decode base64
        encrypted_bytes = base64.b64decode(encrypted_data)
        
        # Create cipher
        cipher = Cipher(
            algorithms.AES(self.encryption_key),
            modes.CBC(self.iv),
            backend=default_backend()
        )
        
        # Create decryptor
        decryptor = cipher.decryptor()
        unpadder = padding.PKCS7(128).unpadder()
        
        # Decrypt and unpad
        decrypted_data = decryptor.update(encrypted_bytes) + decryptor.finalize()
        unpadded_data = unpadder.update(decrypted_data) + unpadder.finalize()
        
        # Return as string
        return unpadded_data.decode('utf-8')
    
    def sha256_hex(self, data: str) -> str:
        """
        Generate SHA-256 hash of data
        
        Args:
            data: String data to hash
            
        Returns:
            Hexadecimal hash string
        """
        return hashlib.sha256(data.encode('utf-8')).hexdigest()
    
    def stringify_section(self, obj: Dict[str, Any], ordered_keys: list) -> str:
        """
        Convert section object to pipe-delimited string
        
        Args:
            obj: Dictionary containing section data
            ordered_keys: Ordered list of keys to include
            
        Returns:
            Pipe-delimited string
        """
        if not ordered_keys:
            return ""
        return "|".join(str(obj.get(k, "")) for k in ordered_keys)
    
    def build_encrypted_request(self, request_data: Dict[str, Any]) -> Dict[str, str]:
        """
        Build encrypted request for YagoutPay
        
        Args:
            request_data: Dictionary containing request data
            
        Returns:
            Dictionary with me_id and merchant_request
        """
        # Extract sections from request data
        txn_details = request_data.get("txnDetails", {})
        pg_details = request_data.get("pgDetails", {})
        card_details = request_data.get("cardDetails", {})
        cust_details = request_data.get("custDetails", {})
        bill_details = request_data.get("billDetails", {})
        ship_details = request_data.get("shipDetails", {})
        item_details = request_data.get("itemDetails", {})
        upi_details = request_data.get("upiDetails", {})
        other_details = request_data.get("otherDetails", {})
        
        # Build section strings in documented order
        txn_str = self.stringify_section(txn_details, [
            'ag_id', 'me_id', 'order_no', 'amount', 'country', 'currency', 
            'txn_type', 'success_url', 'failure_url', 'channel'
        ])
        pg_str = self.stringify_section(pg_details, ['pg_id', 'paymode', 'scheme', 'wallet_type'])
        card_str = self.stringify_section(card_details, ['card_no', 'exp_month', 'exp_year', 'cvv', 'card_name'])
        cust_str = self.stringify_section(cust_details, ['cust_name', 'email_id', 'mobile_no', 'unique_id', 'is_logged_in'])
        bill_str = self.stringify_section(bill_details, ['bill_address', 'bill_city', 'bill_state', 'bill_country', 'bill_zip'])
        ship_str = self.stringify_section(ship_details, ['ship_address', 'ship_city', 'ship_state', 'ship_country', 'ship_zip', 'ship_days', 'address_count'])
        item_str = self.stringify_section(item_details, ['item_count', 'item_value', 'item_category'])
        upi_str = self.stringify_section(upi_details, [])  # Empty section placeholder
        other_str = self.stringify_section(other_details, ['udf_1', 'udf_2', 'udf_3', 'udf_4', 'udf_5'])
        
        # Join sections with ~
        full_message = "~".join([
            txn_str, pg_str, card_str, cust_str, bill_str, ship_str, item_str, upi_str, other_str
        ])
        
        # Encrypt the full message
        encrypted_data = self.aes_encrypt_base64(full_message)
        
        return {
            "me_id": request_data.get("merchantId", ""),
            "merchant_request": encrypted_data
        }
    
    def build_encrypted_hash(self, hash_data: Dict[str, Any]) -> Dict[str, str]:
        """
        Build encrypted hash for YagoutPay
        
        Args:
            hash_data: Dictionary containing hash data
            
        Returns:
            Dictionary with hash
        """
        # Create hash string with ~ separators (matching JavaScript SDK)
        hash_string = (
            f"{hash_data.get('merchantId', '')}~"
            f"{hash_data.get('order_no', '')}~"
            f"{hash_data.get('amount', '')}~"
            f"{hash_data.get('currencyFrom', '')}~"
            f"{hash_data.get('currencyTo', '')}"
        )
        
        # Generate SHA-256 hash
        hash_value = self.sha256_hex(hash_string)
        
        # Encrypt the hash
        encrypted_hash = self.aes_encrypt_base64(hash_value)
        
        return {
            "hash": encrypted_hash
        }
    
    def generate_response_hash(self, response_data: Dict[str, Any]) -> str:
        """
        Generate hash for response verification
        
        Args:
            response_data: Dictionary containing response data
            
        Returns:
            Generated hash string
        """
        # Create hash string from response data
        hash_string = (
            f"{response_data.get('order_no', '')}"
            f"{response_data.get('amount', '')}"
            f"{response_data.get('status', '')}"
        )
        
        # Generate SHA-256 hash
        return self.sha256_hex(hash_string)
    
    def verify_response_hash(self, response_data: Dict[str, Any], received_hash: str) -> bool:
        """
        Verify response hash
        
        Args:
            response_data: Dictionary containing response data
            received_hash: Hash received from gateway
            
        Returns:
            True if hash is valid, False otherwise
        """
        try:
            # Decrypt the received hash
            decrypted_hash = self.aes_decrypt_base64(received_hash)
            
            # Generate expected hash
            expected_hash = self.generate_response_hash(response_data)
            
            # Compare hashes
            return decrypted_hash == expected_hash
        except Exception:
            return False
