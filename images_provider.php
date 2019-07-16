<?php

$JPG_EXTENSION = "jpg";
$JPEG_EXTENSION = "jpeg";
$PATH_TYPE_RELATIVE = 'relative';

$GET_COUNT_KEY = "count";
$GET_SORT_KEY = "sort";

$SORT_DESC_VALUE = "desc";
$SORT_ASC_VALUE = "asc";

$JSON_FILE_PATH_KEY = 'file_path';
$JSON_MD5_KEY = 'md5';
$JSON_PATH_TYPE_KEY = 'path_type';
$JSON_MODIFICATION_TIME_KEY = 'modification_time';
$JSON_COUNT_KEY = $GET_COUNT_KEY;
$JSON_SORT_KEY = $GET_SORT_KEY;
$JSON_ERROR_KEY = "error";

$VERSION = 0;

$count = isset($_GET[$GET_COUNT_KEY]) ? htmlspecialchars($_GET[$GET_COUNT_KEY]) : -1;
$sort = isset($_GET[$GET_SORT_KEY]) ? htmlspecialchars($_GET[$GET_SORT_KEY]) : $SORT_DESC_VALUE;

// Set header to JSON.
header('Content-type: application/json');

// Create JSON to return.
$json = [];
$json['version'] = $VERSION;

$json[$JSON_COUNT_KEY] = $count;
$json[$JSON_SORT_KEY] = $sort;

// Get all files from the current directory.
$files = scandir(".");
if ($files == FALSE)
{
	$files = [];
}

// Get images data.
$images = array();

$file_count = count($files);
for ($i=0; $i < $file_count; ++$i)
{ 
	$file = $files[$i];
	// if the file isn't a JPEG image, do nothing.
	if (stristr($file, $JPEG_EXTENSION) == FALSE && stristr($file, $JPG_EXTENSION) == FALSE)
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

	// Get file modification time as timestamp.
	$file_modification_time = filemtime($file);

	// Store image data.
	array_push($images, [
		$JSON_FILE_PATH_KEY => $file,
		$JSON_MD5_KEY => $file_checksum,
		$JSON_PATH_TYPE_KEY => $PATH_TYPE_RELATIVE,
		$JSON_MODIFICATION_TIME_KEY => $file_modification_time
	]);
}

// By default, order array by file modification time (newer first).
$sort_success = usort($images, function($a, $b) {
	if ($a[$JSON_MODIFICATION_TIME_KEY] < $b[$JSON_MODIFICATION_TIME_KEY])
	{
		return -1;
	}
	else if ($a[$JSON_MODIFICATION_TIME_KEY] > $b[$JSON_MODIFICATION_TIME_KEY])
	{
		return 1;
	}
	else
	{
		return 0;
	}
});

if ($sort_success == FALSE)
{
	$json[$JSON_ERROR_KEY] = "Not able to properly sort found images.";

	// Encode json to string.
	$json_string = json_encode($json);

	echo $json_string;
}

// Apply sort parameter.
if ($sort == $SORT_DESC_VALUE)
{
	$images = array_reverse($images);
}

// Apply count parameter.
if ($count > 0)
{
	$images = array_slice($images, 0, $count);
}

// Create json with gathered data.
$json['images'] = $images;

// Encode json to string.
$json_string = json_encode($json);

echo $json_string;

?>