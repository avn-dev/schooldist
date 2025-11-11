<?php

namespace Admin\Http\Controller\Storage;

class ManagerController extends \Core\Controller\Vendor\ResourceAbstractController {
	
	protected $_sAccessRight = 'storage_admin';

	protected $_sViewClass = '\MVC_View_Smarty';

	protected $sRootDir = null;
	
	/**
	 * Simple PHP File Manager
	 * Copyright John Campbell (jcampbell1)
	 * Liscense: MIT
	 */
	public function page() {

		$this->sRootDir = \Util::getDocumentRoot().'storage/';
		
		//Security options
		$allow_delete = true; // Set to false to disable delete button and delete POST request.
		$allow_create_folder = true; // Set to false to disable folder creation
		$allow_upload = true; // Set to true to allow upload files
		$allow_direct_link = true; // Set to false to only allow downloads and not direct link
		$disallowed_extensions = ['php'];  // must be an array. Extensions disallowed to be uploaded
		$hidden_extensions = ['php']; // must be an array of lowercase file extensions. Extensions hidden in directory index

		$tmp_dir = $this->sRootDir;
		if(DIRECTORY_SEPARATOR==='\\') $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);
		$tmp = $this->get_absolute_path($tmp_dir.$_REQUEST['file']);
		$tmp = str_replace('//', '/', $tmp);

		if($tmp === false)
			$this->err(404,'File or Directory Not Found');
		if(substr($tmp, 0, strlen($tmp_dir)) !== $tmp_dir)
			$this->err(403,"Forbidden");
		if(!$_COOKIE['_sfm_xsrf'])
			setcookie('_sfm_xsrf',bin2hex(openssl_random_pseudo_bytes(16)));
		if($_POST) {
			if($_COOKIE['_sfm_xsrf'] !== $_POST['xsrf'] || !$_POST['xsrf'])
				$this->err(403,"XSRF Failure");
		}
		$file = $tmp;
		if($_GET['do'] == 'list') {
			if (is_dir($file)) {
				$directory = $file;
				$result = [];
				$files = array_diff(scandir($directory), ['.','..']);
				foreach($files as $entry) if($entry !== basename(__FILE__) && !in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), $hidden_extensions)) {
					$i = $directory . '/' . $entry;
					$stat = stat($i);
					$result[] = [
						'mtime' => $stat['mtime'],
						'size' => $stat['size'],
						'name' => basename($i),
						'path' => str_replace($this->sRootDir, '', preg_replace('@^\./@', '', $i)),
						'is_dir' => is_dir($i),
						'is_deleteable' => $allow_delete && ((!is_dir($i) && is_writable($directory)) ||
																   (is_dir($i) && is_writable($directory) && $this->is_recursively_deleteable($i))),
						'is_readable' => is_readable($i),
						'is_writable' => is_writable($i),
						'is_executable' => is_executable($i),
					];
				}
			} else {
				$this->err(412,"Not a Directory");
			}
			echo json_encode(['success' => true, 'is_writable' => is_writable($file), 'results' =>$result]);
			exit;
		} elseif ($_POST['do'] == 'delete') {
			if($allow_delete) {
				$this->rmrf($file);
			}
			exit;
		} elseif ($_POST['do'] == 'mkdir' && $allow_create_folder== true) {
			// don't allow actions outside root. we also filter out slashes to catch args like './../outside'
			$dir = \Util::getCleanFilename($_POST['name']);
			$dir = str_replace('/', '', $dir);
			if(substr($dir, 0, 2) === '..')
				exit;
			\Util::checkDir($file.$dir);
			\Util::changeDirMode($file.$dir);
			exit;
		} elseif ($_POST['do'] == 'upload' && $allow_upload == true) {
			var_dump($_POST);
			var_dump($_FILES);
			var_dump($_FILES['file_data']['tmp_name']);
			foreach($disallowed_extensions as $ext) 
				if(preg_match(sprintf('/\.%s$/',preg_quote($ext)), $_FILES['file_data']['name'])) 
					$this->err(403,"Files of this type are not allowed.");
				
			$sTargetFile = \Util::getCleanFilename($_FILES['file_data']['name']);
				
			var_dump(move_uploaded_file($_FILES['file_data']['tmp_name'], $file.'/'.$sTargetFile));
			\Util::changeFileMode($file.'/'.$sTargetFile);
			exit;
		} elseif ($_GET['do'] == 'download') {
			$filename = basename($file);
			header('Content-Type: ' . mime_content_type($file));
			header('Content-Length: '. filesize($file));
			header(sprintf('Content-Disposition: attachment; filename=%s',
				strpos('MSIE',$_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\"" ));
			ob_flush();
			readfile($file);
			exit;
		}
		
		$MAX_UPLOAD_SIZE = min($this->asBytes(ini_get('post_max_size')), $this->asBytes(ini_get('upload_max_filesize')));
		
	}

	function rmrf($dir) {
		\Util::recursiveDelete($dir);
	}

	function is_recursively_deleteable($d) {
		$stack = [$d];
		while($dir = array_pop($stack)) {
			if(!is_readable($dir) || !is_writable($dir)) 
				return false;
			$files = array_diff(scandir($dir), ['.','..']);
			foreach($files as $file) if(is_dir($file)) {
				$stack[] = "$dir/$file";
			}
		}
		return true;
	}
	// from: http://php.net/manual/en/function.realpath.php#84012
	function get_absolute_path($path) {
			$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
			$parts = explode(DIRECTORY_SEPARATOR, $path);
			$absolutes = [];
			foreach ($parts as $part) {
				if ('.' == $part) continue;
				if ('..' == $part) {
					array_pop($absolutes);
				} else {
					$absolutes[] = $part;
				}
			}
			return implode(DIRECTORY_SEPARATOR, $absolutes);
		}
	function err($code,$msg) {
		http_response_code($code);
		echo json_encode(['error' => ['code'=>intval($code), 'msg' => $msg]]);
		exit;
	}
	function asBytes($ini_v) {
		$ini_v = trim($ini_v);
		$s = ['g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10];
		return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
	}
}
