<?php
/**
 * Imagick extension-based image processing class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class Magick implements IImageAdapter {
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
	function __construct(&$scope){
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
	 * @return boolean
	 */
	function scaling($source, $dest, $wh = 1024, $is_width = true, $quality = 80, $filters = PNG_ALL_FILTERS){
		if (!extension_loaded('imagick'))
			throw new MagickNotInstalledException('Detects that the server has not been installed imagick library to create thumbnails can not be completed.');
		
		if (!is_file($source))
			throw new FileNotFoundException('File does not exist.');
		
		if ($quality < 1 || $quality > 100) 
			throw new CreateImageException('The image quality parameters must be a number between 1-100.');
		
		try {
			$imagick = new Imagick($source);
		} catch (ImagickException $ex) {
			throw new CreateImageException('Initialize the Imagick object failed. Please check the load of the source image file format is correct.', -1);
		}
		
		if ($is_width) {
			if ($imagick->getImageWidth() <= $wh) {
				$imagick->destroy();
				return false;
			}
			$imagick->thumbnailImage($wh, NULL);
		} else {
			if ($imagick->getImageHeight() <= $wh) {
				$imagick->destroy();
				return false;
			}
			$imagick->thumbnailImage(NULL, $wh);
		}
		
		$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
		if ($quality < $imagick->getCompressionQuality()) {
			$imagick->setImageCompressionQuality($quality);
		}
		$imagick->stripImage();
		$imagick->writeImage($dest);
		$imagick->destroy();
		
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
	 * @return boolean
	 */
	function crop($source, $mode = 0, $dst_w = 120, $dst_h = 160, $quality = 80, $dest = NULL, $filters = PNG_ALL_FILTERS){
		if (!extension_loaded('imagick'))
			throw new MagickNotInstalledException('Detects that the server has not been installed imagick library to create thumbnails can not be completed.');
		
		if (!is_file($source))
			throw new FileNotFoundException('File does not exist.');
		
		if ($quality < 1 || $quality > 100) 
			throw new CreateImageException('The image quality parameters must be a number between 1-100.');
		
		try {
			$imagick = new Imagick($source);
		} catch (ImagickException $ex) {
			throw new CreateImageException('Initialize the Imagick object failed. Please check the load of the source image file format is correct.', -1);
		}
		
		switch ($mode) {
			case 0 : // Cutting the center of the location ...
				$imagick->cropThumbnailImage($dst_w, $dst_h);
				$imagick->setImagePage(0, 0, 0, 0);
				break;
			default :
				throw new RuntimeException('Undefined interface methods.', -1);
				break;
		}
		
		//$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
		$source_quality = $imagick->getCompressionQuality();
		if ($quality < $source_quality) {
			$imagick->setImageCompressionQuality($quality);
		}
		$imagick->stripImage();
		$imagick->writeImage($dest);
		$imagick->destroy();
		
		return true;
	}
	
	/**
	 * Generate images with a watermark.
	 *
	 * @param string $src
	 * @param string $dest
	 * @param string $watermark
	 * @param string $position
	 * @param int $quality
	 * @param int $filters
	 * @return boolean
	 */
	function symbol($source, $dest, $watermark, $position = 'rb', $quality = 80, $filters = PNG_ALL_FILTERS){
		if (!extension_loaded('imagick'))
			throw new MagickNotInstalledException('Detects that the server has not been installed imagick library to create thumbnails can not be completed.');
		
		if (!is_file($source))
			throw new FileNotFoundException('The source file does not exist.(' . $source . ')');
		if (!is_file($watermark))
			throw new FileNotFoundException('The watermark image does not exist.(' . $watermark . ')');
		
		if ($quality < 1 || $quality > 100) 
			throw new CreateImageException('The image quality parameters must be a number between 1-100.');
		
		try {
			$image_src = new Imagick($source);
			$image_wtm = new Imagick($watermark);
		} catch (ImagickException $ex) {
			throw new CreateImageException('Initialize the Imagick object failed. Please check the load of the source image file format is correct.', -1);
		}
		
		$gravity = Imagick::GRAVITY_CENTER;
		switch ($position) {
			case 'lt' :
				$gravity = Imagick::GRAVITY_NORTHWEST;
				break;
			case 'rt' :
				$gravity = Imagick::GRAVITY_NORTHEAST;
				break;
			case 'lb' :
				$gravity = Imagick::GRAVITY_SOUTHWEST;
				break;
			case 'rb' :
				$gravity = Imagick::GRAVITY_SOUTHEAST;
				break;
			case 'ht' :
				$gravity = Imagick::GRAVITY_NORTH;
				break;
			case 'hb' :
				$gravity = Imagick::GRAVITY_SOUTH;
				break;
			case 'vl':
				$gravity = Imagick::GRAVITY_WEST;
				break;
			case 'vr':
				$gravity = Imagick::GRAVITY_EAST;
				break;
			case 'center' :
				$gravity = Imagick::GRAVITY_CENTER;
				break;
		}
		
		$draw = new ImagickDraw();
		$draw->setGravity($gravity);
		$draw->composite($image_wtm->getImageCompose(), 0, 0, 50, 0, $image_wtm);
		$image_src->drawImage($draw);
		
		$image_wtm->destroy();
		
		$source_quality = $image_src->getCompressionQuality();
		if ($quality < $source_quality) {
			$image_src->setImageCompressionQuality($quality);
		}
		$image_src->stripImage();
		$image_src->writeImage($dest);
		$image_src->destroy();
		
		return true;
	}
}