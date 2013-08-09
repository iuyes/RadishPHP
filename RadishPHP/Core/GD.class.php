<?php
/**
 * GD2 extension-based image processing class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class GD implements IImageAdapter {
	/**
	 * The object instance of RadishPHP.
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
	 * According to the original proportions to create the thumbnails.
	 *
	 * @param string $source
	 * @param string $dest
	 * @param int $wh
	 * @param boolean $is_width
	 * @param int $quality
	 * @param int $filters
	 */
	function scaling($source, $dest, $wh = 1024, $is_width = true, $quality = 80, $filters = PNG_ALL_FILTERS) {
		if (!extension_loaded('gd'))
			throw new GD2NotInstalledException('Detects that the server has not been installed GD library to create thumbnails can not be completed.');
		
		if (!function_exists('imagegd2'))
			throw new GD2NotInstalledException('GD library version must be 2.0 or above.');
		
		if (!is_file($source))
			throw new FileNotFoundException('File does not exist.');
		
		if ($quality < 1 || $quality > 100) 
			throw new CreateImageException('The image quality parameters must be a number between 1-100.');
			
		// Get the image width and height ...
		list($thumb_src, $type, $src_w, $src_h) = $this->getResource($source);
		
		if ($thumb_src == NULL)
			throw new CreateImageException('Failure to obtain an image resource.', -1);
		
		if ($is_width) {
			if ($src_w <= $wh) {
				return false;
			} else {
				$width = $wh;
				$height = ( int ) (( float ) $src_h * $wh / $src_w);
			}
		} else {
			if ($src_h <= $wh) {
				return false;
			} else {
				$width = ( int ) (( float ) $src_w * $wh / $src_h);
				$height = $wh;
			}
		}
		
		try {
			if ($thumb_src) {
				$thumb_dst = imagecreatetruecolor($width, $height);
				
				imagecopyresampled($thumb_dst, $thumb_src, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
				
				if (is_file($dest)) {
					unlink($dest);
				}
				
				switch ($type) {
					case IMAGETYPE_JPEG:
					case IMAGETYPE_JPEG2000:
						imagejpeg($thumb_dst, $dest, $quality);
						break;
					case IMAGETYPE_GIF:
						imagegif($thumb_dst, $dest);
						break;
					case IMAGETYPE_PNG:
						imagepng($thumb_dst, $dest, $quality, $filters);
						break;
				}
				
				
				imagedestroy($thumb_src);
				imagedestroy($thumb_dst);
			}
		} catch (Exception $ex) {
			throw new CreateImageException('Scaling image failed.(' . $ex->getMessage() . ')', -1);
		}
		
		return true;
	}

	/**
	 * Image scaling to the specified size, and automatically cut the extra part.
	 *
	 * @param string $source
	 * @param int $mode
	 * @param int $dst_w
	 * @param int $dst_h
	 * @param int $quality
	 * @param string $dest
	 * @param int $filters
	 */
	function crop($source, $mode = 0, $dst_w = 120, $dst_h = 160, $quality = 80, $dest = NULL, $filters = PNG_ALL_FILTERS) {
		if (!extension_loaded('gd'))
			throw new GD2NotInstalledException('Detects that the server has not been installed GD library to create thumbnails can not be completed.');
		
		if (!function_exists('imagegd2'))
			throw new GD2NotInstalledException('GD library version must be 2.0 or above.');
		
		if (!is_file($source))
			throw new FileNotFoundException('File does not exist.');
		
		if ($quality < 1 || $quality > 100) 
			throw new CreateImageException('The image quality parameters must be a number between 1-100.');
			
		// Get the image width and height ...
		list($src, $type, $src_w, $src_h) = $this->getResource($source);
		
		$c1 = $src_w / $src_h;
		$c2 = $dst_w / $dst_h;
		
		$src_x = 0;
		$src_y = 0;
		$src_crop_w = $src_w;
		$src_crop_h = $src_h;
		
		switch ($mode) {
			case 0 : // Cutting the center of the location ...
				if ($c1 > $c2) {
					$src_crop_w = ( int ) ((( float ) $src_h * $dst_w) / $dst_h);
					$src_x = ( int ) (($src_w - ((( float ) $src_h * $dst_w) / $dst_h)) / 2);
					$src_y = 0;
				} else if ($c1 < $c2) {
					$src_crop_h = ( int ) ((( float ) $src_w * $dst_h) / $dst_w);
					$src_x = 0;
					$src_y = ( int ) (($src_h - ((( float ) $src_w * $dst_h) / $dst_w)) / 2);
				}
				break;
		}
		
		try {
			$dst = imagecreatetruecolor($dst_w, $dst_h);
			imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_crop_w, $src_crop_h);
			
			switch ($type) {
				case IMAGETYPE_JPEG:
				case IMAGETYPE_JPEG2000:
					imagejpeg($dst, $dest, $quality);
					break;
				case IMAGETYPE_GIF:
					imagegif($dst, $dest);
					break;
				case IMAGETYPE_PNG:
					imagepng($dst, $dest, $quality, $filters);
					break;
			}
			
			imagedestroy($src);
			imagedestroy($dst);
		} catch (Exception $ex) {
			throw new CreateImageException('By cropping the image error has occurred.', -1);
		}
	}

	/**
	 * Generate images with a watermark.
	 *
	 * @param string $src
	 * @param string $watermark
	 * @param string $position
	 * @param int $quality
	 * @param int $filters
	 * @return boolean
	 */
	function symbol($source, $dest, $watermark, $position = 'rb', $quality = 80, $filters = PNG_ALL_FILTERS) {
		if (!extension_loaded('gd'))
			throw new GD2NotInstalledException('Detects that the server has not been installed GD library to create thumbnails can not be completed.');
		
		if (!function_exists('imagegd2'))
			throw new GD2NotInstalledException('GD library version must be 2.0 or above.');
		
		if (!is_file($source))
			throw new FileNotFoundException('File does not exist.(' . $source . ')');
		if (!is_file($watermark))
			throw new FileNotFoundException('File does not exist.(' . $watermark . ')');
		
		if ($quality < 1 || $quality > 100) 
			throw new CreateImageException('The image quality parameters must be a number between 1-100.');
		
		try {
			list($image_src, $type_1, $im_w1, $im_h1) = $this->getResource($source);
			list($image_wtm, $type_2, $im_w2, $im_h2) = $this->getResource($watermark);
			
			if ($image_src && $image_wtm) {
				$dst_x = $im_w1 - $im_w2;
				$dst_y = $im_h1 - $im_h2;
				
				switch ($position) {
					case 'lt' :
						$dst_x = 0;
						$dst_y = 0;
						break;
					case 'rt' :
						$dst_x = $im_w1 - $im_w2;
						$dst_y = 0;
						break;
					case 'lb' :
						$dst_x = 0;
						$dst_y = $im_h1 - $im_h2;
						break;
					case 'rb' :
						$dst_x = $im_w1 - $im_w2;
						$dst_y = $im_h1 - $im_h2;
						break;
					case 'ht' :
						$dst_x = ( int ) (($im_w1 - $im_w2) / 2);
						$dst_y = 0;
						break;
					case 'hb' :
						$dst_x = ( int ) (($im_w1 - $im_w2) / 2);
						$dst_y = $im_h1 - $im_h2;
						break;
					case 'vl':
						$dst_x = 0;
						$dst_y = ( int ) (($im_h1 - $im_h2) / 2);
						break;
					case 'vr':
						$dst_x = $im_w1 - $im_w2;
						$dst_y = ( int ) (($im_h1 - $im_h2) / 2);
						break;
					case 'center' :
						$dst_x = ( int ) (($im_w1 - $im_w2) / 2);
						$dst_y = ( int ) (($im_h1 - $im_h2) / 2);
						break;
				}
				
				imagecopy($image_src, $image_wtm, $dst_x, $dst_y, 0, 0, $im_w2, $im_h2);
				
				if (is_file($source))
					unlink($source);
				
				switch ($type_1) {
					case IMAGETYPE_JPEG:
					case IMAGETYPE_JPEG2000:
						imagejpeg($image_src, $dest, $quality);
						break;
					case IMAGETYPE_GIF:
						imagegif($image_src, $dest);
						break;
					case IMAGETYPE_PNG:
						imagepng($image_src, $dest, $quality, $filters);
						break;
				}
				
				imagedestroy($image_src);
				imagedestroy($image_wtm);
			}
		} catch (Exception $ex) {
			throw new CreateImageException('Watermark image into failure.(' . $ex->getMessage() . ')', -1);
		}
		
		return true;
	}

	/**
	 * Resources to get the image object.
	 *
	 * @param string $source
	 * @return array
	 */
	private function getResource($source) {
		list($w, $h, $type) = getimagesize($source);
		
		switch ($type) {
			case IMAGETYPE_JPEG :
			case IMAGETYPE_JPEG2000 :
				return array(imagecreatefromjpeg($source), $type, $w, $h);
			case IMAGETYPE_GIF :
				return array(imagecreatefromgif($source),  $type, $w, $h);
			case IMAGETYPE_PNG :
				return array(imagecreatefrompng($source),  $type, $w, $h);
			default :
				return false;
		}
	}
}