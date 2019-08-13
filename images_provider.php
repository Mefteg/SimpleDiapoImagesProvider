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

			if (stristr($file, "simpleslideshow_resized") == FALSE)
			{
				$resized_image_filename = $file . "simpleslideshow_resized.jpg";

				if (file_exists($resized_image_filename) == TRUE)
				{
					// The file already has been resized already.
					continue;
				}

				// Create a resized file if necessary.
				$image_size = getimagesize($file);
				$image_width = $image_size[0];
				$image_height = $image_size[1];

				if ($image_width > 1920 || $image_height > 1080)
				{
					// Compute new image ratios.
					$width_ratio = 1;
					$height_ratio = 1;

					if ($image_width > $image_height)
					{
						$width_ratio = 1920 / $image_width;
						$height_ratio = $width_ratio;
					}
					else
					{
						$height_ratio = 1080 / $image_height;
						$width_ratio = $height_ratio;
					}

					// Resize the file.
					$new_width = floor($image_width * $width_ratio);
					$new_height = floor($image_height * $height_ratio);

					$resized_image = imagecreatetruecolor($new_width, $new_height);
					$image = imagecreatefromjpeg($file);

					imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $image_width, $image_height);

					imagejpeg($resized_image, $resized_image_filename);

					$resized_image = NULL;
					$image = NULL;

					$file = $resized_image_filename;
				}	
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