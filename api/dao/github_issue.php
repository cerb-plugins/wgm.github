<?php
class DAO_GitHubIssue extends C4_ORMHelper {
	const ID = 'id';
	const GITHUB_ID = 'github_id';
	const GITHUB_NUMBER = 'github_number';
	const GITHUB_REPOSITORY_ID = 'github_repository_id';
	const TITLE = 'title';
	const IS_CLOSED = 'is_closed';
	const REPORTER_NAME = 'reporter_name';
	const REPORTER_GITHUB_ID = 'reporter_github_id';
	const MILESTONE = 'milestone';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	const CLOSED_AT = 'closed_at';
	const SYNCED_AT = 'synced_at';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO github_issue () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'github_issue', $fields);

		// Log the context update
		DevblocksPlatform::markContextChanged('cerberusweb.contexts.github.issue', $ids);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('github_issue', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_GitHubIssue[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, github_id, github_number, github_repository_id, title, is_closed, reporter_name, reporter_github_id, milestone, created_at, updated_at, closed_at, synced_at ".
			"FROM github_issue ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_GitHubIssue	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_GitHubIssue[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_GitHubIssue();
			$object->id = $row['id'];
			$object->github_id = $row['github_id'];
			$object->github_number = $row['github_number'];
			$object->github_repository_id = $row['github_repository_id'];
			$object->title = $row['title'];
			$object->is_closed = $row['is_closed'];
			$object->reporter_name = $row['reporter_name'];
			$object->reporter_github_id = $row['reporter_github_id'];
			$object->milestone = $row['milestone'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$object->closed_at = $row['closed_at'];
			$object->synced_at = $row['synced_at'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM github_issue WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => 'cerberusweb.contexts.',
                	'context_ids' => $ids
                )
            )
	    );
	    */
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_GitHubIssue::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"github_issue.id as %s, ".
			"github_issue.github_id as %s, ".
			"github_issue.github_number as %s, ".
			"github_issue.github_repository_id as %s, ".
			"github_issue.title as %s, ".
			"github_issue.is_closed as %s, ".
			"github_issue.reporter_name as %s, ".
			"github_issue.reporter_github_id as %s, ".
			"github_issue.milestone as %s, ".
			"github_issue.created_at as %s, ".
			"github_issue.updated_at as %s, ".
			"github_issue.closed_at as %s, ".
			"github_issue.synced_at as %s ",
				SearchFields_GitHubIssue::ID,
				SearchFields_GitHubIssue::GITHUB_ID,
				SearchFields_GitHubIssue::GITHUB_NUMBER,
				SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID,
				SearchFields_GitHubIssue::TITLE,
				SearchFields_GitHubIssue::IS_CLOSED,
				SearchFields_GitHubIssue::REPORTER_NAME,
				SearchFields_GitHubIssue::REPORTER_GITHUB_ID,
				SearchFields_GitHubIssue::MILESTONE,
				SearchFields_GitHubIssue::CREATED_AT,
				SearchFields_GitHubIssue::UPDATED_AT,
				SearchFields_GitHubIssue::CLOSED_AT,
				SearchFields_GitHubIssue::SYNCED_AT
			);
			
		$join_sql = "FROM github_issue ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'github_issue.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		array_walk_recursive(
			$params,
			array('DAO_GitHubIssue', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
	
		return array(
			'primary_table' => 'github_issue',
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
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
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
			($has_multiple_values ? 'GROUP BY github_issue.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_GitHubIssue::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT github_issue.id) " : "SELECT COUNT(github_issue.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_GitHubIssue implements IDevblocksSearchFields {
	const ID = 'g_id';
	const GITHUB_ID = 'g_github_id';
	const GITHUB_NUMBER = 'g_github_number';
	const GITHUB_REPOSITORY_ID = 'g_github_repository_id';
	const TITLE = 'g_title';
	const IS_CLOSED = 'g_is_closed';
	const REPORTER_NAME = 'g_reporter_name';
	const REPORTER_GITHUB_ID = 'g_reporter_github_id';
	const MILESTONE = 'g_milestone';
	const CREATED_AT = 'g_created_at';
	const UPDATED_AT = 'g_updated_at';
	const CLOSED_AT = 'g_closed_at';
	const SYNCED_AT = 'g_synced_at';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'github_issue', 'id', $translate->_('common.id'), null),
			self::GITHUB_ID => new DevblocksSearchField(self::GITHUB_ID, 'github_issue', 'github_id', $translate->_('dao.github_issue.github_id'), null),
			self::GITHUB_NUMBER => new DevblocksSearchField(self::GITHUB_NUMBER, 'github_issue', 'github_number', $translate->_('dao.github_issue.github_number'), Model_CustomField::TYPE_NUMBER),
			self::GITHUB_REPOSITORY_ID => new DevblocksSearchField(self::GITHUB_REPOSITORY_ID, 'github_issue', 'github_repository_id', $translate->_('dao.github_issue.github_repository_id'), null),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'github_issue', 'title', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'github_issue', 'is_closed', $translate->_('dao.github_issue.is_closed'), Model_CustomField::TYPE_CHECKBOX),
			self::REPORTER_NAME => new DevblocksSearchField(self::REPORTER_NAME, 'github_issue', 'reporter_name', $translate->_('dao.github_issue.reporter_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::REPORTER_GITHUB_ID => new DevblocksSearchField(self::REPORTER_GITHUB_ID, 'github_issue', 'reporter_github_id', $translate->_('dao.github_issue.reporter_github_id'), null),
			self::MILESTONE => new DevblocksSearchField(self::MILESTONE, 'github_issue', 'milestone', $translate->_('dao.github_issue.milestone'), Model_CustomField::TYPE_SINGLE_LINE),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'github_issue', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'github_issue', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			self::CLOSED_AT => new DevblocksSearchField(self::CLOSED_AT, 'github_issue', 'closed_at', $translate->_('dao.github_issue.closed_at'), Model_CustomField::TYPE_DATE),
			self::SYNCED_AT => new DevblocksSearchField(self::SYNCED_AT, 'github_issue', 'synced_at', $translate->_('dao.github_issue.synced_at'), Model_CustomField::TYPE_DATE),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		//}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_GitHubIssue {
	public $id;
	public $github_id;
	public $github_number;
	public $github_repository_id;
	public $title;
	public $is_closed;
	public $reporter_name;
	public $reporter_github_id;
	public $milestone;
	public $created_at;
	public $updated_at;
	public $closed_at;
	public $synced_at;
};

class View_GitHubIssue extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'github_issue';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('GitHub Issues');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_GitHubIssue::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID,
			SearchFields_GitHubIssue::REPORTER_NAME,
			SearchFields_GitHubIssue::MILESTONE,
			SearchFields_GitHubIssue::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_GitHubIssue::ID,
			SearchFields_GitHubIssue::GITHUB_ID,
			SearchFields_GitHubIssue::REPORTER_GITHUB_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_GitHubIssue::ID,
			SearchFields_GitHubIssue::GITHUB_ID,
			SearchFields_GitHubIssue::REPORTER_GITHUB_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_GitHubIssue::search(
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
		return $this->_doGetDataSample('DAO_GitHubIssue', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID:
				case SearchFields_GitHubIssue::IS_CLOSED:
				case SearchFields_GitHubIssue::MILESTONE:
				case SearchFields_GitHubIssue::REPORTER_NAME:
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
			case SearchFields_GitHubIssue::MILESTONE:
			case SearchFields_GitHubIssue::REPORTER_NAME:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_GitHubIssue', $column);
				break;
				
			case SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID:
				$label_map = array();
				
				$repositories = DAO_GitHubRepository::getWhere();
				foreach($repositories as $repo) {
					$label_map[$repo->id] = $repo->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_GitHubIssue', $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_GitHubIssue::IS_CLOSED:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_GitHubIssue', $column);
				break;
				
// 			case SearchFields_GitHubIssue::VIRTUAL_WATCHERS:
// 				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_GitHubIssue', $column);
// 				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_GitHubIssue', $column, 'github_issue.id');
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
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		// [TODO] Cache!!
		$repositories = DAO_GitHubRepository::getWhere();
		$tpl->assign('repositories', $repositories);
		
		$tpl->assign('view_template', 'devblocks:wgm.github::issue/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID:
			case SearchFields_GitHubIssue::TITLE:
			case SearchFields_GitHubIssue::REPORTER_NAME:
			case SearchFields_GitHubIssue::MILESTONE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_GitHubIssue::ID:
			case SearchFields_GitHubIssue::GITHUB_ID:
			case SearchFields_GitHubIssue::GITHUB_NUMBER:
			case SearchFields_GitHubIssue::REPORTER_GITHUB_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_GitHubIssue::IS_CLOSED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_GitHubIssue::CREATED_AT:
			case SearchFields_GitHubIssue::UPDATED_AT:
			case SearchFields_GitHubIssue::CLOSED_AT:
			case SearchFields_GitHubIssue::SYNCED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_GitHubIssue::IS_CLOSED:
				parent::_renderCriteriaParamBoolean($param);
				break;
			
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
		return SearchFields_GitHubIssue::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_GitHubIssue::ID:
			case SearchFields_GitHubIssue::TITLE:
			case SearchFields_GitHubIssue::REPORTER_NAME:
			case SearchFields_GitHubIssue::MILESTONE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_GitHubIssue::GITHUB_ID:
			case SearchFields_GitHubIssue::GITHUB_NUMBER:
			case SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID:
			case SearchFields_GitHubIssue::REPORTER_GITHUB_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_GitHubIssue::CREATED_AT:
			case SearchFields_GitHubIssue::UPDATED_AT:
			case SearchFields_GitHubIssue::CLOSED_AT:
			case SearchFields_GitHubIssue::SYNCED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_GitHubIssue::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
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
					//$change_fields[DAO_GitHubIssue::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_GitHubIssue::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_GitHubIssue::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_GitHubIssue::update($batch_ids, $change_fields);
			}

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_GitHubIssue::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_GitHubIssue extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.github.issue';
	
	function getRandom() {
		//return DAO_GitHubIssue::random();
	}
	
	function getMeta($context_id) {
		$issue = DAO_GitHubIssue::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		//$friendly = DevblocksPlatform::strToPermalink($repo->name);
		
		return array(
			'id' => $issue->id,
			'name' => $issue->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&=type=github_issue&id=%d",$context_id), true),
		);
	}
	
	function getContext($issue, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'GitHub Repository:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_GitHubIssue::ID);

		// Polymorph
		if(is_numeric($issue)) {
			$issue = DAO_GitHubIssue::get($repo);
		} elseif($issue instanceof Model_GitHubIssue) {
			// It's what we want already.
		} else {
			$issue = null;
		}
		
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'tritle' => $prefix.$translate->_('common.title'),
			'updated|date' => $prefix.$translate->_('common.updated'),
			//'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_GitHubIssue::ID;
		
		if($issue) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $issue->title;
			$token_values['created'] = $issue->created_at;
			$token_values['id'] = $issue->id;
			$token_values['title'] = $issue->title;
			$token_values['updated'] = $issue->updated_at;
			
			// URL
			//$url_writer = DevblocksPlatform::getUrlService();
			//$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=example.object&id=%d-%s",$tweet->id, DevblocksPlatform::strToPermalink($tweet->name)), true);
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_GitHubIssue::ID;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
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
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
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
			SearchFields_GitHubIssue::GITHUB_REPOSITORY_ID,
			SearchFields_GitHubIssue::REPORTER_NAME,
			SearchFields_GitHubIssue::MILESTONE,
			SearchFields_GitHubIssue::UPDATED_AT,
		);
		$view->addParams(array(
		), true);
		$view->renderSortBy = SearchFields_GitHubIssue::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		$view->renderFilters = false;
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_GitHubIssue::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_GitHubIssue::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};