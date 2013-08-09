<?php
include_once (RADISHPHP_ROOT_PATH . 'Interface' . DS . 'IDbAdapter.class.php');

/**
 * Page information entity class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class PageAdapter implements IDbAdapter {
	/**
	 * The total number of records.
	 *
	 * @var int
	 */
	private $recordCount = 0;

	/**
	 * Total number of pages.
	 *
	 * @var int
	 */
	private $pageCount = 1;

	/**
	 * Initial index value.
	 *
	 * @var int
	 */
	private $startIndex = 0;

	/**
	 * How many records per page.
	 *
	 * @var int
	 */
	private $pageSize = 10;

	/**
	 * The starting index number.
	 *
	 * @var int
	 */
	private $begin = 0;

	/**
	 * As an index number.
	 *
	 * @var int
	 */
	private $end = 0;

	/**
	 * The current page number.
	 *
	 * @var int
	 */
	private $currentPageIndex = 1;

	/**
	 * Page variable name.
	 *
	 * @var string
	 */
	private $offset = 'offset';

	/**
	 * Data result set.
	 *
	 * @var array
	 */
	private $dataResult = NULL;

	/**
	 * SQL - Set COUNT query.
	 *
	 * @var string
	 */
	private $query_count = NULL;

	/**
	 * SQL - Set LIST query.
	 *
	 * @var string
	 */
	private $query_list = NULL;

	/**
	 * The pattern of the link path.
	 *
	 * @var string
	 */
	private $link_pattern = NULL;

	/**
	 * Link names.
	 *
	 * @var array
	 */
	private $link_names = array(
		'First',
		'Prev',
		'Next',
		'Last'
	);

	/**
	 * Constructor.
	 *
	 */
	function __construct() {
	}

	/**
	 * Page generated connection string.
	 *
	 * @param int $style
	 * @param int $step
	 * @param int $left
	 * @param string $tpl
	 * @return string
	 */
	function toString($style = 1, $step = 5, $left = 2, $tpl = NULL) {
		$iPageCount = ceil($this->getRecordCount() / $this->getPageSize());
		$iPageIndex = ( int ) $_GET[$this->getOffset()];
		if ($iPageIndex <= 0)
			$iPageIndex = 1;
		elseif ($iPageIndex > $iPageCount)
			$iPageIndex = $iPageCount;

		if ($iPageCount <= 0)
			$iPageCount = 1;

		$str = '';

		if (1 == $style) {
			$sPattern = '<a href="%s"><li>%s</li></a>';
			
			if ($iPageIndex > 1) {
				$str .= sprintf($sPattern, $this->buildLinkStr(1), $this->link_names[0]);
				$str .= sprintf($sPattern, $this->buildLinkStr($iPageIndex - 1), $this->link_names[1]);
			}
			if ($iPageIndex < $iPageCount) {
				$str .= sprintf($sPattern, $this->buildLinkStr($iPageIndex + 1), $this->link_names[2]);
				$str .= sprintf($sPattern, $this->buildLinkStr($iPageCount), $this->link_names[3]);
			}
		} elseif (2 == $style) {
			if (!empty($tpl))
				$sPattern = $tpl;
			else 
				$sPattern = '<a href="{href}"{class}>{label}</a>';
			
			$PageStep = $step;

			if ($iPageIndex > 1) {
				$str .= $this->_toTplParser($sPattern, $this->buildLinkStr(1), $this->link_names[0], '');
				$str .= $this->_toTplParser($sPattern, $this->buildLinkStr($iPageIndex - 1), $this->link_names[1], '');
			}

			$b = $iPageIndex - $left;
			if ($b <= 0)
				$b = 1;
			$c = $b + $PageStep - 1;
			if ($c > $iPageCount)
				$c = $iPageCount;
			for ($i = $b; $i <= $c; $i++)
				$str .= $this->_toTplParser($sPattern, $this->buildLinkStr($i), $i, $iPageIndex == $i ? ' class="current"' : ' class="num"');

			if ($iPageIndex < $iPageCount) {
				$str .= $this->_toTplParser($sPattern, $this->buildLinkStr($iPageIndex + 1), $this->link_names[2], '');
				$str .= $this->_toTplParser($sPattern, $this->buildLinkStr($iPageCount), $this->link_names[3], '');
			}
		}

		return $str;
	}
	
	/**
	 * The paging information into a single array.
	 *
	 * @param int $style
	 * @param int $step
	 * @param int $left
	 * @param string $tpl
	 * @return array
	 */
	function toArray($style = 1, $step = 8, $left = 2, $tpl = NULL) {
		return array(
			'RecordCount' => $this->getRecordCount(),
			'PageCount'   => $this->getPageCount(),
			'PageIndex'   => $this->getCurrentPageIndex(),
			'StartIndex'  => $this->getStartIndex(),
			'EndIndex'    => $this->getEnd(),
			'PageSize'    => $this->getPageSize(),
			'PageHtml'    => $this->toString($style, $step, $left, $tpl)
		);
	}

	/**
	 * Set COUNT query.
	 *
	 * @param string $sql
	 * @return PageAdapter
	 */
	function setQueryCount($sql) {
		$this->query_count = $sql;
		return $this;
	}

	/**
	 * Get Count query.
	 *
	 * @return string
	 */
	function getQueryCount() {
		return $this->query_count;
	}

	/**
	 * Set LIST query.
	 *
	 * @param string $sql
	 * @return PageAdapter
	 */
	function setQueryResult($sql) {
		$this->query_list = $sql;
		return $this;
	}

	/**
	 * Get LIST query.
	 *
	 * @return string
	 */
	function getQueryResult() {
		return $this->query_list;
	}

	/**
	 * Set the data result set.
	 *
	 * @param array $data
	 * @return PageAdapter
	 */
	function setResult(&$data) {
		$this->dataResult = &$data;
		return $this;
	}

	/**
	 * Get the data result set.
	 *
	 * @return array
	 */
	function getResult() {
		return $this->dataResult;
	}

	/**
	 * The total number of records set.
	 *
	 * @param int $value
	 * @return PageAdapter
	 */
	function setRecordCount($value) {
		$this->recordCount = $value;
		return $this;
	}

	/**
	 * Get the total number of records.
	 *
	 * @return int
	 */
	function getRecordCount() {
		return ( int ) $this->recordCount;
	}

	/**
	 * Set the total number of pages.
	 *
	 * @param int $value
	 * @return PageAdapter
	 */
	function setPageCount($value) {
		$this->pageCount = $value;
		return $this;
	}

	/**
	 * Get the total number of pages.
	 *
	 * @return int
	 */
	function getPageCount() {
		return ( int ) $this->pageCount < 1 ? 1 : ( int ) $this->pageCount;
	}

	/**
	 * Set the number of how many per page.
	 *
	 * @param int $value
	 * @return PageAdapter
	 */
	function setPageSize($value) {
		$this->pageSize = $value;
		return $this;
	}

	/**
	 * Get the number of how many per page.
	 *
	 * @return int
	 */
	function getPageSize() {
		return ( int ) $this->pageSize;
	}

	/**
	 * The starting index value to set the current page.
	 *
	 * @param int $value
	 * @return PageAdapter
	 */
	function setStartIndex($value) {
		$this->startIndex = $value;
		return $this;
	}

	/**
	 * The starting index value for the current page.
	 *
	 * @return int
	 */
	function getStartIndex() {
		return ( int ) $this->startIndex < 0 ? 0 : ( int ) $this->startIndex;
	}

	/**
	 * Set the starting index number.
	 *
	 * @param int $value
	 * @return PageAdapter
	 */
	function setBegin($value) {
		$this->begin = $value;
		return $this;
	}

	/**
	 * Get the starting index number.
	 *
	 * @return int
	 */
	function getBegin() {
		return ( int ) $this->begin;
	}

	/**
	 * Set the cutoff index number.
	 *
	 * @param int $value
	 * @return PageAdapter
	 */
	function setEnd($value) {
		$this->end = $value;
		return $this;
	}

	/**
	 * As for the index number.
	 *
	 * @return int
	 */
	function getEnd() {
		return ( int ) $this->end;
	}

	/**
	 * Get the current page.
	 *
	 * @return int
	 */
	function getCurrentPageIndex() {
		return (( int ) $this->currentPageIndex < 1 ? 1 : (( int ) $this->currentPageIndex > $this->pageCount ? $this->pageCount : ( int ) $this->currentPageIndex));
	}

	/**
	 * Set page variable name.
	 *
	 * @param string $value
	 * @return PageAdapter
	 */
	function setOffset($value) {
		$this->offset = $value;

		$this->currentPageIndex = ( int ) $_GET[$this->offset];
		if (0 >= ( int ) $this->currentPageIndex)
			$this->currentPageIndex = 1;

		return $this;
	}

	/**
	 * Get page variable name.
	 *
	 * @return string
	 */
	function getOffset() {
		return $this->offset;
	}

	/**
	 * Set the pattern of the link path.
	 *
	 * @param string $linkPattern
	 * @return PageAdapter
	 */
	function setLinkPattern($linkPattern) {
		$this->link_pattern = $linkPattern;
		return $this;
	}

	/**
	 * Get the pattern of the link path.
	 *
	 * @return string
	 */
	function getLinkPattern() {
		return $this->link_pattern;
	}

	/**
	 * Set name of links.
	 *
	 * @param array $value
	 * @return PageAdapter
	 */
	function setLinkNames($value) {
		$this->link_names = $value;
		return $this;
	}

	/**
	 * Back to the link page HTML code.
	 *
	 * @param integer $offset
	 * @param integer $index
	 * @return string
	 */
	private function buildLinkStr($index) {
		if ($this->link_pattern != NULL) {
			return sprintf($this->link_pattern, $index);
		} else {
			if (empty($_SERVER['QUERY_STRING'])) {
				return $_SERVER['SCRIPT_NAME'] . '?' . $this->getOffset() . '=' . $index;
			} else {
				if (preg_match('/(\?|&)' . $this->getOffset() . '=[\-0-9]*/i', $_SERVER['REQUEST_URI'])) {
					return preg_replace('/(\?|&)' . $this->getOffset() . '=[\-0-9]*/is', '\\1' . $this->getOffset() . '=' . $index, $_SERVER['REQUEST_URI']);
				} else {
					return $_SERVER['REQUEST_URI'] . '&' . $this->getOffset() . '=' . $index;
				}
			}
		}
	}
	
	/**
	 * Parsing the template variables.
	 *
	 * @param string $tpl
	 * @param string $href
	 * @param string $label
	 * @param string $class
	 * @return string
	 */
	private function _toTplParser($tpl, $href, $label, $class) {
		$tpl = str_replace('{href}', $href, $tpl);
		$tpl = str_replace('{label}', $label, $tpl);
		$tpl = str_replace('{class}', $class, $tpl);
		
		return $tpl;
	}

	/**
	 * Get an instance of the object.
	 *
	 * @return PageAdapter
	 */
	static function instance() {
		return new PageAdapter();
	}
}