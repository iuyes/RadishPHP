<?php
/**
 * The paging data adapter for DbPdo object interface.
 *
 * @author Lei Lee
 * @version 1.0
 */
interface IDbAdapter {
	/**
	 * Get display the number of records per page limit.
	 *
	 * @return int
	 */
	function getPageSize();
	
	/**
	 * Get the results of a query SQL statement.
	 *
	 * @return string
	 */
	function getQueryResult();
	
	/**
	 * Set the total number of records.
	 *
	 * @param int $value
	 */
	function setRecordCount($value);
	
	/**
	 * Set the total number of pages.
	 *
	 * @param int $value
	 */
	function setPageCount($value);
	
	/**
	 * Set the starting index.
	 *
	 * @param int $value
	 */
	function setStartIndex($value);
	
	/**
	 * Set the starting serial number.
	 *
	 * @param int $value
	 */
	function setBegin($value);
	
	/**
	 * Set the cut-off number.
	 *
	 * @param int $value
	 */
	function setEnd($value);
	
	/**
	 * Set the array of query results.
	 *
	 * @param array $value
	 */
	function setResult(&$value);
}