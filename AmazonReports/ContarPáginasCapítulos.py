import tkinter as tk
from tkinter import filedialog
from docx import Document

def count_words_in_sections_and_chapters(docx_file):
    # Open the document
    doc = Document(docx_file)
    
    # Initialize structures for counting
    section_word_counts = {}
    chapter_word_counts = {}
    total_word_count = 0
    current_section = None
    current_chapter = None

    # Define the sections we expect
    sections = [
        "Prólogo", "Luxúria", "Soberba", "Ira", "Inveja", "Gula", "Preguiça", "Avareza", "Epílogo"
    ]

    # Initialize counts
    for section in sections:
        section_word_counts[section] = 0
        chapter_word_counts[section] = {}

    # Iterate through the paragraphs in the document
    for paragraph in doc.paragraphs:
        stripped_text = paragraph.text.strip()

        # Check if the paragraph matches a section title
        if stripped_text in sections:
            # If we start a new section, finalize the previous chapter if it exists
            if current_chapter and current_section:
                # Finalize the last chapter word count
                chapter_word_counts[current_section][current_chapter] = chapter_word_counts[current_section].get(current_chapter, 0)

            # Start a new section
            current_section = stripped_text
            current_chapter = None  # Reset the chapter for the new section
            #print(f"Started new section: {current_section}")

        # Check if the paragraph matches a chapter title
        elif stripped_text.startswith("Capítulo"):
            # If we start a new chapter, finalize the previous chapter if it exists
            if current_chapter and current_section:
                chapter_word_counts[current_section][current_chapter] = chapter_word_counts[current_section].get(current_chapter, 0)

            # Start a new chapter
            current_chapter = stripped_text
            # Initialize chapter count if it's the first occurrence
            if current_chapter not in chapter_word_counts[current_section]:
                chapter_word_counts[current_section][current_chapter] = 0
            print(f"Started new chapter: {current_chapter} in section: {current_section}")

        # Count words if within a section or chapter
        if current_section:
            word_count = len(stripped_text.split())
            # Increment section word count
            section_word_counts[current_section] += word_count
            
            # Increment chapter word count if a chapter is set
            if current_chapter:
                chapter_word_counts[current_section][current_chapter] += word_count
                print(f"Added {word_count} words to {current_chapter} in {current_section}")

            # Update total word count
            total_word_count += word_count

    # Finalize the last chapter count if needed
    if current_chapter and current_section:
        chapter_word_counts[current_section][current_chapter] = chapter_word_counts[current_section].get(current_chapter, 0)

    return section_word_counts, chapter_word_counts, total_word_count

def select_file():
    root = tk.Tk()
    root.withdraw()  # Hide the root window
    file_path = filedialog.askopenfilename(title="Select a Word .docx file", filetypes=[("Word files", "*.docx")])
    return file_path

def main():
    file_path = select_file()
    if file_path:
        section_word_counts, chapter_word_counts, total_word_count = count_words_in_sections_and_chapters(file_path)
        
        print("\nWord Count by Section and Chapter:")
        for section, count in section_word_counts.items():
            print(f"{section}: {count} words")
            for chapter, chapter_count in chapter_word_counts[section].items():
                print(f"    {chapter}: {chapter_count} words")
        
        print(f"\nTotal Word Count: {total_word_count} words")

if __name__ == "__main__":
    main()
