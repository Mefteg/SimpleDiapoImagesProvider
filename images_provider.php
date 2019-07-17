<?php

class ImagesProvider
{
	private static $JPG_EXTENSION = "jpg";
	private static $JPEG_EXTENSION = "jpeg";
	private static $PATH_TYPE_RELATIVE = 'relative';

	private static $GET_COUNT_KEY = "count";
	private static $GET_SORT_KEY = "sort";

	private static $SORT_DESC_VALUE = "desc";
	private static $SORT_ASC_VALUE = "asc";

	private static $JSON_IMAGES_KEY = 'images';
	private static $JSON_FILE_PATH_KEY = 'file_path';
	private static $JSON_MD5_KEY = 'md5';
	private static $JSON_PATH_TYPE_KEY = 'path_type';
	private static $JSON_MODIFICATION_TIME_KEY = 'modification_time';
	private static $JSON_COUNT_KEY = "count";
	private static $JSON_SORT_KEY = "count";
	private static $JSON_ERROR_KEY = "error";

	private static $VERSION = 0;	

	public function __construct()
	{

	}

	public function provide()
	{
		// Get parameters.
		$count = isset($_GET[self::$GET_COUNT_KEY]) ? htmlspecialchars($_GET[self::$GET_COUNT_KEY]) : -1;
		$sort = isset($_GET[self::$GET_SORT_KEY]) ? htmlspecialchars($_GET[self::$GET_SORT_KEY]) : self::$SORT_DESC_VALUE;

		// Set header to JSON.
		header("Access-Control-Allow-Origin: *");
		header('Content-type: application/json');

		// Create JSON to return.
		$json = [];
		$json['version'] = self::$VERSION;

		$json[self::$JSON_COUNT_KEY] = $count;
		$json[self::$JSON_SORT_KEY] = $sort;

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
			if (stristr($file, self::$JPEG_EXTENSION) == FALSE && stristr($file, self::$JPG_EXTENSION) == FALSE)
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
				self::$JSON_FILE_PATH_KEY => $file,
				self::$JSON_MD5_KEY => $file_checksum,
				self::$JSON_PATH_TYPE_KEY => self::$PATH_TYPE_RELATIVE,
				self::$JSON_MODIFICATION_TIME_KEY => $file_modification_time
			]);
		}

		// By default, order array by file modification time (newer first).
		$sort_success = usort($images, array("ImagesProvider", "CompareImageModificationTime"));

		if ($sort_success == FALSE)
		{
			$json[self::$JSON_ERROR_KEY] = "Not able to properly sort found images.";

			// Encode json to string.
			$json_string = json_encode($json);

			echo $json_string;
			return;
		}

		// Apply sort parameter.
		if ($sort == self::$SORT_DESC_VALUE)
		{
			$images = array_reverse($images);
		}

		// Apply count parameter.
		if ($count > 0)
		{
			$images = array_slice($images, 0, $count);
		}

		// Create json with gathered data.
		$json[self::$JSON_IMAGES_KEY] = $images;

		// Encode json to string.
		$json_string = json_encode($json);

		echo $json_string;	
	}

	private static function CompareImageModificationTime($img0, $img1)
	{
		if ($img0[self::$JSON_MODIFICATION_TIME_KEY] == $img1[self::$JSON_MODIFICATION_TIME_KEY])
		{
			return 0;
		}

		return $img0[self::$JSON_MODIFICATION_TIME_KEY] < $img1[self::$JSON_MODIFICATION_TIME_KEY] ? -1 : 1;
	}
}

function Main()
{
	$provider = new ImagesProvider();
	$provider->provide();
}

Main();

?>