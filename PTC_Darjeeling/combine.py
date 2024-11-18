import csv
import os
import glob

# Mapping of language codes to their corresponding header names
language_files = {
    "CN.txt": "Chinese (PRC)",
    "TW.txt": "Chinese (Taiwan)",
    "FR.txt": "French (France)",
    "DE.txt": "German (Germany)",
    #"IT.txt": "Italian",
    "JA.txt": "Japanese",
    "ES.txt": "Spanish (Spain)"
}

# Directory containing the language files
input_directory = r"C:\MAMP\htdocs\PTC_Darjeeling\Uploads"
# Output combined file
output_file = r"C:\MAMP\htdocs\PTC_Darjeeling\Uploads\combined_languages.txt"

# Prepare header row
headers = ["English (United States)"] + list(language_files.values())

# Initialize translations dictionary
translations = {}

# Read all the language files and combine them
for file_name, header in language_files.items():
    # Construct full file path based on the language file naming convention
    file_pattern = f"*_{file_name}"
    file_paths = glob.glob(os.path.join(input_directory, file_pattern))
    
    for file_path in file_paths:
        with open(file_path, mode='r', encoding='utf-8') as file:
            csv_reader = csv.reader(file, delimiter='\t')  # assuming tab-delimited
            for row in csv_reader:
                if row:  # Ensure the row is not empty
                    key = row[0]
                    value = row[1] if len(row) > 1 else ''
                    if key not in translations:
                        translations[key] = { "English (United States)": key }
                    translations[key][header] = value

# Write the combined translations to the output file
with open(output_file, mode='w', encoding='utf-8', newline='') as file:
    csv_writer = csv.writer(file)
    # Write header row
    csv_writer.writerow(headers)
    # Write translations
    for key, values in translations.items():
        row = [values.get(header, '') for header in headers]
        csv_writer.writerow(row)

print(f"Combined file created at {output_file}")
