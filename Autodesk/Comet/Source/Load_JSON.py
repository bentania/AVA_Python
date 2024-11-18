import os
import json
import pandas as pd
import logging
from tkinter import Tk
from tkinter.filedialog import askdirectory
import Levenshtein  # Import the Levenshtein module

def setup_logging(base_folder):
    """Set up the logging configuration."""
    log_file_path = os.path.join(base_folder, 'FilesProcessed.log')
    
    # Configure logging to overwrite the file
    logger = logging.getLogger()
    logger.setLevel(logging.INFO)
    
    # Clear any existing handlers
    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
    
    # Set up file handler to overwrite the log file
    file_handler = logging.FileHandler(log_file_path, mode='w')
    file_handler.setLevel(logging.INFO)
    
    # Set up formatter
    formatter = logging.Formatter('%(asctime)s - %(levelname)s - %(message)s')
    file_handler.setFormatter(formatter)
    
    # Add the handler to the logger
    logger.addHandler(file_handler)
    
    logging.info("Logging started.")

def select_base_folder():
    """Prompt the user to select the base folder."""
    Tk().withdraw()  # we don't want a full GUI, so keep the root window from appearing
    folder_selected = askdirectory()  # show an "Open" dialog box and return the path to the selected folder
    return folder_selected

def count_words(text):
    """Count the number of words in a given text."""
    return len(text.split())

def process_json_files(base_folder):
    """Process JSON files in the directory structure and compile data into Excel files with side-by-side comparisons."""
    # Get list of language directories
    languages = [d for d in os.listdir(base_folder) if os.path.isdir(os.path.join(base_folder, d))]
    
    for language in languages:
        lang_folder = os.path.join(base_folder, language)
        
        # Initialize DataFrames for storing combined data
        df_combined = pd.DataFrame()
        total_words = 0

        # Iterate through test folders
        for test_folder in ['test1']:
            test_folder_path = os.path.join(lang_folder, test_folder)
            if not os.path.exists(test_folder_path):
                continue

            json_file_path = os.path.join(test_folder_path, 'comet_compare.json')
            if os.path.isfile(json_file_path):
                with open(json_file_path, 'r', encoding='utf-8') as file:
                    data = json.load(file)

                source_texts = data['source']
                references = data.get('reference', [])  # Get the reference values

                # Initialize dictionaries to hold translations and scores
                translations_dict = {'Source': [], 'TranslationA': [], 'ScoreA': [], 'TranslationB': [], 'ScoreB': [], 'Reference': []}
                
                for translation in data['translations']:
                    name = translation['name'].replace('.txt', '')
                    if name in ['microsoft-custom', 'autodesk-nmt']: #Change to "microsoft" or "autodesk-nmt"
                        for idx, mt_text in enumerate(translation['mt']):
                            source_text = source_texts[idx]
                            score = translation['scores'][idx]
                            reference = references[idx] if idx < len(references) else None
                            if name == 'microsoft-custom':
                                translations_dict['Source'].append(source_text)
                                translations_dict['Reference'].append(reference)
                                translations_dict['TranslationA'].append(mt_text)
                                translations_dict['TranslationB'].append(None)
                                translations_dict['ScoreA'].append(score)
                                translations_dict['ScoreB'].append(None)
                            elif name == 'autodesk-nmt':   #Change to "microsoft" or "autodesk-nmt"
                                if source_text in translations_dict['Source']:
                                    index = translations_dict['Source'].index(source_text)
                                    translations_dict['TranslationB'][index] = mt_text
                                    translations_dict['ScoreB'][index] = score
                                else:
                                    translations_dict['Source'].append(source_text)
                                    translations_dict['Reference'].append(reference)
                                    translations_dict['TranslationA'].append(None)
                                    translations_dict['TranslationB'].append(mt_text)
                                    translations_dict['ScoreA'].append(None)
                                    translations_dict['ScoreB'].append(score)

                # Create DataFrame from the dictionary
                df = pd.DataFrame(translations_dict)

                # Calculate Levenshtein distance
                df['Levenshtein Distance'] = df.apply(
                    lambda row: Levenshtein.distance(row['TranslationA'], row['TranslationB']) if pd.notnull(row['TranslationA']) and pd.notnull(row['TranslationB']) else None,
                    axis=1
                )

                # Add to combined DataFrame
                df_combined = pd.concat([df_combined, df], ignore_index=True)

        # Write the combined DataFrame to an Excel file
        excel_file_path = os.path.join(base_folder, f'{language}.xlsx')
        with pd.ExcelWriter(excel_file_path, engine='xlsxwriter') as writer:
            df_combined.to_excel(writer, sheet_name='Comparison', index=False)

            # Apply formatting
            workbook  = writer.book
            percentage_format = workbook.add_format({'num_format': '0.00%'})  # Define the percentage format
            
            worksheet = writer.sheets['Comparison']
            worksheet.set_column('A:A', 30)  # Set width for Source column
            worksheet.set_column('B:B', 30)  # Set width for TranslationA column
            worksheet.set_column('C:C', 30)  # Set width for TranslationB column
            worksheet.set_column('D:D', 30)  # Set width for Reference column
            worksheet.set_column('E:E', 15)  # Set width for Levenshtein Distance column
            worksheet.set_column('F:F', 15)  # Set width for ScoreA column
            worksheet.set_column('G:G', 15)  # Set width for ScoreB column

        logging.info(f'{language} - Data successfully written to {excel_file_path}')
        logging.info(f'{language} - Total words in Source column: {total_words}')

if __name__ == '__main__':
    base_folder = select_base_folder()
    if base_folder:
        setup_logging(base_folder)
        process_json_files(base_folder)
