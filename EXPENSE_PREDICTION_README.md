 # Expense Prediction System with Gemini AI

This system provides intelligent expense predictions for the Cash Compass Budget Tracker application using both statistical analysis and Google's Gemini AI.

## Features

### 1. Statistical Predictions
- **Monthly Predictions**: Predicts next month's expenses based on historical data
- **Quarterly Predictions**: Predicts next 3 months with seasonal adjustments
- **Semi-Annual Predictions**: Predicts next 6 months with trend analysis
- **Yearly Predictions**: Predicts next year's total expenses

### 2. AI-Powered Predictions (Gemini Flash)
- Analyzes spending patterns, categories, and trends
- Considers seasonal factors and holidays
- Provides more sophisticated predictions based on user behavior
- Accounts for special events and economic factors

### 3. Chart Visualization
- Dual dataset display (Actual vs Predicted)
- Color-coded bars for easy distinction
- Interactive legend and tooltips
- AI predictions shown directly alongside actual data

## Setup Instructions

### 1. Database Requirements
The system uses the existing `transactions` and `categories` tables. Ensure you have:
- User transaction history (at least 3 months recommended)
- Proper category assignments
- Date fields in correct format

### 2. Gemini AI Integration

#### Option A: Using Mock Data (Current Setup)
The system currently uses mock data for demonstration purposes. No API key required.

#### Option B: Using Real Gemini AI
1. Get a Gemini API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Edit `FUNCTION/geminiExpensePrediction.php`
3. Replace `'YOUR_GEMINI_API_KEY'` with your actual API key
4. Uncomment the actual API call code and comment out the mock response

```php
// In geminiExpensePrediction.php, around line 25
$apiKey = 'your_actual_gemini_api_key_here';
```

### 3. File Structure
```
FUNCTION/
├── getExpenseData.php          # Regular statistical predictions
├── geminiExpensePrediction.php # AI-powered predictions
└── expensePrediction.php       # Legacy prediction file

FRONTEND/
└── main.php                    # Updated with prediction charts

CSS/
└── main.css                    # Updated with toggle styles
```

## How It Works

### 1. AI Predictions (`geminiExpensePrediction.php`)
- Calculates historical spending patterns from the last 12 months.
- Generates a prompt for Gemini AI, providing transaction analysis and requesting a prediction for the total sum of expenses for the selected future period (1, 3, 6, or 12 months).
- Fetches the prediction from Gemini AI.
- Combines historical data with the single predicted value for the chart.

### 2. Chart Display
- **Historical Data**: The chart displays historical expenses as blue bars for the selected period.
- **Predicted Data**: A single red bar is added to the chart representing the total predicted expense for the next period.
- **Period buttons**: Switch between different historical and prediction periods.

## Usage

### 1. Accessing Predictions
1. Log into Cash Compass.
2. Navigate to the Dashboard.
3. Find the "Expense Timeline by Period" chart.
4. Use the period buttons to select a timeframe.
5. View historical expenses alongside a single bar representing the total predicted expense for the next period.

### 2. Understanding the Data
- **Monthly**: Shows historical data for the current year, plus a prediction for the next month.
- **Quarterly**: Shows historical quarterly data, plus a prediction for the next 3 months.
- **Semi-Annual**: Shows historical semi-annual data, plus a prediction for the next 6 months.
- **Yearly**: Shows historical yearly data, plus a prediction for the next year.

### 3. Prediction Factors

#### Statistical Predictions Consider:
- Historical monthly averages
- Seasonal adjustments (Christmas, Black Friday, etc.)
- Recent spending trends
- Basic inflation factors

#### AI Predictions Consider:
- Detailed spending patterns
- Category-specific trends
- Day-of-week behavior
- Seasonal and holiday impacts
- Economic indicators
- Special events and celebrations

## Customization

### 1. Adjusting Seasonal Factors
Edit the seasonal factors in `getExpenseData.php`:

```php
// Holiday seasons
if ($month == 12) $seasonalFactor = 1.3; // Christmas
elseif ($month == 11) $seasonalFactor = 1.2; // Black Friday
// Add more seasonal adjustments as needed
```

### 2. Modifying AI Prompts
Customize the AI analysis by editing the prompt in `geminiExpensePrediction.php`:

```php
$prompt = "Your custom prompt here...";
```

### 3. Chart Colors
Update chart colors in `main.php`:

```javascript
// Actual expenses
borderColor: 'rgba(21, 74, 120, 0.79)',
backgroundColor: 'rgba(126, 172, 220, 0.34)',

// Predicted expenses
borderColor: 'rgba(255, 99, 132, 0.79)',
backgroundColor: 'rgba(255, 99, 132, 0.34)',
```

## Troubleshooting

### 1. No Predictions Showing
- Check if user has sufficient transaction history
- Verify database connection
- Ensure proper session management

### 2. AI Predictions Not Working
- Verify Gemini API key is correct
- Check internet connection for API calls
- Review browser console for errors

### 3. Chart Not Updating
- Clear browser cache
- Check JavaScript console for errors
- Verify all required files are present

## Security Considerations

### 1. API Key Security
- Never commit API keys to version control
- Use environment variables in production
- Implement proper access controls

### 2. Data Privacy
- All predictions are user-specific
- No data is shared with external services
- Transaction data remains private

## Performance Optimization

### 1. Caching
- Consider implementing prediction caching
- Cache AI responses for similar queries
- Use database caching for statistical calculations

### 2. Database Optimization
- Index transaction dates for faster queries
- Optimize category lookups
- Consider materialized views for complex calculations

## Future Enhancements

### 1. Advanced AI Features
- Multi-variable regression analysis
- Machine learning model training
- Real-time prediction updates

### 2. Enhanced Visualization
- Interactive prediction scenarios
- Confidence intervals
- Trend analysis charts

### 3. Integration Features
- Export predictions to PDF/Excel
- Email alerts for budget overruns
- Mobile app integration

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review browser console for errors
3. Verify all setup steps are completed
4. Test with sample data first

## License

This expense prediction system is part of the Cash Compass Budget Tracker application. 
