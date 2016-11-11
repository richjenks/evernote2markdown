<?php require 'vendor/autoload.php';

function out($message = '') { fwrite(STDOUT, $message . PHP_EOL); }

// HTML to Markdown converter
$converter = new League\HTMLToMarkdown\HtmlConverter([
	'strip_tags'   => true,
	'header_style' => 'atx',
]);

// Paths & files
$input_path  = implode(DIRECTORY_SEPARATOR, [__DIR__, 'input']);
$output_path = implode(DIRECTORY_SEPARATOR, [__DIR__, 'output', date('Ymdhis')]);
$input_files = scandir($input_path);
mkdir($output_path);

// Log paths
out('Paths:');
out('- Input Path: ' . $input_path);
out('- Output Path: ' . $output_path);

// Get and log list of files to convert
$files = [];
foreach ($input_files as $file) if (substr($file, -5) === '.enex') $files[] = $file;
out('Files to convert:');
foreach ($files as $file) out('- ' . $file);

// Counters
$total = 0;
$notes = 0;

// GO!
out('Starting conversion...');

// For each exported file...
foreach ($files as $file) {

	// Log filename
	out('- Converting: ' . $file);

	// Construct full paths
	$file_path   = implode(DIRECTORY_SEPARATOR, [$input_path, $file]);
	$folder_path = implode(DIRECTORY_SEPARATOR, [$output_path, substr($file, 0, -5)]);

	// Parse notebook and create folder for it
	$xml = simplexml_load_file($file_path);
	mkdir($folder_path);

	// Process each note
	foreach ($xml->note as $key => $note) {

		// Sanitise filenames
		$note->title = str_replace(['/', '<', '>', '\\', ':', '|'], ' - ', $note->title); // Separators
		$note->title = str_replace(['?', '*', '\''], '', $note->title);                   // Invalid characters
		$note->title = preg_replace('/ {2,}/', ' ', $note->title);                        // Multiple spaces
		$note->title = trim($note->title);                                                // Leading and trailing whitespace

		// Convert images
		foreach ($note->resource as $image) {
			if (substr($image->mime, 0, 6) === 'image/') {

				// Resource basename and full path
				$basename      = $note->title . '.' . $bytes = bin2hex(random_bytes(3)) . '.' . explode('/', $image->mime)[1];
				$basename      = str_replace(' ', '_', $basename);
				$resource_path = implode(DIRECTORY_SEPARATOR, [$folder_path, $basename]);

				// Export image file
				file_put_contents($resource_path, base64_decode($image->data));

				// Generate media hash
				$hash = md5(stream_get_contents(fopen($resource_path, 'r')));

				// Attempt to insert this image in place of hash in content
				$pattern       = '/<en-media hash="' . $hash . '".*\/>/';
				$filename      = $image->{'resource-attributes'}->{'file-name'};
				$url           = $image->{'resource-attributes'}->{'source-url'};
				$markdown      = '![' . $filename . '](' . $basename . ' "' . $url . '")';
				$note->content = preg_replace($pattern, $markdown, $note->content);

			}
		}

		// Pre-conversion sanitisation
		$note->content = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $note->content); // Not removed by markdown converter
		$note->content = str_replace('<en-todo checked="true"/>', '[x] ', $note->content); // Checkboxes: unchecked
		$note->content = str_replace('<en-todo checked="false"/>', '[ ] ', $note->content); // Checkboxes: checked

		// Convert to markdown
		$note->content = $converter->convert($note->content);

		// Post-conversion sanitisation
		$note->content = str_replace('\_', '_', $note->content); // Underscores are escaped for some reason
		$note->content = str_replace("\n\n", PHP_EOL, $note->content); // Fix double newlines, may leave \r which is fixed below

		// Trim whitespace on note and each line
		$note->content = trim($note->content);
		$lines         = explode(PHP_EOL, $note->content);
		foreach ($lines as $key => $line) $lines[$key] = trim($line);
		$note->content = implode(PHP_EOL, $lines);

		// Create output file
		$note_path = implode(DIRECTORY_SEPARATOR, [$folder_path, $note->title . '.md']);
		file_put_contents($note_path, $note->content, FILE_APPEND);

		// Set note updated/accessed datetime
		touch($note_path, strtotime($note->updated));

		// Iterated counters
		$total++;
		$notes++;

	}

	// Confirmation
	out('- ' . $notes . ' notes converted');
	$notes = 0;

}

// Final confirmation
out('Conversion complete: ' . $total . ' notes converted');
