#!C:/Python312/python.exe
# -*- coding: utf-8 -*-
 
import json
import os
import sys

def process_files(base_folder):
    """Process files in the selected folder."""
    try:
        # Check if the folder is accessible and list files
        files = os.listdir(base_folder)
        # Simulate processing files
        return {'status': 'success', 'base_folder': base_folder, 'files': files}
    except Exception as e:
        # Provide detailed error information
        return {'status': 'error', 'message': f'Error accessing folder: {str(e)}'}

if __name__ == '__main__':
    if 'REQUEST_METHOD' in os.environ:
        # Running as CGI script
        print("Content-type: application/json\n")
        try:
            # Read input from CGI environment
            import cgi
            form = cgi.FieldStorage()
            base_folder = form.getvalue('base_folder')

            if base_folder:
                response = process_files(base_folder)
            else:
                response = {'status': 'error', 'message': 'No base folder provided.'}
        except Exception as e:
            response = {'status': 'error', 'message': f'Unexpected error: {str(e)}'}

        print(json.dumps(response))
    else:
        # Running from command line for testing
        if len(sys.argv) < 2:
            print("Usage: select_folder.py <base_folder>")
            sys.exit(1)

        base_folder = sys.argv[1]
        response = process_files(base_folder)
        print(json.dumps(response))