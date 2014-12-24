<?php
class DAO_GitHubRepository extends Cerb_ORMHelper {
	const _CACHE_ALL = 'cache_cerb_github_repository_all';
	
	const ID = 'id';
	const GITHUB_ID = 'github_id';
	const GITHUB_WATCHERS = 'github_watchers';
	const GITHUB_FORKS = 'github_forks';
	const OWNER_GITHUB_ID = 'owner_github_id';
	const OWNER_GITHUB_NAME = 'owner_github_name';
	const NAME = 'name';
	const DESCRIPTION = 'description';
	const BRANCH = 'branch';
	const URL = 'url';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	const PUSHED_AT = 'pushed_at';
	const SYNCED_AT = 'synced_at';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO github_repository () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges('cerberusweb.contexts.github.repository', $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'github_repository', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.github_repository.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged('cerberusweb.contexts.github.repository', $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('github_repository', $fields, $where);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_GitHubRepository[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($repositories = $cache->load(self::_CACHE_ALL))) {
			$repositories = self::getWhere(null, DAO_GitHubRepository::NAME, true);
			$cache->save($repositories, self::_CACHE_ALL);
		}
		
		return $repositories;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_GitHubRepository[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, github_id, github_watchers, github_forks, owner_github_id, owner_github_name, name, branch, description, url, created_at, updated_at, pushed_at, synced_at ".
			"FROM github_repository ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_GitHubRepository	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getByGitHubId($remote_id) {
		$repositories = DAO_GitHubRepository::getAll();
		
		foreach($repositories as $repo) { /* @var $repo Model_GitHubRepository */
			if($repo->github_id == $remote_id)
				return self::get($repo->id);
		}
		
		return false;
	}
	
	static function getDistinctOwners() {
		$db = DevblocksPlatform::getDatabaseService();
		$results = array();
		
		$rows = $db->GetArray("SELECT DISTINCT owner_github_name FROM github_repository");
		
		foreach($rows as $row) {
			$results[] = $row['owner_github_name'];
		}
		
		return $results;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_GitHubRepository[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_GitHubRepository();
			$object->id = $row['id'];
			$object->github_id = $row['github_id'];
			$object->github_watchers = $row['github_watchers'];
			$object->github_forks = $row['github_forks'];
			$object->owner_github_id = $row['owner_github_id'];
			$object->owner_github_name = $row['owner_github_name'];
			$object->name = $row['name'];
			$object->description = $row['description'];
			$object->branch = $row['branch'];
			$object->url = $row['url'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$object->pushed_at = $row['pushed_at'];
			$object->synced_at = $row['synced_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('github_repository');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM github_repository WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.github.repository',
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_GitHubRepository::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"github_repository.id as %s, ".
			"github_repository.github_id as %s, ".
			"github_repository.github_watchers as %s, ".
			"github_repository.github_forks as %s, ".
			"github_repository.owner_github_id as %s, ".
			"github_repository.owner_github_name as %s, ".
			"github_repository.name as %s, ".
			"github_repository.description as %s, ".
			"github_repository.branch as %s, ".
			"github_repository.url as %s, ".
			"github_repository.created_at as %s, ".
			"github_repository.updated_at as %s, ".
			"github_repository.pushed_at as %s, ".
			"github_repository.synced_at as %s ",
				SearchFields_GitHubRepository::ID,
				SearchFields_GitHubRepository::GITHUB_ID,
				SearchFields_GitHubRepository::GITHUB_WATCHERS,
				SearchFields_GitHubRepository::GITHUB_FORKS,
				SearchFields_GitHubRepository::OWNER_GITHUB_ID,
				SearchFields_GitHubRepository::OWNER_GITHUB_NAME,
				SearchFields_GitHubRepository::NAME,
				SearchFields_GitHubRepository::DESCRIPTION,
				SearchFields_GitHubRepository::BRANCH,
				SearchFields_GitHubRepository::URL,
				SearchFields_GitHubRepository::CREATED_AT,
				SearchFields_GitHubRepository::UPDATED_AT,
				SearchFields_GitHubRepository::PUSHED_AT,
				SearchFields_GitHubRepository::SYNCED_AT
			);
			
		$join_sql = "FROM github_repository ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'github_repository.id',
			$select_sql,
			$join_sql
		);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_GitHubRepository', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'github_repository',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			/*
			case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
			*/
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY github_repository.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_GitHubRepository::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT github_repository.id) " : "SELECT COUNT(github_repository.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOne($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
	
};

class SearchFields_GitHubRepository implements IDevblocksSearchFields {
	const ID = 'g_id';
	const GITHUB_ID = 'g_github_id';
	const GITHUB_WATCHERS = 'g_github_watchers';
	const GITHUB_FORKS = 'g_github_forks';
	const OWNER_GITHUB_ID = 'g_owner_github_id';
	const OWNER_GITHUB_NAME = 'g_owner_github_name';
	const NAME = 'g_name';
	const DESCRIPTION = 'g_description';
	const BRANCH = 'g_branch';
	const URL = 'g_url';
	const CREATED_AT = 'g_created_at';
	const UPDATED_AT = 'g_updated_at';
	const PUSHED_AT = 'g_pushed_at';
	const SYNCED_AT = 'g_synced_at';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'github_repository', 'id', $translate->_('common.id'), null),
			self::GITHUB_ID => new DevblocksSearchField(self::GITHUB_ID, 'github_repository', 'github_id', $translate->_('dao.github_repository.github_id'), null),
			self::GITHUB_WATCHERS => new DevblocksSearchField(self::GITHUB_WATCHERS, 'github_repository', 'github_watchers', $translate->_('dao.github_repository.github_watchers'), Model_CustomField::TYPE_NUMBER),
			self::GITHUB_FORKS => new DevblocksSearchField(self::GITHUB_FORKS, 'github_repository', 'github_forks', $translate->_('dao.github_repository.github_forks'), Model_CustomField::TYPE_NUMBER),
			self::OWNER_GITHUB_ID => new DevblocksSearchField(self::OWNER_GITHUB_ID, 'github_repository', 'owner_github_id', $translate->_('dao.github_repository.owner_github_id'), null),
			self::OWNER_GITHUB_NAME => new DevblocksSearchField(self::OWNER_GITHUB_NAME, 'github_repository', 'owner_github_name', $translate->_('dao.github_repository.owner_github_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::NAME => new DevblocksSearchField(self::NAME, 'github_repository', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'github_repository', 'description', $translate->_('dao.github_repository.description'), Model_CustomField::TYPE_MULTI_LINE),
			self::BRANCH => new DevblocksSearchField(self::BRANCH, 'github_repository', 'branch', $translate->_('dao.github_repository.branch'), Model_CustomField::TYPE_SINGLE_LINE),
			self::URL => new DevblocksSearchField(self::URL, 'github_repository', 'url', $translate->_('common.url'), Model_CustomField::TYPE_SINGLE_LINE),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'github_repository', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'github_repository', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			self::PUSHED_AT => new DevblocksSearchField(self::PUSHED_AT, 'github_repository', 'pushed_at', $translate->_('dao.github_repository.pushed_at'), Model_CustomField::TYPE_DATE),
			self::SYNCED_AT => new DevblocksSearchField(self::SYNCED_AT, 'github_repository', 'synced_at', $translate->_('dao.github_repository.synced_at'), Model_CustomField::TYPE_DATE),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			'cerberusweb.contexts.github.repository',
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_GitHubRepository {
	public $id;
	public $github_id;
	public $github_watchers;
	public $github_forks;
	public $owner_github_id;
	public $owner_github_name;
	public $name;
	public $description;
	public $branch;
	public $url;
	public $created_at;
	public $updated_at;
	public $pushed_at;
	public $synced_at;
};

class View_GitHubRepository extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'github_repository';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('GitHub Repositories');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_GitHubRepository::PUSHED_AT;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_GitHubRepository::URL,
			SearchFields_GitHubRepository::GITHUB_FORKS,
			SearchFields_GitHubRepository::GITHUB_WATCHERS,
			SearchFields_GitHubRepository::BRANCH,
			SearchFields_GitHubRepository::PUSHED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_GitHubRepository::ID,
			SearchFields_GitHubRepository::GITHUB_ID,
			SearchFields_GitHubRepository::OWNER_GITHUB_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_GitHubRepository::ID,
			SearchFields_GitHubRepository::GITHUB_ID,
			SearchFields_GitHubRepository::OWNER_GITHUB_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_GitHubRepository::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_GitHubRepository', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_GitHubRepository::BRANCH:
				case SearchFields_GitHubRepository::OWNER_GITHUB_NAME:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Task::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
			
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_GitHubRepository::BRANCH:
			case SearchFields_GitHubRepository::OWNER_GITHUB_NAME:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_GitHubRepository', $column);
				break;
				
			case SearchFields_GitHubRepository::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_GitHubRepository', 'cerberusweb.contexts.github.repository', $column);
				break;
				
			case SearchFields_GitHubRepository::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_GitHubRepository', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_GitHubRepository', $column, 'github_repository.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.github.repository');
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:wgm.github::repository/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_GitHubRepository::OWNER_GITHUB_NAME:
			case SearchFields_GitHubRepository::NAME:
			case SearchFields_GitHubRepository::DESCRIPTION:
			case SearchFields_GitHubRepository::BRANCH:
			case SearchFields_GitHubRepository::URL:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_GitHubRepository::ID:
			case SearchFields_GitHubRepository::GITHUB_ID:
			case SearchFields_GitHubRepository::GITHUB_WATCHERS:
			case SearchFields_GitHubRepository::GITHUB_FORKS:
			case SearchFields_GitHubRepository::OWNER_GITHUB_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_GitHubRepository::CREATED_AT:
			case SearchFields_GitHubRepository::UPDATED_AT:
			case SearchFields_GitHubRepository::PUSHED_AT:
			case SearchFields_GitHubRepository::SYNCED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
		}
	}

	function getFields() {
		return SearchFields_GitHubRepository::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_GitHubRepository::OWNER_GITHUB_NAME:
			case SearchFields_GitHubRepository::NAME:
			case SearchFields_GitHubRepository::DESCRIPTION:
			case SearchFields_GitHubRepository::BRANCH:
			case SearchFields_GitHubRepository::URL:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_GitHubRepository::ID:
			case SearchFields_GitHubRepository::GITHUB_ID:
			case SearchFields_GitHubRepository::GITHUB_WATCHERS:
			case SearchFields_GitHubRepository::GITHUB_FORKS:
			case SearchFields_GitHubRepository::OWNER_GITHUB_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_GitHubRepository::CREATED_AT:
			case SearchFields_GitHubRepository::UPDATED_AT:
			case SearchFields_GitHubRepository::PUSHED_AT:
			case SearchFields_GitHubRepository::SYNCED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_GitHubRepository::EXAMPLE] = 'some value';
					break;
					
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_GitHubRepository::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_GitHubRepository::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_GitHubRepository::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields('cerberusweb.contexts.github.repository', $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_GitHubRepository extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.github.repository';
	
	function getRandom() {
		return DAO_GitHubRepository::random();
	}
	
	function getMeta($context_id) {
		$repo = DAO_GitHubRepository::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		//$friendly = DevblocksPlatform::strToPermalink($repo->name);
		
		return array(
			'id' => $repo->id,
			'name' => $repo->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&=type=github_repository&id=%d",$context_id), true),
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'owner_github_name',
			'synced_at',
			'pushed_at',
			'description',
			'github_forks',
			'github_watchers',
		);
	}
	
	function getContext($repo, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'GitHub Repository:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_GitHubRepository::ID);

		// Polymorph
		if(is_numeric($repo)) {
			$repo = DAO_GitHubRepository::get($repo);
		} elseif($repo instanceof Model_GitHubRepository) {
			// It's what we want already.
		} elseif(is_array($repo)) {
			$repo = Cerb_ORMHelper::recastArrayToModel($repo, 'Model_GitHubRepository');
		} else {
			$repo = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created' => $prefix.$translate->_('common.created'),
			'description' => $prefix.$translate->_('dao.github_repository.description'),
			'github_forks' => $prefix.$translate->_('dao.github_repository.github_forks'),
			'github_watchers' => $prefix.$translate->_('dao.github_repository.github_watchers'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'owner_github_name' => $prefix.$translate->_('common.owner'),
			'pushed_at' => $prefix.$translate->_('dao.github_repository.pushed_at'),
			'synced_at' => $prefix.$translate->_('dao.github_repository.synced_at'),
			'updated' => $prefix.$translate->_('common.updated'),
			'url' => $prefix.$translate->_('common.url'),
			//'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created' => Model_CustomField::TYPE_DATE,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'github_forks' => Model_CustomField::TYPE_NUMBER,
			'github_watchers' => Model_CustomField::TYPE_NUMBER,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner_github_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'pushed_at' => Model_CustomField::TYPE_DATE,
			'synced_at' => Model_CustomField::TYPE_DATE,
			'updated' => Model_CustomField::TYPE_DATE,
			'url' => Model_CustomField::TYPE_URL,
			//'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_GitHubRepository::ID;
		$token_values['_types'] = $token_types;
		
		if($repo) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $repo->name;
			$token_values['created'] = $repo->created_at;
			$token_values['description'] = $repo->description;
			$token_values['github_forks'] = $repo->github_forks;
			$token_values['github_watchers'] = $repo->github_watchers;
			$token_values['id'] = $repo->id;
			$token_values['name'] = $repo->name;
			$token_values['owner_github_name'] = $repo->owner_github_name;
			$token_values['pushed_at'] = $repo->pushed_at;
			$token_values['synced_at'] = $repo->synced_at;
			$token_values['updated'] = $repo->updated_at;
			$token_values['url'] = $repo->url;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($repo, $token_values);
			
			// URL
			//$url_writer = DevblocksPlatform::getUrlService();
			//$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=example.object&id=%d-%s",$tweet->id, DevblocksPlatform::strToPermalink($tweet->name)), true);
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_GitHubRepository::ID;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->view_columns = array(
			SearchFields_GitHubRepository::URL,
			SearchFields_GitHubRepository::GITHUB_FORKS,
			SearchFields_GitHubRepository::GITHUB_WATCHERS,
			SearchFields_GitHubRepository::BRANCH,
			SearchFields_GitHubRepository::PUSHED_AT,
		);
		$view->addParams(array(
		), true);
		$view->renderSortBy = SearchFields_GitHubRepository::PUSHED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		$view->renderFilters = false;
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_GitHubRepository::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_GitHubRepository::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};