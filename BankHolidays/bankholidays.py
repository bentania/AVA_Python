import pandas as pd
from datetime import date
import holidays

# List of countries you want to include
countries = {
    "United Kingdom": holidays.UnitedKingdom,
    "United States": holidays.UnitedStates,
    "Spain": holidays.Spain,
    "China": holidays.China,
    "Denmark": holidays.Denmark,
    "Czechia": holidays.Czechia,
    "Estonia": holidays.Estonia,
    "Germany": holidays.Germany,
    "Italy": holidays.Italy,
    "Korea": holidays.Korea,
    "France": holidays.France,
    "Philippines": holidays.Philippines,
    "Japan": holidays.Japan,
    
    "Brazil": holidays.Brazil,
    "Netherlands": holidays.Netherlands,
    "Taiwan": holidays.Taiwan,
    "Finland": holidays.Finland,
    "Norway": holidays.Norway,
    "Portugal": holidays.Portugal,
    "Sweden": holidays.Sweden,
    "Greece": holidays.Greece,
    "India": holidays.India,
    "Israel": holidays.Israel,
    "Canada": holidays.Canada,
    "Mexico": holidays.Mexico,
    # Add other countries as needed
}

# List to hold all holidays data
holiday_data = []

# Loop through each country
for country_name, holiday_class in countries.items():
    for year in range(2024, 2026):  # Define the range of years
        for holiday_date, name in holiday_class(years=year).items():
            holiday_data.append([country_name, year, holiday_date, name])

# Create a DataFrame
df = pd.DataFrame(holiday_data, columns=["Country", "Year", "Date", "Holiday"])

# Convert the 'Date' column to datetime format
df['Date'] = pd.to_datetime(df['Date'])

# Sort by 'Date' column only
df = df.sort_values(by="Date")

# Format the 'Date' column to DD/MM/YYYY
df['Date'] = df['Date'].dt.strftime('%d/%m/%Y')

# Export the DataFrame to Excel for pivot table usage
df.to_excel("BankHolidays\\bank_holidays_sorted_by_date.xlsx", index=False)

# Display the sorted DataFrame
df

