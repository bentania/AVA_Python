import pandas as pd
import chardet
import sys
from tabulate import tabulate
from http.server import SimpleHTTPRequestHandler, HTTPServer
import threading

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
        'Albanian': {'Software': 0.149, 'Marketing': 0.157},
        'Arabic (United Arab Emirates)': {'Software': 0.191, 'Marketing': 0.2106},
        'Bulgarian': {'Software': 0.149, 'Marketing': 0.157},
        'Czech': {'Software': 0.172, 'Marketing': 0.1826},
        'Danish': {'Software': 0.206, 'Marketing': 0.2274},
        'Dutch': {'Software': 0.182, 'Marketing': 0.2005},
        'Finnish': {'Software': 0.206, 'Marketing': 0.2274},
        'French (France)': {'Software': 0.168, 'Marketing': 0.1837},
        'French (Canada)': {'Software': 0.206, 'Marketing': 0.2274},
        'German (Germany)': {'Software': 0.168, 'Marketing': 0.1837},
        'Greek': {'Software': 0.174, 'Marketing': 0.1926},
        'Hebrew (Israel)': {'Software': 0.182, 'Marketing': 0.2005},
        'Croatian': {'Software': 0.191, 'Marketing': 0.2106},
        'Hindi (India)': {'Software': 0.174, 'Marketing': 0.1826},
        'Hungarian': {'Software': 0.182, 'Marketing': 0.2005},
        'Italian': {'Software': 0.168, 'Marketing': 0.1837},
        'Japanese': {'Software': 0.231, 'Marketing': 0.227},
        'Korean': {'Software': 0.168, 'Marketing': 0.1837},
        'Norwegian': {'Software': 0.206, 'Marketing': 0.2274},
        'Polish': {'Software': 0.174, 'Marketing': 0.1826},
        'Portuguese (Brazil)': {'Software': 0.124, 'Marketing': 0.1568},
        'Portuguese': {'Software': 0.161, 'Marketing': 0.177},
        'Russian': {'Software': 0.146, 'Marketing': 0.1613},
        'Serbian - Serbia (Latin)': {'Software': 0.191, 'Marketing': 0.214},
        'Slovak': {'Software': 0.178, 'Marketing': 0.196},
        'Spanish (Spain)': {'Software': 0.146, 'Marketing': 0.1613},
        'Spanish (Mexico)': {'Software': 0.13, 'Marketing': 0.1613},
        'Swedish': {'Software': 0.206, 'Marketing': 0.2274},
        'Turkish': {'Software': 0.174, 'Marketing': 0.1826},
        'Chinese (Simplified)': {'Software': 0.107, 'Marketing': 0.1232},
        'Chinese (Traditional)': {'Software': 0.141, 'Marketing': 0.1546}
    }

def process_xtm_csv(input_file, output_file, content_type, encoding, delimiter):
    df = None  # Initialize df to None to ensure it's always defined
    try:
        print("Processing XTM file...")

        # Read the XTM file
        df = pd.read_csv(input_file, encoding=encoding, delimiter=delimiter, skiprows=1)

        if df.empty:
            print("Warning: The input file is empty.")
            return

        # Keep only the first 15 columns
        df = df.iloc[:, :15]
        print(f"Shape after filtering columns: {df.shape}")

        # Remove rows that contain "All" in the first or second column
        df = df[~df.iloc[:, 0].str.contains("All", na=False)]
        df = df[~df.iloc[:, 1].str.contains("All", na=False)]

        if df.empty:
            print("Warning: No data left after filtering 'All'.")
            return

        # Define the new header names
        new_header = ['File', 'Target language', 'Words total count', 'Words non-translatable',
                      'Words ICE match', 'Words leveraged match', 'Words 95-99 fuzzy match',
                      'Words 85-94 fuzzy match', 'Words 75-84 fuzzy match', 'Words Machine translation',
                      'Words repeat', 'Words 95-99 fuzzy repeat', 'Words 85-94 fuzzy repeat',
                      'Words 75-84 fuzzy repeat', 'Words no matching']

        df.columns = new_header

        # Calculate Weighted Words
        weighted_words_weights = {
            'Words no matching': 1.0,
            'Words 75-84 fuzzy match': 0.5,
            'Words 85-94 fuzzy match': 0.3,
            'Words 95-99 fuzzy match': 0.25,
            'Words leveraged match': 0.1,
            'Words repeat': 0.1
        }

        # Calculate Weighted Words based on the weights
        df['Weighted Words'] = (
            df['Words no matching'] * weighted_words_weights['Words no matching'] +
            df['Words 75-84 fuzzy match'] * weighted_words_weights['Words 75-84 fuzzy match'] +
            df['Words 85-94 fuzzy match'] * weighted_words_weights['Words 85-94 fuzzy match'] +
            df['Words 95-99 fuzzy match'] * weighted_words_weights['Words 95-99 fuzzy match'] +
            df['Words leveraged match'] * weighted_words_weights['Words leveraged match'] +
            df['Words repeat'] * weighted_words_weights['Words repeat']
        ).round().astype(int)

        # Load cost matrix
        cost_matrix = load_cost_matrix()

        # Add a Cost column by multiplying Weighted Words by the corresponding price
        def calculate_cost(row):
            lang = row['Target language']
            price = cost_matrix.get(lang, {}).get(content_type, None)  # Change to None to handle missing price
            if price is None:
                print(f"Warning: No price found for language '{lang}' and content type '{content_type}'")
                return 0.00  # Return 0.00 if price is not found
            cost = row['Weighted Words'] * price
            return round(cost, 2)  # Round to 2 decimal places

        df['Cost'] = df.apply(calculate_cost, axis=1)

        # Check if df is still None before writing to CSV
        if df is not None and not df.empty:
            # Write the processed DataFrame to the output CSV
            df.to_csv(output_file, index=False, encoding='utf-8', sep=delimiter)
            print("XTM processing complete.")
        else:
            print("Error: DataFrame is empty. The output file was not created.")
            return

        # Display results in browser
        display_in_browser(df)

        # Show pivot table
        show_pivot_table(df)

    except Exception as e:
        print(f"An error occurred while processing XTM: {e}")


def display_in_browser(df):
    """Display the DataFrame in a web browser."""
    # Convert the DataFrame to an HTML table using tabulate
    html_table = tabulate(df, headers='keys', tablefmt='html', showindex=False)

    # Create a simple HTTP server to serve the HTML
    class SimpleHTTPRequestHandlerWithHTML(SimpleHTTPRequestHandler):
        def do_GET(self):
            self.send_response(200)
            self.send_header("Content-type", "text/html")
            self.end_headers()
            self.wfile.write(html_table.encode('utf-8'))

    # Start the server
    server_address = ('', 8000)  # Serve on all available interfaces, port 8000
    httpd = HTTPServer(server_address, SimpleHTTPRequestHandlerWithHTML)

    print("Serving on http://localhost:8000")
    thread = threading.Thread(target=httpd.serve_forever)
    thread.daemon = True
    thread.start()

def show_pivot_table(df):
    """Create and display a pivot table from the DataFrame."""
    
    # Create the pivot table to get the sum of 'Cost' and 'Weighted Words'
    pivot_table = pd.pivot_table(
        df,
        index='Target language',
        values=[
            'Cost',
            'Weighted Words',
            'Words total count',
            'Words non-translatable',
            'Words ICE match',
            'Words leveraged match',
            'Words 95-99 fuzzy match',
            'Words 85-94 fuzzy match',
            'Words 75-84 fuzzy match',
            'Words Machine translation',
            'Words repeat',
            'Words 95-99 fuzzy repeat',
            'Words 85-94 fuzzy repeat',
            'Words 75-84 fuzzy repeat',
            'Words no matching'
        ],
        aggfunc='sum',  # Use sum to aggregate values
        fill_value=0
    ).reset_index()

    # Format 'Cost' with the euro symbol
    pivot_table['Cost'] = pivot_table['Cost'].apply(lambda x: f"EUR {x:.2f}")

    # Print the formatted pivot table
    print("\nPivot Table:")
    print(tabulate(pivot_table, headers='keys', tablefmt='grid', stralign='center'))

if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python script.py <input_file> <output_file> <content_type>")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]
    content_type = sys.argv[3]
    process_xtm_csv(input_file, output_file, content_type, detect_encoding(input_file), detect_delimiter(input_file, detect_encoding(input_file)))
