import sys
import json
import holidays
import mysql.connector
import logging
import pandas as pd

# Set up logging to a file
logging.basicConfig(filename='fetch_holidays.log', level=logging.DEBUG)

# Country code to country name mapping
country_code_to_name = {
    "AL": "Albania",
    "DZ": "Algeria",
    "AS": "American Samoa",
    "AD": "Andorra",
    "AO": "Angola",
    "AR": "Argentina",
    "AM": "Armenia",
    "AW": "Aruba",
    "AU": "Australia",
    "AT": "Austria",
    "AZ": "Azerbaijan",
    "BS": "Bahamas",
    "BH": "Bahrain",
    "BD": "Bangladesh",
    "BB": "Barbados",
    "BY": "Belarus",
    "BE": "Belgium",
    "BZ": "Belize",
    "BO": "Bolivia",
    "BA": "Bosnia and Herzegovina",
    "BW": "Botswana",
    "BR": "Brazil",
    "BN": "Brunei",
    "BG": "Bulgaria",
    "BF": "Burkina Faso",
    "BI": "Burundi",
    "KH": "Cambodia",
    "CM": "Cameroon",
    "CA": "Canada",
    "TD": "Chad",
    "CL": "Chile",
    "CN": "China",
    "CO": "Colombia",
    "CG": "Congo",
    "CR": "Costa Rica",
    "HR": "Croatia",
    "CU": "Cuba",
    "CW": "Curacao",
    "CY": "Cyprus",
    "CZ": "Czechia",
    "DK": "Denmark",
    "DJ": "Djibouti",
    "DM": "Dominica",
    "DO": "Dominican Republic",
    "EC": "Ecuador",
    "EG": "Egypt",
    "SV": "El Salvador",
    "EE": "Estonia",
    "SZ": "Eswatini",
    "ET": "Ethiopia",
    "FI": "Finland",
    "FR": "France",
    "GA": "Gabon",
    "GE": "Georgia",
    "DE": "Germany",
    "GH": "Ghana",
    "GR": "Greece",
    "GL": "Greenland",
    "GU": "Guam",
    "GT": "Guatemala",
    "HT": "Haiti",
    "HN": "Honduras",
    "HK": "Hong Kong",
    "HU": "Hungary",
    "IS": "Iceland",
    "IN": "India",
    "ID": "Indonesia",
    "IR": "Iran",
    "IE": "Ireland",
    "IM": "Isle of Man",
    "IL": "Israel",
    "IT": "Italy",
    "JM": "Jamaica",
    "JP": "Japan",
    "JE": "Jersey",
    "JO": "Jordan",
    "KZ": "Kazakhstan",
    "KE": "Kenya",
    "KW": "Kuwait",
    "KG": "Kyrgyzstan",
    "LA": "Laos",
    "LV": "Latvia",
    "LS": "Lesotho",
    "LI": "Liechtenstein",
    "LT": "Lithuania",
    "LU": "Luxembourg",
    "MG": "Madagascar",
    "MW": "Malawi",
    "MY": "Malaysia",
    "MV": "Maldives",
    "MT": "Malta",
    "MH": "Marshall Islands",
    "MR": "Mauritania",
    "MX": "Mexico",
    "MD": "Moldova",
    "MC": "Monaco",
    "ME": "Montenegro",
    "MA": "Morocco",
    "MZ": "Mozambique",
    "NA": "Namibia",
    "NL": "Netherlands",
    "NZ": "New Zealand",
    "NI": "Nicaragua",
    "NG": "Nigeria",
    "MP": "Northern Mariana Islands",
    "MK": "North Macedonia",
    "NO": "Norway",
    "PK": "Pakistan",
    "PW": "Palau",
    "PA": "Panama",
    "PG": "Papua New Guinea",
    "PY": "Paraguay",
    "PE": "Peru",
    "PH": "Philippines",
    "PL": "Poland",
    "PT": "Portugal",
    "PR": "Puerto Rico",
    "RO": "Romania",
    "RU": "Russia",
    "KN": "Saint Kitts and Nevis",
    "WS": "Samoa",
    "SM": "San Marino",
    "SA": "Saudi Arabia",
    "RS": "Serbia",
    "SC": "Seychelles",
    "SG": "Singapore",
    "SK": "Slovakia",
    "SI": "Slovenia",
    "ZA": "South Africa",
    "KR": "South Korea",
    "ES": "Spain",
    "SE": "Sweden",
    "CH": "Switzerland",
    "TW": "Taiwan",
    "TZ": "Tanzania",
    "TH": "Thailand",
    "TL": "Timor Leste",
    "TO": "Tonga",
    "TN": "Tunisia",
    "TR": "Turkey",
    "UA": "Ukraine",
    "AE": "United Arab Emirates",
    "GB": "United Kingdom",
    "UM": "United States Minor Outlying Islands",
    "US": "United States of America",
    "VI": "Virgin Islands (U.S.)",
    "UY": "Uruguay",
    "UZ": "Uzbekistan",
    "VU": "Vanuatu",
    "VA": "Vatican City",
    "VE": "Venezuela",
    "VN": "Vietnam",
    "ZM": "Zambia",
    "ZW": "Zimbabwe"
}

try:
    # Get the language IDs and year from the arguments
    language_ids = sys.argv[1].split(',')  # Split the comma-separated language IDs
    year = int(sys.argv[2])

    # Log the received language IDs and year
    logging.debug(f"Received Language IDs: {language_ids}")
    logging.debug(f"Received Year: {year}")

    # Connect to the MySQL database
    db = mysql.connector.connect(
        host="10.0.0.223",
        user="pbento",
        password="bento2024$%",
        database="ava"
    )

    cursor = db.cursor()

    # Get the country codes and subdivisions corresponding to the language IDs
    if language_ids:
        query = """
        SELECT l.country_code, l.subdivision 
        FROM languages l 
        WHERE l.id IN (%s)
        """ % ','.join(['%s'] * len(language_ids))
        cursor.execute(query, tuple(language_ids))
        results = cursor.fetchall()

        # Log the fetched country codes and subdivisions
        logging.debug(f"Fetched Country Codes and Subdivisions: {results}")

        country_codes = [row[0] for row in results]
        subdivisions = [row[1] for row in results]  # Fetch subdivision if available

        supported_countries = holidays.list_supported_countries()

        # Fetch holidays for each country and subdivision (if available)
        holiday_data = []
        for country_code, subdivision in zip(country_codes, subdivisions):
            if country_code not in supported_countries:
                logging.error(f"Country code {country_code} is not supported by the holidays library.")
                continue  # Skip unsupported countries

            try:
                # Fetch national holidays first
                logging.debug(f"Fetching national holidays for country: {country_code}")
                holidays_in_country = holidays.country_holidays(country_code, years=year)

                # Get country name from the mapping, fallback to country code if not found
                country_name = country_code_to_name.get(country_code, country_code)

                # Append national holiday data
                for date, name in holidays_in_country.items():
                    holiday_data.append({
                        "date": str(date),
                        "holiday": name,
                        "country": country_name,  # Use country name here
                        "subdivision": "National"
                    })

                # If subdivisions are provided, fetch their holidays as well
                if subdivision:
                    subdiv_list = subdivision.split(',')  # Split multiple subdivisions by comma
                    for subdiv in subdiv_list:
                        logging.debug(f"Fetching holidays for country: {country_code}, subdivision: {subdiv.strip()}")
                        holidays_in_region = holidays.country_holidays(country_code, subdiv=subdiv.strip(), years=year)

                        # Append subdivision holiday data
                        for date, name in holidays_in_region.items():
                            holiday_data.append({
                                "date": str(date),
                                "holiday": name,
                                "country": country_name,  # Use country name here
                                "subdivision": subdiv.strip()
                            })

            except AttributeError as e:
                logging.error(f"Error fetching holidays for country code {country_code}: {e}")

        # Output only the JSON data
        sys.stdout.write(json.dumps(holiday_data))

    else:
        sys.stdout.write(json.dumps([]))  # Output an empty JSON array

except Exception as e:
    # Log any unexpected errors and return a valid JSON error message
    logging.error(f"Unexpected error: {e}")
    sys.stdout.write(json.dumps({"error": str(e)}))
