# Report Generation Feature Guide

## Overview
The Generate Reports feature provides comprehensive reporting capabilities for your restaurant ordering system. It allows you to create custom reports based on various criteria including date ranges, years, and specific analysis types.

## Accessing the Feature
1. **From Admin Dashboard**: Click the "Generate Reports" button in the top toolbar
2. **From Navigation Menu**: Click "Generate Reports" in the main navigation menu

## Available Report Types

### 1. Daily Sales Report
- **Purpose**: Analyze daily sales performance over a specific date range
- **Parameters**: Start Date, End Date
- **Data Includes**:
  - Date
  - Number of orders
  - Total sales amount
  - Average order value
- **Use Cases**:
  - Identify peak sales days
  - Track daily performance trends
  - Compare sales across different periods

### 2. Monthly Sales Report
- **Purpose**: Review monthly sales performance for a specific year
- **Parameters**: Year selection
- **Data Includes**:
  - Month name
  - Total orders
  - Total sales
  - Average daily sales
- **Use Cases**:
  - Seasonal trend analysis
  - Monthly performance comparison
  - Year-over-year growth tracking

### 3. Yearly Sales Report
- **Purpose**: Compare annual performance across multiple years
- **Parameters**: None (shows all available years)
- **Data Includes**:
  - Year
  - Total orders
  - Total sales
  - Average monthly sales
- **Use Cases**:
  - Long-term business growth analysis
  - Year-over-year performance comparison
  - Strategic planning insights

### 4. Order Analysis Report
- **Purpose**: Analyze order status distribution and performance
- **Parameters**: Start Date, End Date
- **Data Includes**:
  - Order status (pending, paid, preparing, ready, completed, cancelled)
  - Number of orders per status
  - Total amount per status
  - Percentage distribution
- **Use Cases**:
  - Operational efficiency analysis
  - Identify bottlenecks in order processing
  - Track order completion rates

### 5. Popular Items Report
- **Purpose**: Identify best-selling menu items
- **Parameters**: Start Date, End Date
- **Data Includes**:
  - Item name
  - Item price
  - Number of orders
  - Total quantity sold
  - Total revenue generated
- **Use Cases**:
  - Menu optimization
  - Inventory planning
  - Marketing focus areas

## How to Generate Reports

### Step 1: Select Report Type
Choose the type of report you want to generate from the dropdown menu.

### Step 2: Set Parameters
- **For Date Range Reports** (Daily Sales, Order Analysis, Popular Items):
  - Select start date
  - Select end date
- **For Yearly Reports** (Monthly Sales):
  - Select the year from the dropdown

### Step 3: Generate Report
Click the "Generate Report" button to process your request.

### Step 4: Review Results
The system will display:
- Summary cards with key metrics
- Detailed data table
- Total calculations where applicable

## Export and Print Options

### Export to CSV
1. Click the "Export to CSV" button
2. The file will be automatically downloaded with a descriptive filename
3. Filename format: `[report_type]_report_[date_range]_[generation_date].csv`

### Print Report
1. Click the "Print Report" button
2. A print-friendly version will open in a new window
3. Use your browser's print function to print or save as PDF

## Key Features

### Dynamic Form Fields
- Form fields automatically show/hide based on selected report type
- Intuitive user interface that adapts to your selection

### Summary Statistics
Each report includes relevant summary cards showing:
- Total sales
- Total orders
- Average values
- Peak performance metrics

### Responsive Design
- Works on desktop, tablet, and mobile devices
- Bootstrap-based responsive layout

### Data Validation
- Date range validation
- Required field checking
- Error handling for invalid parameters

## Best Practices

### For Daily Reports
- Use shorter date ranges (1-30 days) for detailed analysis
- Compare similar periods (e.g., same weekdays across different weeks)

### For Monthly Reports
- Generate reports for complete months for accurate comparisons
- Consider seasonal factors when analyzing results

### For Yearly Reports
- Use for long-term strategic planning
- Compare with industry benchmarks if available

### For Order Analysis
- Monitor regularly to identify operational issues
- Focus on status transitions and completion rates

### For Popular Items
- Generate monthly reports for menu planning
- Use data to optimize inventory and pricing

## Troubleshooting

### No Data Found
- Check if the selected date range contains any orders
- Verify that the daily_sales, monthly_sales, or yearly_sales tables have data
- Ensure the date format is correct

### Export Issues
- Ensure your browser allows downloads
- Check if pop-up blockers are interfering with the export function

### Performance
- For large date ranges, reports may take longer to generate
- Consider breaking very large date ranges into smaller chunks

## Technical Notes

### Database Tables Used
- `daily_sales` - For daily and date range reports
- `monthly_sales` - For monthly reports
- `yearly_sales` - For yearly reports
- `orders` - For order analysis
- `order_items` - For popular items analysis
- `menu_items` - For item details
- `order_statuses` - For status information

### Security
- All user inputs are sanitized
- SQL injection protection implemented
- Admin authentication required

### Performance Optimization
- Indexed database queries
- Efficient data aggregation
- Minimal data transfer

## Support
For technical support or feature requests, contact your system administrator.

