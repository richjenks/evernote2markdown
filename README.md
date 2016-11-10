# evernote2markdown

Converts exported Evernote files into Markdown — a bit hacky but gets the job done

## Features

- Converts Notebooks into folders and Notes into Markdown files
- Saves Updated/Accessed metadata
- Supports checkboxes
- Supports images

## Unsupported

- Attached files
- Codeblocks
- Audio
- Encrypted content
- Tables

## Usage

1. Clone this repo: `git clone git@github.com:richjenks/evernote2markdown.git`
1. Install dependencies: `composer install`
1. Export Evernote notebooks individually (right-click > Export > Export as a file in ENEX format)
1. Place exported `.emex` files into `emex` directory
1. Run the script: `php evernote2markdown.php`
1. Folders and Markdown files will be created in a newly-created `markdown` folder

## Considerations

- Each exported file will become a folder and each note will become a markdown file, hence the requirement to export each notebook individually
- Notebook Stacks are not supported but you can organise your notes howerver you like after conversion, e.g. into folders and subfolders to mirror your Stacks
- Notes with duplicate names will be concatenated into one note
