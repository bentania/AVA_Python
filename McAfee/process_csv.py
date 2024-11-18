import pandas as pd
import chardet
import sys
from tabulate import tabulate  # Import tabulate for better formatting

def detect_encoding(file_path):
    """Detect the encoding of the file."""
    with open(file_path, 'rb') as file:
        raw_data = file.read(1000)  # Read the first 1000 bytes for detection
        result = chardet.detect(raw_data)
    return result['encoding']

def detect_delimiter(file_path, encoding):
    """Detect the delimiter used in the CSV file."""
    with open(file_path, 'r', encoding=encoding) as file:
        first_line = file.readline()
        delimiters = [',', ';', '\t']
        delimiter_counts = {d: first_line.count(d) for d in delimiters}
        return max(delimiter_counts, key=delimiter_counts.get)

def load_cost_matrix():
    """Load the cost matrix from a predefined source."""
    return {
        'alb': {'Software': 0.149, 'Marketing': 0.157},
        'ara': {'Software': 0.191, 'Marketing': 0.2106},
        'bul': {'Software': 0.149, 'Marketing': 0.157},
        'cze': {'Software': 0.172, 'Marketing': 0.1826},
        'dan': {'Software': 0.206, 'Marketing': 0.2274},
        'dut': {'Software': 0.182, 'Marketing': 0.2005},
        'fin': {'Software': 0.206, 'Marketing': 0.2274},
        'fre': {'Software': 0.168, 'Marketing': 0.1837},
        'fre-CA': {'Software': 0.206, 'Marketing': 0.2274},
        'ger': {'Software': 0.168, 'Marketing': 0.1837},
        'gre': {'Software': 0.174, 'Marketing': 0.1926},
        'heb': {'Software': 0.182, 'Marketing': 0.2005},
        'hrv': {'Software': 0.191, 'Marketing': 0.2106},
        'hun': {'Software': 0.182, 'Marketing': 0.2005},
        'ita': {'Software': 0.168, 'Marketing': 0.1837},
        'jpn': {'Software': 0.231, 'Marketing': 0.227},
        'kor': {'Software': 0.168, 'Marketing': 0.1837},
        'nnb': {'Software': 0.206, 'Marketing': 0.2274},
        'pol': {'Software': 0.174, 'Marketing': 0.1826},
        'por-BR': {'Software': 0.124, 'Marketing': 0.1568},
        'por-PT': {'Software': 0.161, 'Marketing': 0.177},
        'rus': {'Software': 0.146, 'Marketing': 0.1613},
        'scr': {'Software': 0.191, 'Marketing': 0.214},
        'slo': {'Software': 0.178, 'Marketing': 0.196},
        'spa-ES': {'Software': 0.146, 'Marketing': 0.1613},
        'spa-MX': {'Software': 0.13, 'Marketing': 0.1613},
        'swe': {'Software': 0.206, 'Marketing': 0.2274},
        'tur': {'Software': 0.174, 'Marketing': 0.1826},
        'zho-CN': {'Software': 0.107, 'Marketing': 0.1232},
        'zho-TW': {'Software': 0.141, 'Marketing': 0.1546}
    }

def process_csv(input_file, output_file, content_type):
    try:
        print(f"Content type selected: {content_type}")

        # Detect file encoding
        encoding = detect_encoding(input_file)
        print(f"Detected encoding: {encoding}")

        # Detect delimiter
        delimiter = detect_delimiter(input_file, encoding)
        print(f"Detected delimiter: {delimiter}")

        if not delimiter:
            raise ValueError("Could not detect delimiter.")

        # Read the first two lines to determine file type
        with open(input_file, 'r', encoding=encoding) as file:
            first_line = file.readline().strip()
            second_line = file.readline().strip()

        # Adjust file type detection logic
        if first_line.startswith(';;X-translated') and second_line.startswith('File;Char/Word'):
            file_type = 'memoQ'
            first_line = ";" + first_line  # Add extra ';' to shift columns
        elif 'Target language' in first_line:
            file_type = 'XTM'
        else:
            raise ValueError("Unknown file type")

        print(f"Detected file type: {file_type}")

        # Load the cost matrix
        cost_matrix = load_cost_matrix()

        # Proceed with the processing based on file type
        if file_type == 'memoQ':
            df = pd.read_csv(input_file, encoding=encoding, delimiter=delimiter, engine='python', skiprows=2, header=None)

            # Specify the columns to keep (based on Excel notation)
            cols_to_keep = [0, 3, 11, 19, 27, 35, 43, 51, 59, 67, 83]  # Corresponding to A, D, L, T, AB, AJ, AR, AZ, BH, BP, CF
            df = df.iloc[:, cols_to_keep]  # Keep only the desired columns
            print(f"Shape after filtering columns: {df.shape}")

            # Define the new header
            new_header = ['Language\\File', 'Context', '101%', 'Repetitions', '100% Matches', '95% - 99%', '85% - 94%', '75% - 84%', 
                          '50% - 74%', 'No Match', 'Total']

            # Splitting the first column into 'Language' and 'File'
            extracted = df.iloc[:, 0].str.extract(r'\[(.*?)\]\s*(.*)', expand=True)
            df.insert(0, 'Language', extracted[0])
            df.insert(1, 'File', extracted[1])
            df = df.drop(df.columns[2], axis=1)

            # Update the header after processing
            df.columns = ['Language', 'File'] + new_header[1:]  # Keep the new header starting from index 1

            # Define weights for the calculation
            weights = {
                'No Match': 1.0,
                '50% - 74%': 1.0,
                '75% - 84%': 0.5,
                '85% - 94%': 0.3,
                '95% - 99%': 0.25,
                '100% Matches': 0.1,
                'Repetitions': 0.1
            }

            # Calculate Weighted Wordcounts as an integer
            df['Weighted Wordcounts'] = (
                df['No Match'] * weights['No Match'] +
                df['50% - 74%'] * weights['50% - 74%'] +
                df['75% - 84%'] * weights['75% - 84%'] +
                df['85% - 94%'] * weights['85% - 94%'] +
                df['95% - 99%'] * weights['95% - 99%'] +
                df['100% Matches'] * weights['100% Matches'] +
                df['Repetitions'] * weights['Repetitions']
            ).round(0).astype(int)

            # Calculate the cost
            df['Cost'] = df.apply(lambda row: row['Weighted Wordcounts'] * cost_matrix[row['Language']][content_type], axis=1).round(2)

        elif file_type == 'XTM':
            # Process XTM file (as per your existing code)
            pass

        # Write the processed DataFrame to the output CSV
        df.to_csv(output_file, index=False, encoding='utf-8', sep=delimiter)

        print("Processing complete.")

        # Displaying pivot table
        pivot_table = pd.pivot_table(
            df,
            index='Language',
            values=['Context', 'Repetitions', '100% Matches', '95% - 99%', '85% - 94%', '75% - 84%', 
                    '50% - 74%', 'No Match', 'Total', 'Weighted Wordcounts', 'Cost'],
            aggfunc='sum',  # Use sum to aggregate values
            fill_value=0
        )

        # Reorganizing the pivot table
        pivot_table = pivot_table[['Context', 'Repetitions', '100% Matches', '95% - 99%', '85% - 94%', 
                                    '75% - 84%', '50% - 74%', 'No Match', 'Total', 'Weighted Wordcounts', 'Cost']]

        # Format 'Cost' with the euro symbol
        pivot_table['Cost'] = pivot_table['Cost'].apply(lambda x: f"EUR {x:.2f}")

        # Print the formatted pivot table
        print(tabulate(pivot_table, headers='keys', tablefmt='grid', stralign='center'))

    except Exception as e:
        print(f"An error occurred: {e}")

if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python script.py <input_file> <output_file> <content_type>")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]
    content_type = sys.argv[3]

    process_csv(input_file, output_file, content_type)
