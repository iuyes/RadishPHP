<?php
/**
 * File system operations objects.
 *
 * @author Lei Lee
 * @version 1.0
 */
class FileSystem {
    /**
     * Create a text file.
     *
     */
    const FSO_TYPE_CREATE_TEXT_FILE = 1;

    /**
     * Create a directory.
     *
     */
    const FSO_TYPE_CREATE_DIRECTORY = 2;

	/**
	 * RadishPHP object instance.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;

	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 */
	function __construct(&$scope) {
		$this->scope = &$scope;
	}

    /**
     * Create a text file or directory.
     *
     * @param int $fso_type
     *        Set the mode of operation. The value can be used: FSO_TYPE_CREATE_TEXT_FILE, FSO_TYPE_CREATE_DIRECTORY constant.
     * @param string $filename
     *        Set the absolute path of the file or directory.
     * @param string $content
     *        Set the content of the document to be created.
     * @param string $mode
     *        Set the text file write mode.
     *
     * @exception CreateDirectoryException
     * @exception CreateTextFileException
     * @exception CreateTextWrittenException
     */
	function create($fso_type, $filename, $content = NULL, $mode = 'w') {
	    switch ($fso_type) {
	        case self::FSO_TYPE_CREATE_DIRECTORY:
	        	if (is_dir($filename))
	        		return;
                if (false === mkdir($filename, 0777, true))
	               throw new CreateDirectoryException('Create directory failed.', -1);
	            break;
	        case self::FSO_TYPE_CREATE_TEXT_FILE:
	            if (false === ($fp = fopen($filename, $mode)))
                    throw new CreateTextFileException('Create a text file failed.', -1);

	            if (false === fwrite($fp, $content))
                    throw new CreateTextWrittenException('Failed to write a text file.', -1);
	            fclose($fp);
	            break;
	        default:
	        	throw new InvalidOperationException('FSO type is invalid.', -1);
	        	break;
	    }
	}

	/**
	 * Delete a file or directory.
	 *
	 * @param string $filename       To delete a file or specify the directory path.
	 * @return boolean
	 */
	function delete($filename) {
	    if (file_exists($filename)) {
	    	if (is_dir($filename))
	    		return $this->deleteRecursive($filename);
	    	elseif (is_file($filename))
	    		return unlink($filename);
	    }
	    return false;
	}

	/**
	 * Recursively delete directories.
	 *
	 * @param string $dirname        Specifies the absolute path to the directory to be deleted.
	 * @param boolean $delete_self   Whether to delete the directory itself?
	 * @return boolean
	 */
	function deleteRecursive($dirname, $delete_self = false) {
		if (!is_dir($dirname))
			return false;

		$d = dir($dirname);
		while (false !== ($file = $d->read())) {
			if ('.' !== $file && '..' != $file) {
				$fpath = $dirname . DIRECTORY_SEPARATOR . $file;
				if (is_dir($fpath)) {
					$this->deleteRecursive($fpath);
					rmdir($fpath);
				} elseif (is_file($fpath))
					unlink($fpath);
			}
		}
		$d->close();

		if ($delete_self)
			rmdir($dirname);

		return true;
	}

	/**
	 * Calculation file or directory in bytes.
	 *
	 * @param int $size
	 * @param string $fp Specify the absolute path to the file or directory.
	 */
	function calculateSize(&$size, $fp) {
		if (is_file($fp)) {
			$size = filesize($fp);
		} elseif (is_dir($fp)) {
			$d = dir($fp);
			while (false !== ($file = $d->read())) {
				if ('.' !== $file && '..' != $file) {
					$fpath = $fp . DIRECTORY_SEPARATOR . $file;
					if (is_dir($fpath)) {
						$this->calculateSize($size, $fpath);
					} elseif (is_file($fpath))
						$size += filesize($fpath);
				}
			}
			$d->close();
		}
	}

	/**
	 * Copy the files.
	 * When the target file directory path does not exist automatically creates a directory structure.
	 *
	 * @param string $source
	 * @param string $dest
	 */
	function copy($source, $dest) {
		if (!is_file($source))
			throw new FileNotFoundException('Source file is not a valid file format.');

		$dirname = dirname($dest);

		if (!is_dir($dirname))
			mkdir($dirname, 0777, true);

		copy($source, $dest);
	}

	/**
	 * The specified directory will be compressed into a ZIP file.
	 *
	 * @param string $zipname
	 * 	      Set ZIP archive path.
	 * @param string $dirname
	 *        Set the path to the directory will be compressed.
	 * @param boolean $create_root_dir
	 *        Whether to create a root directory?
	 */
	function compress($zipname, $dirname, $create_root_dir = true) {
		if (!(file_exists($dirname) && is_dir($dirname)))
			throw new DirectoryNotFoundException('Compressed the directory does not exist.', -1);

		$exts = get_loaded_extensions();
		if (!in_array('zip', $exts))
			throw new ZipNotInstalledException('ZIP extension is not installed.', -1);

		$fi = pathinfo($dirname);

		if (!(file_exists($fi['dirname']) && is_dir($fi['dirname'])))
			$this->create(self::FSO_TYPE_CREATE_DIRECTORY, $fi['dirname']);

		$it = new RecursiveDirectoryIterator($dirname);

		$archive = new ZipArchive();
		$res = $archive->open($zipname, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
		if ($res === true) {
			if ($create_root_dir)
				$archive->addEmptyDir($fi['filename']);
			foreach (new RecursiveIteratorIterator($it) as $file) {
				$archive->addFile($file, ($create_root_dir ? $fi['filename'] . '/' : '') . str_replace('\\', '/', str_replace($dirname . '\\', '', $file)));
			}
			$archive->close();
		} else {
			throw new CreateZipArchiveException('Create a ZIP file failed.', -1);
		}
	}

	/**
	 * ZIP file decompression implementation.
	 *
	 * @param string $zipname
	 * @param string $dest_dir
	 * @param boolean $delete_zip_archive [OPTIONAL] Delete the file after extracting.
	 */
	function decompress($zipname, $dest_dir, $delete_zip_archive = false) {
		if (!(file_exists($zipname) && is_file($zipname)))
			throw new FileNotFoundException('ZIP archive file does not exist.', -1);

		$archive = new ZipArchive();
		$res = $archive->open($zipname);
		if ($res) {
			$archive->extractTo($dest_dir);
			$archive->close();

			if ($delete_zip_archive)
				$this->delete($zipname);
		} else {
			throw new ExtractZipArchiveException('Exception occurs during decompression.', -1);
		}
	}
}