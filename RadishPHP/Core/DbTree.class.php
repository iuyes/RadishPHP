<?php
/**
 * The Nested Set Model Db Tree Class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class DbTree {
	const POSITION_BEFORE = 'before';

	const POSITION_AFTER = 'after';

	/**
	 * RadishPHP object instance.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;

	/**
	 * Table structure array definition.
	 *
	 * @var array
	 */
	private $table_structure = NULL;

	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 */
	function __construct(&$scope) {
		$this->scope = &$scope;
	}

	/**
	 * Create a new node.
	 *
	 * @param int $id
	 * @param array $data
	 * @return int
	 */
	function create($id, $data = array()) {
		$this->check();

		$dNode = $this->getNodeInfo($id);

		if ($dNode == false)
			return false;

		list($leftId, $rightId, $depth) = $dNode;

		$data[$this->table_structure['left']] = $rightId;
		$data[$this->table_structure['right']] = $rightId + 1;
		$data[$this->table_structure['depth']] = $depth + 1;

		$this->scope->db->execute("UPDATE `" . $this->table_structure['table'] . "` SET `" . $this->table_structure['left'] . "` = (CASE WHEN `" . $this->table_structure['left'] . "` > " . $rightId . " THEN `" . $this->table_structure['left'] . "` + 2 ELSE `" . $this->table_structure['left'] . "` END), `" . $this->table_structure['right'] . "` = (CASE WHEN `" . $this->table_structure['right'] . "` >= " . $rightId . " THEN `" . $this->table_structure['right'] . "` + 2 ELSE `" . $this->table_structure['right'] . "` END) WHERE `" . $this->table_structure['right'] . "` >= " . $rightId, NULL, DbPdo::SQL_TYPE_UPDATE);

		$fields = $vars = array();
		foreach ($data as $key => $value) {
			$fields[] = "`" . $key . "`";
			$vars[] = '?';
		}

		$inst_id = ( int ) $this->scope->db->execute("INSERT INTO `" . $this->table_structure['table'] . "` (" . implode(', ', $fields) . ") VALUES(" . implode(', ', $vars) . ")", array_values($data), DbPdo::SQL_TYPE_INSERT);

		return $inst_id;
	}

	/**
	 * Delete a node and all its child nodes.
	 *
	 * @param int $id
	 */
	function delete($id) {
		$this->check();

		$dNode = $this->getNodeInfo($id);

		if ($dNode == false)
			return false;

		list($leftId, $rightId) = $dNode;

		$this->scope->db->execute("DELETE FROM `" . $this->table_structure['table'] . "` WHERE `" . $this->table_structure['left'] . "` BETWEEN " . $leftId . " AND " . $rightId, NULL, DbPdo::SQL_TYPE_DELETE);

		$deltaId = (($rightId - $leftId) + 1);

		$this->scope->db->execute("UPDATE `" . $this->table_structure['table'] . "` SET `" . $this->table_structure['left'] . "` = (CASE WHEN `" . $this->table_structure['left'] . "` > " . $leftId . " THEN `" . $this->table_structure['left'] . "` - " . $deltaId . " ELSE `" . $this->table_structure['left'] . "` END), `" . $this->table_structure['right'] . "` = (CASE WHEN `" . $this->table_structure['right'] . "` > " . $leftId . " THEN `" . $this->table_structure['right'] . "` - " . $deltaId . " ELSE `" . $this->table_structure['right'] . "` END) WHERE `" . $this->table_structure['right'] . "` > " . $rightId, NULL, DbPdo::SQL_TYPE_DELETE);
		
		return true;
	}

	/**
	 * Moving node and all child node to another, as a subset.
	 *
	 * @param int $id
	 * @param int $to_id
	 * @return boolean
	 */
	function changeAll($id, $to_id) {
		$this->check();

		$dNode = $this->getNodeInfo($id);
		if ($dNode == false)
			return false;
		list($leftId, $rightId, $level) = $dNode;

		$dNode = $this->getNodeInfo($to_id);
		if ($dNode == false)
			return false;
		list($leftIdP, $rightIdP, $levelP) = $dNode;

		if ($id == $to_id || $leftId == $leftIdP || ($leftIdP >= $leftId && $leftIdP <= $rightId) || ($level == $levelP + 1 && $leftId > $leftIdP && $rightId < $rightIdP)) {
			return false;
		}

		if ($leftIdP < $leftId && $rightIdP > $rightId && $levelP < $level - 1) {
			$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['depth'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['depth'] . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->table_structure['depth'] . ' END, ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['right'] . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->table_structure['right'] . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['right'] . '+' . ((($rightIdP - $rightId - $level + $levelP) / 2) * 2 + $level - $levelP - 1) . ' ELSE ' . $this->table_structure['right'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->table_structure['left'] . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['left'] . '+' . ((($rightIdP - $rightId - $level + $levelP) / 2) * 2 + $level - $levelP - 1) . ' ELSE ' . $this->table_structure['left'] . ' END ' . 'WHERE ' . $this->table_structure['left'] . ' BETWEEN ' . ($leftIdP + 1) . ' AND ' . ($rightIdP - 1);
		} elseif ($leftIdP < $leftId) {
			$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['depth'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['depth'] . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->table_structure['depth'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $rightIdP . ' AND ' . ($leftId - 1) . ' THEN ' . $this->table_structure['left'] . '+' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['left'] . '-' . ($leftId - $rightIdP) . ' ELSE ' . $this->table_structure['left'] . ' END, ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['right'] . ' BETWEEN ' . $rightIdP . ' AND ' . $leftId . ' THEN ' . $this->table_structure['right'] . '+' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->table_structure['right'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['right'] . '-' . ($leftId - $rightIdP) . ' ELSE ' . $this->table_structure['right'] . ' END ' . 'WHERE (' . $this->table_structure['left'] . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ' ' . 'OR ' . $this->table_structure['right'] . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ')';
		} else {
			$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['depth'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['depth'] . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->table_structure['depth'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->table_structure['left'] . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['left'] . '+' . ($rightIdP - 1 - $rightId) . ' ELSE ' . $this->table_structure['left'] . ' END, ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['right'] . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->table_structure['right'] . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->table_structure['right'] . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_structure['right'] . '+' . ($rightIdP - 1 - $rightId) . ' ELSE ' . $this->table_structure['right'] . ' END ' . 'WHERE (' . $this->table_structure['left'] . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' ' . 'OR ' . $this->table_structure['right'] . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
		}

		$this->scope->db->execute($sql, NULL, DbPdo::SQL_TYPE_UPDATE);

		return true;
	}

	/**
	 * Change the location of two sibling nodes.
	 *
	 * @param int $id1
	 * @param int $id2
	 * @param string $position
	 * @return boolean
	 */
	function changePosition($id1, $id2, $position = 'after') {
		$this->check();

		$dNode = $this->getNodeInfo($id1);
		if ($dNode == false)
			return false;
		list($leftId1, $rightId1, $level1) = $dNode;

		$dNode = $this->getNodeInfo($id2);
		if ($dNode == false)
			return false;
		list($leftId2, $rightId2, $level2) = $dNode;

		if ($level1 != $level2)
			return false;

		if ('before' == $position) {
			if ($leftId1 > $leftId2) {
				$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['right'] . ' - ' . ($leftId1 - $leftId2) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_structure['right'] . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_structure['right'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['left'] . ' - ' . ($leftId1 - $leftId2) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_structure['left'] . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_structure['left'] . ' END ' . 'WHERE ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId2 . ' AND ' . $rightId1;
			} else {
				$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['right'] . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->table_structure['right'] . ' - ' . (($rightId1 - $leftId1 + 1)) . ' ELSE ' . $this->table_structure['right'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['left'] . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->table_structure['left'] . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_structure['left'] . ' END ' . 'WHERE ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . ($leftId2 - 1);
			}
		}
		if ('after' == $position) {
			if ($leftId1 > $leftId2) {
				$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['right'] . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_structure['right'] . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_structure['right'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['left'] . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_structure['left'] . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_structure['left'] . ' END ' . 'WHERE ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . $rightId1;
			} else {
				$sql = 'UPDATE ' . $this->table_structure['table'] . ' SET ' . $this->table_structure['right'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['right'] . ' + ' . ($rightId2 - $rightId1) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->table_structure['right'] . ' - ' . (($rightId1 - $leftId1 + 1)) . ' ELSE ' . $this->table_structure['right'] . ' END, ' . $this->table_structure['left'] . ' = CASE WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_structure['left'] . ' + ' . ($rightId2 - $rightId1) . ' ' . 'WHEN ' . $this->table_structure['left'] . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->table_structure['left'] . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_structure['left'] . ' END ' . 'WHERE ' . $this->table_structure['left'] . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId2;
			}
		}

		$this->scope->db->execute($sql, NULL, DbPdo::SQL_TYPE_UPDATE);

		return true;
	}

	/**
	 * Set the table structure.
	 *
	 * @param array $table_structure
	 * @return DbTree
	 */
	function setTableStructure($table_structure) {
		$this->table_structure = $table_structure;
		return $this;
	}

	/**
	 * Perform data checks.
	 *
	 */
	private function check() {
		if (empty($this->table_structure))
			throw new RuntimeException('Tree-table structure has not been set.', -1);

		$keys = array(
			'table',
			'left',
			'right',
			'depth',
			'primary'
		);

		foreach ($keys as $key) {
			if (empty($this->table_structure[$key]))
				throw new RuntimeException('Tree table structure must contain `' . $key . '` key.', -1);
		}
	}

	/**
	 * For a single node data.
	 *
	 * @param int $id
	 * @return array
	 */
	private function getNodeInfo($id) {
		$data = $this->scope->db->fetch("SELECT `" . $this->table_structure['left'] . "`, `" . $this->table_structure['right'] . "`, `" . $this->table_structure['depth'] . "` FROM `" . $this->table_structure['table'] . "` WHERE `" . $this->table_structure['primary'] . "` = ?", array(
			( int ) $id
		));
		if ($data) {
			return array(
				( int ) $data[$this->table_structure['left']],
				( int ) $data[$this->table_structure['right']],
				( int ) $data[$this->table_structure['depth']]
			);
		}
		return false;
	}
}