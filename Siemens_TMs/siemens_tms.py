import os
import mysql.connector
import re

# MySQL connection details
db = mysql.connector.connect(
    host="10.0.0.223",
    user="pbento",
    passwd="bento2024$%",
    db="ava",
    charset='utf8mb4',
    allow_local_infile=True
)

cursor = db.cursor()

# Directory containing CSV files
directory = r'C:\Siemens_TMs'  # Use a raw string for the path

# Recursively walk through the directory and subdirectories
for root, dirs, files in os.walk(directory):
    for filename in files:
        if filename.endswith(".csv"):
            file_path = os.path.join(root, filename)
            print(f"Processing file: {file_path}")

            # Extract the language code from the filename
            match = re.search(r'_(\w{2}(_\w{2})?)\.csv$', filename)
            language_code = match.group(1) if match else None
            
            if language_code:
                print(f"Extracted language code: {language_code}")
            else:
                print(f"No language code found in filename: {filename}")
                continue  # Skip this file if no language code is found

            # Construct the LOAD DATA INFILE query
            query = f"""
            LOAD DATA LOCAL INFILE '{file_path.replace("\\", "/")}'
            INTO TABLE `siemens_tm`
            CHARACTER SET utf8mb4
            FIELDS TERMINATED BY ',' 
            ENCLOSED BY '"' 
            LINES TERMINATED BY '\n' 
            IGNORE 1 ROWS
            (`DATE`, `SOURCE`, `TRANSLATION`, `FILENAME`, `TARGET`)
            SET `TARGET` = '{language_code}'; 
            """
            print(f"Executing query: {query}")

            try:
                # Execute the LOAD DATA INFILE query
                cursor.execute(query)
                db.commit()  # Commit the transaction

                # Check the affected rows after loading
                affected_rows = cursor.rowcount
                print(f"Successfully loaded {filename}. Affected rows: {affected_rows}")

            except mysql.connector.Error as e:
                # Rollback in case of error
                db.rollback()
                print(f"Error loading {filename}: {e}")

# Check the total records in siemens_tm after loading
try:
    cursor.execute("SELECT COUNT(*) FROM `siemens_tm`;")
    count = cursor.fetchone()[0]
    print(f"Total records in siemens_tm: {count}")
except mysql.connector.Error as e:
    print(f"Error retrieving record count: {e}")

cursor.close()
db.close()
