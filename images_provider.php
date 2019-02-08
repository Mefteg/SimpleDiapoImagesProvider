<?php

$JPEG_EXTENSION = "jpg";
$PATH_TYPE_RELATIVE = 'relative';

$JSON_FILE_PATH_KEY = 'file_path';
$JSON_MD5_KEY = 'md5';
$JSON_PATH_TYPE_KEY = 'path_type';

// Get all files from the current directory.
$files = scandir(".");
if ($files == FALSE)
{
	$files = [];
}

// Get current version.
$version = 0;

// Get images data.
$images = array();

$file_count = count($files);
for ($i=0; $i < $file_count; ++$i)
{ 
	$file = $files[$i];
	// if the file isn't a JPEG image, do nothing.
	if (stristr($file, $JPEG_EXTENSION) == FALSE)
	{
		continue;
	}

	// Compute file checksum.
	$file_handle = @fopen($file, 'r');
	if ($file_handle == FALSE)
	{
		continue;
	}

	$file_data = fread($file_handle, filesize($file));
	fclose($file_handle);
	$file_checksum = hash('md5', $file_data);

	// Store image data.
	array_push($images, [
		$JSON_FILE_PATH_KEY => $file,
		$JSON_MD5_KEY => $file_checksum,
		$JSON_PATH_TYPE_KEY => $PATH_TYPE_RELATIVE
	]);
}

// Create json with gathered data.
$json = [];
$json['version'] = $version;
$json['images'] = $images;

// Encode json to string.
$json_string = json_encode($json);

echo $json_string;

?>