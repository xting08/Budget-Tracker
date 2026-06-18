# Cash Compass – AI Budget Tracker

> A full-stack personal finance web application that helps users track expenses, set budgets, manage savings goals, and gain AI-powered financial insights.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql)
![Python](https://img.shields.io/badge/Python-3.9+-3776AB?style=flat&logo=python)
![Flask](https://img.shields.io/badge/Flask-2.0+-000000?style=flat&logo=flask)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=flat&logo=javascript)
![Chart.js](https://img.shields.io/badge/Chart.js-3.0-FF6384?style=flat&logo=chart.js)

---

## 📌 Overview

**Cash Compass** is a web-based budget tracker designed to empower users in managing their personal finances efficiently. It combines traditional expense tracking with AI-powered features like receipt OCR, expense prediction, and personalized spending suggestions.

### Key Features

| Feature | Description |
|---------|-------------|
| 💰 **Transaction Management** | Add, edit, delete income/expense records with manual entry or receipt OCR |
| 📊 **Budget & Savings Goals** | Set budgets and savings targets with real-time progress tracking |
| 👥 **Shared Expenses** | Split expenses with other registered users with recipient validation |
| 📈 **Interactive Dashboards** | Visualize spending with Chart.js (daily, category, and predictive charts) |
| 🤖 **AI-Powered OCR** | Auto-extract transaction data from receipt images using Gemini API |
| 🔮 **Expense Prediction** | Predict future spending trends (monthly, quarterly, semi-annual, yearly) |
| 💡 **Personalized Suggestions** | Receive AI-generated tips to improve spending habits |
| 🔐 **Secure Authentication** | Session-based login with password hashing |

---

## 🛠️ Tech Stack

### Backend
- **PHP** – Core business logic and API endpoints
- **Python (Flask)** – OCR and AI service for receipt processing
- **MySQL** – Relational database management

### Frontend
- **HTML5, CSS3, JavaScript** – Responsive user interface
- **Chart.js** – Interactive data visualizations
- **SweetAlert2** – User-friendly alerts and confirmations

### APIs & Libraries
- **Google Gemini API** – Receipt data extraction
- **PHPMailer** – Email notifications for shared expenses

---

## 📁 Project Structure

```
Budget-Tracker/
├── FRONTEND/
│   ├── main.php              # Dashboard
│   ├── transaction.php       # Transaction management
│   ├── budget.php            # Budget settings
│   ├── savinggoals.php       # Savings goals
│   ├── shareexpense.php      # Shared expenses
│   ├── profile.php           # User profile
│   └── assets/
│       ├── css/              # Stylesheets
│       ├── js/               # JavaScript files
│       └── img/              # Images
├── BACKEND/
│   ├── DB/
│   │   └── db_connect.php    # Database connection
│   ├── FUNCTION/
│   │   ├── addTransactionPost.php
│   │   ├── getCategoryData.php
│   │   ├── getDailyExpenseData.php
│   │   ├── geminiExpensePrediction.php
│   │   └── geminiSuggestion.php
│   └── app.py                # Flask OCR service
├── DATABASE/
│   └── cashcompass.sql       # Database schema
└── README.md
```

---

## 🗄️ Database Schema

**Main Tables:**
- `users` – User authentication and profile data
- `transactions` – Income/expense records
- `categories` – Customizable transaction categories
- `budgets` – Budget limits and tracking
- `savinggoals` – Savings targets and progress
- `shareexpense` – Shared expense management
- `transaction_receipt` – OCR receipt storage
- `item_list` – Extracted receipt items

---

## ⚙️ Installation & Setup

### Prerequisites
- XAMPP (Apache, PHP, MySQL)
- Python 3.9+
- Composer

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/xting08/Budget-Tracker.git
   cd Budget-Tracker
   ```

2. **Set up the database**
   - Start XAMPP (Apache & MySQL)
   - Open phpMyAdmin and import `DATABASE/cashcompass.sql`

3. **Configure database connection**
   - Update `BACKEND/DB/db_connect.php` with your database credentials

4. **Install PHP dependencies**
   ```bash
   composer install
   ```

5. **Install Python dependencies**
   ```bash
   pip install flask pillow requests
   ```

6. **Start the Flask OCR service**
   ```bash
   cd BACKEND
   python app.py
   ```

7. **Access the application**
   - Open browser and go to: `http://localhost/Budget-Tracker/FRONTEND/main.php`

---

## 🔧 Configuration

### Database Connection (`BACKEND/DB/db_connect.php`)
```php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'cashcompass';
```

### Flask OCR Service (`BACKEND/app.py`)
- Runs on port `5000` by default
- Endpoint: `POST /upload-receipt-gemini`

### Gemini API Key
- Add your Gemini API key in the Flask service configuration

---

## 📊 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/FUNCTION/getCategoryData.php` | GET | Category-wise income/expense data |
| `/FUNCTION/getDailyExpenseData.php` | GET | Daily expense data for charts |
| `/FUNCTION/geminiExpensePrediction.php` | GET | AI-powered expense predictions |
| `/FUNCTION/geminiSuggestion.php` | GET | Personalized spending suggestions |
| `/FUNCTION/addTransactionPost.php` | POST | Add new transaction |
| `/FUNCTION/addBudgetPost.php` | POST | Create new budget |
| `/FUNCTION/addSavingGoalsPost.php` | POST | Create new saving goal |
| `/upload-receipt-gemini` (Flask) | POST | OCR receipt processing |

---

## 🤖 AI Features

### 1. Receipt OCR
- Upload receipt images
- Gemini API extracts: item names, prices, total amount
- Auto-fills transaction form
- Displays itemized list

### 2. Expense Prediction
- Analyzes historical spending patterns
- Predicts future expenses for: 1 Month, 3 Months, 6 Months, 1 Year
- Visualized as line charts

### 3. Personalized Suggestions
- Identifies budget overruns
- Recommends spending reductions
- Suggests savings strategies
- Alerts for pending shared expenses

---

## 🧪 Testing

### Test Coverage
- ✅ Unit Testing – All core modules tested
- ✅ Integration Testing – Module interactions validated
- ✅ Usability Testing – Real user feedback collected
- ✅ Acceptance Testing – End-to-end workflow validation

### Test Results
- **Registration/Login:** ✅ Passed
- **Transaction CRUD:** ✅ Passed
- **Budget & Savings:** ✅ Passed
- **Shared Expenses:** ✅ Passed
- **OCR & AI Features:** ✅ Passed
- **Dashboard Analytics:** ✅ Passed

---

## 📹 Video Demo

📺 **Watch the full demo on Google Drive:** [Click here to view](https://drive.google.com/file/d/1iLVqlqnUwTk8Xq_WAg3oVbQTs8qpmudk/view?usp=sharing)

---

## 👤 Author

**Yoon Xiao Ting**  
- Student ID: 1211103617  
- Computer Science – Software Engineering  
- Multimedia University, Cyberjaya  
- GitHub: [@xting08](https://github.com/xting08)

---

## 🙏 Acknowledgements

- **Supervisor:** Dr. Nur Erlida Binti Ruslan
- **Multimedia University** – Faculty of Computing and Informatics
- **Google Gemini API** – AI-powered receipt processing

---

## 🚀 Future Enhancements

- [ ] Mobile-responsive optimization
- [ ] Cloud deployment with HTTPS
- [ ] Enhanced AI prediction models
- [ ] Multi-language support
- [ ] Third-party financial integration
- [ ] Offline functionality

---

**Made with ❤️ for better financial management**
