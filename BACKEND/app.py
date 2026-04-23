from flask import Flask, request, jsonify
from PIL import Image
import pytesseract
import os
import requests
import traceback
import logging
import base64

# Optional: Set this if Tesseract is not in system PATH
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

app = Flask(__name__)

# Set up logging to file for debugging
logging.basicConfig(filename='error.log', level=logging.ERROR)

# Gemini API Key
gemini_key = "AIzaSyDY4BAnM3HxhOC5E0nNX1QRVp2GVW0y7_A"

@app.route('/upload-receipt-gemini', methods=['POST'])
def upload_receipt_gemini():
    try:
        if 'receipt' not in request.files:
            return jsonify({"error": "No file uploaded"}), 400

        file = request.files['receipt']

        # Read the image file and encode it as base64
        image_bytes = file.read()
        
        # Convert image to JPEG format if it isn't already
        try:
            image = Image.open(file)
            if image.format != 'JPEG':
                # Convert to RGB if necessary (in case of RGBA)
                if image.mode in ('RGBA', 'LA'):
                    background = Image.new('RGB', image.size, (255, 255, 255))
                    background.paste(image, mask=image.split()[-1])
                    image = background
                elif image.mode != 'RGB':
                    image = image.convert('RGB')
                
                # Save as JPEG to a bytes buffer
                from io import BytesIO
                buffer = BytesIO()
                image.save(buffer, format='JPEG')
                image_bytes = buffer.getvalue()
        except Exception as e:
            logging.error(f"Error processing image: {str(e)}")
            return jsonify({"error": "Error processing image", "details": str(e)}), 400

        image_b64 = base64.b64encode(image_bytes).decode('utf-8')

        # Prepare prompt for Gemini
        prompt = """
You are an intelligent assistant that extracts transaction information from receipts. 
Given the following receipt image, extract and return the following fields (2 Array) in JSON format:

#Array 1
Transaction Type: Expense/Income
Transaction Amount: "0.00" (example)
Transaction Name: XXX
Transaction Date: dd/mm/yyyy
Transaction Description: 
Used OCR: "1" (if used ocr) or "0" (if not used ocr)

#Array 2 (All Item in the receipt)
Item Name: XXX
Item Price: 0.00

Conditions:
If the transaction have change due, just return (total amount - change due).
If the receipt already have net total, just return the net total.
If there is many item in the receipt, help me crate a suitable transaction name (without special character).
If the receipt is a bill, just return the bill amount and bill date.
If the receipt has no transaction type, just return "Expense".
If the receipt has no description, just return all the item have bought in the receipt.
If the receipt has invalid transaction name, dont extract the invalid string or word, keep the valid string or word.
Check the receipt have date or not, some receipt might have date on the top or bottom of the receipt (Some of the date might be in different format, so you need to check the date format and return the date in the correct format (dd/mm/yyyy)).
If the receipt has no date or date not clear, just return today's date.
If there is more than one item in the receipt, return an array of the item name and the item price. (Item Name, Item Price)
If the item has discount, return the item price after discount.
If the item has no price, just return the item name and the item price as "0.00".
If the receipt has no item, just return the transaction name and the transaction amount, no item name and item price.
Return only the JSON object.
"""

        # Gemini API endpoint
        gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent"

        headers = {
            "Content-Type": "application/json"
        }
        data = {
            "contents": [
                {
                    "parts": [
                        {
                            "text": prompt
                        },
                        {
                            "inline_data": {
                                "mime_type": "image/jpeg",
                                "data": image_b64
                            }
                        }
                    ]
                }
            ]
        }

        response = requests.post(
            f"{gemini_url}?key={gemini_key}",
            headers=headers,
            json=data
        )

        if response.status_code != 200:
            logging.error(f"Gemini API error: {response.text}")
            return jsonify({"error": "Gemini API error", "details": response.text}), 500

        gemini_response = response.json()
        # Extract the model's reply
        try:
            gemini_text = gemini_response['candidates'][0]['content']['parts'][0]['text']
        except Exception as e:
            logging.error(f"Failed to parse Gemini response: {str(e)}\nRaw: {gemini_response}\nStack Trace:\n{traceback.format_exc()}")
            return jsonify({"error": "Failed to parse Gemini response", "details": str(e), "raw": gemini_response, "stack_trace": traceback.format_exc()}), 500

        return jsonify({
            "gemini_extracted": gemini_text
        })
    except Exception as e:
        # Log any unexpected error with stack trace
        logging.error(f"Unexpected error: {str(e)}\nStack Trace:\n{traceback.format_exc()}")
        return jsonify({
            "error": "Internal Server Error",
            "details": str(e),
            "stack_trace": traceback.format_exc()
        }), 500

if __name__ == '__main__':
    app.run(debug=True)


