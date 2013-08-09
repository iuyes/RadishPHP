<?php
/**
 * The image processing interface definition.
 *
 * @author Lei Lee
 * @version 1.0
 */
interface IImageAdapter {
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
	function scaling($source, $dest, $wh = 1024, $is_width = true, $quality = 80, $filters = PNG_ALL_FILTERS);
	
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
	function crop($source, $mode = 0, $dst_w = 120, $dst_h = 160, $quality = 80, $dest = NULL, $filters = PNG_ALL_FILTERS);
	
	/**
	 * Generate images with a watermark.
	 *
	 * @param string $source
	 * @param string $dest
	 * @param string $watermark
	 * @param string $position
	 * @param int $quality
	 * @param int $filters
	 * @return boolean
	 */
	function symbol($source, $dest, $watermark, $position = 'rb', $quality = 80, $filters = PNG_ALL_FILTERS);
}