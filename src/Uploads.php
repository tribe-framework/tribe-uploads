<?php
namespace Tribe;

class Uploads {

	public function getUploadDirPath()
	{
		return TRIBE_ROOT . '/uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
	}

	public function getUploadDirURL()
	{
		return BASE_URL . '/uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
	}

	public function getUploaderPath()
	{
		$folder_path = 'uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
		if (!is_dir(TRIBE_ROOT . '/' . $folder_path)) {
			mkdir(TRIBE_ROOT . '/' . $folder_path, 0755, true);
		}

		return array('upload_dir' => TRIBE_ROOT . '/' . $folder_path, 'upload_url' => BASE_URL . '/' . $folder_path);
	}

	public function getUploadedImageInSize($file_url, $thumbnail = 'md')
	{
		if (preg_match('/\.(gif|jpe?g|png)$/i', $file_url)) {
			$file_arr = array();
			$file_parts = explode('/', $file_url);
			$file_parts = array_reverse($file_parts);
			$filename = urldecode($file_parts[0]);
			if (strlen($file_parts[1]) == 2) {
				$year = $file_parts[4];
				$month = $file_parts[3];
				$day = $file_parts[2];
				$size = $file_parts[1];
			} else {
				$year = $file_parts[3];
				$month = $file_parts[2];
				$day = $file_parts[1];
			}

			if (file_exists(TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . substr(escapeshellarg($filename), 1, -1))) {
				$file_arr['path'] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . substr(escapeshellarg($filename), 1, -1);
				$file_arr['url'] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . rawurlencode($filename);
			}
			else {
				$file_arr['path'] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . substr(escapeshellarg($filename), 1, -1);
				$file_arr['url'] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . rawurlencode($filename);
			}

			return $file_arr;
		} else {
			return false;
		}
	}

	public function getUploadedFileVersions($file_url, $thumbnail = 'xs')
	{

		$file_arr = array();
		$file_parts = explode('/', $file_url);
		$file_parts = array_reverse($file_parts);
		$filename = urldecode($file_parts[0]);

		if (strlen($file_parts[1]) == 2) {
			$year = $file_parts[4];
			$month = $file_parts[3];
			$day = $file_parts[2];
			$size = $file_parts[1];
		} else {
			$year = $file_parts[3];
			$month = $file_parts[2];
			$day = $file_parts[1];
		}

		$file_arr['path']['source'] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . substr(escapeshellarg($filename), 1, -1);
		$file_arr['url']['source'] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . rawurlencode($filename);

		if (preg_match('/\.(gif|jpe?g|png)$/i', $file_url)) {
			$sizes = array('xl', 'lg', 'md', 'sm', 'xs');

			foreach ($sizes as $size) {
				if (file_exists(TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . $filename)) {
					$file_arr['path'][$size] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . substr(escapeshellarg($filename), 1, -1);
					$file_arr['url'][$size] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . rawurlencode($filename);
				}
				else {
					$file_arr['path'][$size] = $file_arr['path']['source'];
					$file_arr['url'][$size] = $file_arr['url']['source'];
				}
			}

			if (file_exists($file_arr['path'][$thumbnail])) {
				$file_arr['url']['thumbnail'] = $file_arr['url'][$thumbnail];
				$file_arr['path']['thumbnail'] = $file_arr['path'][$thumbnail];
			} else {
				$file_arr['url']['thumbnail'] = $file_arr['url']['source'];
				$file_arr['path']['thumbnail'] = $file_arr['path']['source'];
			}
		}

		return $file_arr;
	}

	public function getDirURL()
	{
		return str_replace(TRIBE_ROOT, BASE_URL, getcwd());
	}

	public function copyFileFromURL($url)
	{
		if ($url ?? false) {
			$path = $this->getUploaderPath();

			$file_name = time() . '-' . basename($url);
			$wf_uploads_path = $path['upload_dir'] . '/' . $file_name;
			$wf_uploads_url = $path['upload_url'] . '/' . $file_name;

			if (copy($url, $wf_uploads_path)) {
				return $wf_uploads_url;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function handleUpload(array $files_server_arr, array $post_server_arr = [], array $get_server_arr = []) {

		if ($post_server_arr['url'] ?? false)
			return array('status'=>'success', 'success'=>1, 'error'=>0, 'file'=>array('url'=>$post_server_arr['url']));

		else if ($files_server_arr['image'] ?? false)
			$files_server_arr['file'] = $files_server_arr['image'];

		$handle = new \Verot\Upload\Upload($files_server_arr['file']);

		//Image size variants
		$image_versions = [
			'xl' => array(
				'max_width' => 2100,
				'max_height' => 2100,
			),
			'lg' => array(
				'max_width' => 1400,
				'max_height' => 1400,
			),
			'md' => array(
				'max_width' => 700,
				'max_height' => 700,
			),
			'sm' => array(
				'max_width' => 350,
				'max_height' => 350,
			),
			'xs' => array(
				'max_width' => 100,
				'max_height' => 100,
			),
		];

		if ($handle->uploaded) {
		  
		  $file = array();
		  $uploader_path = $this->getUploaderPath();
		  $file_extension = pathinfo($files_server_arr['file']['name'], PATHINFO_EXTENSION);
		  $file['name'] = pathinfo($files_server_arr['file']['name'], PATHINFO_FILENAME).'_'.uniqid();

		  if (!in_array(strtolower($file_extension), explode(',', $_ENV['ALLOWED_FILE_EXTENSIONS_IN_UPLOADS_FOLDER']))) {
		  	return array('status'=>'error', 'error_message'=>'File format not supported by server.');
		  }

		  else {

			$handle->file_new_name_body = $file['name'];
			$handle->process($uploader_path['upload_dir']);

			$file['name'] = $handle->file_dst_name_body;
			$file['url'] = $uploader_path['upload_url'].'/'.$handle->file_dst_name;

			foreach ($image_versions as $version => $constraints) {
				$handle->file_new_name_body = $file['name'];

				$handle->image_resize         = true;
				$handle->image_x              = $constraints['max_width'];
				$handle->image_y              = $constraints['max_height'];
				$handle->image_ratio          = true;

				$handle->process($uploader_path['upload_dir'].'/'.$version);

				$file[$version]['name'] = $handle->file_dst_name_body;
				$file[$version]['url'] = $uploader_path['upload_url'].'/'.$version.'/'.$handle->file_dst_name;
			}

			if ($handle->processed) {
				return array('status'=>'success', 'success'=>1, 'error'=>0, 'file'=>$file);
				$handle->clean();
			} else {
				return array('status'=>'error', 'success'=>0, 'error'=>1, 'error_message'=>$handle->error);
			}
		  }
		}
	}
}
