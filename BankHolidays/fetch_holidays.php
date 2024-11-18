import sys
import json
import holidays

# Get the country code from the argument
country_code = sys.argv[1]

# Fetch holidays for 2024 and 2025 (you can adjust years)
holiday_data = []
for year in range(2024, 2026):
    for date, name in getattr(holidays, country_code)(years=year).items():
        holiday_data.append({"date": str(date), "holiday": name, "country": country_code})

# Return the holiday data as JSON
print(json.dumps(holiday_data))
