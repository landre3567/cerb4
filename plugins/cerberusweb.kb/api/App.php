<?php
if (class_exists('Extension_ResearchTab')):
class ChKbResearchTab extends Extension_ResearchTab {
	const VIEW_RESEARCH_KB_BROWSE = 'research_kb_browse';
	const VIEW_RESEARCH_KB_SEARCH = 'research_kb_search';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		@$request_path = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$tpl->assign('request_path', $request_path);

		@$stack =  explode('/', $request_path);
		
		@array_shift($stack); // research
		@array_shift($stack); // kb
		
		@$action = array_shift($stack);
		
		switch($action) {
			case 'search':
				if(null == ($view = C4_AbstractViewLoader::getView(null, self::VIEW_RESEARCH_KB_SEARCH))) {
					$view = new C4_KbArticleView();
					$view->id = self::VIEW_RESEARCH_KB_SEARCH;
					$view->name = $translate->_('common.search_results');
					C4_AbstractViewLoader::setView($view->id, $view);
				}
				
				$tpl->assign('view', $view);
				$tpl->assign('view_fields', C4_KbArticleView::getFields());
				$tpl->assign('view_searchable_fields', C4_KbArticleView::getSearchFields());
				
				$tpl->assign('response_uri', 'research/kb/search');

				$tpl->display($tpl_path . 'research_tab/kb/search.tpl');
				break;
				
			default:
				$root_id = intval($action);
				$tpl->assign('root_id', $root_id);
		
				$tree = DAO_KbCategory::getTreeMap($root_id);
				$tpl->assign('tree', $tree);
		
				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				// Breadcrumb // [TODO] API-ize inside Model_KbTree ?
				$breadcrumb = array();
				$pid = $root_id;
				while(0 != $pid) {
					$breadcrumb[] = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				$tpl->assign('breadcrumb',array_reverse($breadcrumb));
				
				$tpl->assign('mid', @intval(ceil(count($tree[$root_id])/2)));
				
				if(null == ($view = C4_AbstractViewLoader::getView(null, self::VIEW_RESEARCH_KB_BROWSE))) {
					$view = new C4_KbArticleView();
					$view->id = self::VIEW_RESEARCH_KB_BROWSE;
				}
				
				// Articles
				if(empty($root_id)) {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,DevblocksSearchCriteria::OPER_IS_NULL,true),
					);
					$view->name = $translate->_('kb.view.uncategorized');
					
				} else {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root_id),
					);
					$view->name = vsprintf($translate->_('kb.view.articles'), $categories[$root_id]->name);
				}
		
				C4_AbstractViewLoader::setView($view->id, $view);
				
				$tpl->assign('view', $view);
				
				$tpl->display($tpl_path . 'research_tab/kb/browse.tpl');	
				break;
		}
	}
}
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class ChKbReplyToolbarButton extends Extension_ReplyToolbarItem {
		function render(CerberusMessage $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('message', $message); /* @var $message CerberusMessage */
			
			$tpl->display('file:' . $tpl_path . 'renderers/reply_kb_button.tpl');
		}
	};
endif;

class ChKbAjaxController extends DevblocksControllerExtension {
	private $_CORE_TPL_PATH = '';
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_CORE_TPL_PATH = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // timetracking

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	function showArticlePeekPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
		}
		
		$tpl->display('file:' . $this->_TPL_PATH . 'ajax/article_peek_panel.tpl');
	}

	function showTopicEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		if(!empty($id)) {
			$topic = DAO_KbCategory::get($id);
			$tpl->assign('topic', $topic);
		}
		
		$tpl->display('file:' . $this->_TPL_PATH . 'ajax/topic_edit_panel.tpl');
	}

	function saveTopicEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete_box'],'integer',0);

		if(!empty($id) && !empty($delete)) {
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			
			$return = "research/kb";
			
		} elseif(empty($id)) { // create
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => 0,
			);
			$id = DAO_KbCategory::create($fields);
			
			$return = "research/kb/" . sprintf("%04d", $id);
			
		} else { // update
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => 0,
			);
			DAO_KbCategory::update($id, $fields);
			
			$return = "research/kb/" . sprintf("%04d", $id);
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}	

	function showArticleEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('root_id', $root_id);
		
		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
			
			$article_categories = DAO_KbArticle::getCategoriesByArticleId($id);
			$tpl->assign('article_categories', $article_categories);
		}
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'ajax/article_edit_panel.tpl');
	}

	function saveArticleEditPanelAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string');
		@$category_ids = DevblocksPlatform::importGPC($_REQUEST['category_ids'],'array',array());
		@$content_raw = DevblocksPlatform::importGPC($_REQUEST['content_raw'],'string');
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'integer',0);
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_KbArticle::delete($id);
			
		} else { // Create|Modify
			// Sanitize
			if($format > 2 || $format < 0)
				$format = 0;
				
			if(empty($title))
				$title = '(' . $translate->_('kb_article.title') . ')';
			
			switch($format) {
				default:
				case 0: // plaintext
					$content_html = $content_raw;
					break;
				case 1: // HTML
					$content_html = $content_raw;
					break;
			}
				
			if(empty($id)) { // create
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT_RAW => $content_raw,
					DAO_KbArticle::CONTENT => $content_html,
					DAO_KbArticle::UPDATED => time(),
				);
				$id = DAO_KbArticle::create($fields);
				
			} else { // update
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT_RAW => $content_raw,
					DAO_KbArticle::CONTENT => $content_html,
					DAO_KbArticle::UPDATED => time(),
				);
				DAO_KbArticle::update($id, $fields);
				
			}
			
			DAO_KbArticle::setCategories($id, $category_ids, true);
		}
		
		// [TODO] Anything?
	}
	
	function doArticleQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $translate = DevblocksPlatform::getTranslationService();
		
        if(null == ($searchView = C4_AbstractViewLoader::getView('',ChKbResearchTab::VIEW_RESEARCH_KB_SEARCH))) {
        	$searchView = new C4_KbArticleView();
        	$searchView->id = ChKbResearchTab::VIEW_RESEARCH_KB_SEARCH;
        	$searchView->name = $translate->_('common.search_results');
        	C4_AbstractViewLoader::setView($searchView->id, $searchView);
        }
		
        $params = array();
        
        switch($type) {
            case "content":
            	$params[SearchFields_KbArticle::CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,$query);               
                break;
        }
        
        $searchView->params = $params;
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('research','kb','search')));
	}
	
	function showKbCategoryEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$category = DAO_KbCategory::get($id);
			$tpl->assign('category', $category);
		}
		
		/*
		 * [TODO] Remove the current category + descendents from the categories, 
		 * so the worker can't create a closed subtree (e.g. category's parent is its child)
		 */
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'ajax/subcategory_edit_panel.tpl');
	}
	
	function saveKbCategoryEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete_box'],'integer',0);

		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		if(!empty($id) && !empty($delete)) {
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			
			// Change $return to category parent
			$return = "research/kb/" . sprintf("%06d", $parent_id);
			
		} elseif(empty($id)) { // create
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::create($fields);
			
		} else { // update
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::update($id, $fields);
			
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}	
};

class DAO_KbArticle extends DevblocksORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const UPDATED = 'updated';
	const VIEWS = 'views';
	const FORMAT = 'format';
	const CONTENT = 'content';
	const CONTENT_RAW = 'content_raw';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('kb_seq');
		
		$sql = sprintf("INSERT INTO kb_article (id,title,views,updated,format,content,content_raw) ".
			"VALUES (%d,'',0,%d,0,'','')",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}

	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, title, views, updated, format, content, content_raw ".
			"FROM kb_article ".
			(!empty($where)?sprintf("WHERE %s ",$where):" ").
			"ORDER BY updated DESC "
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param ADORecordSet $rs
	 */
	static private function _createObjectsFromResultSet(ADORecordSet $rs=null) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_KbArticle();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->updated = $rs->fields['updated'];
			$object->views = $rs->fields['views'];
			$object->format = $rs->fields['format'];
			$object->content = $rs->fields['content'];
			$object->content_raw = $rs->fields['content_raw'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}

	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		parent::_update($ids, 'kb_article', $fields);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
//		// [TODO] Refactor out
//		// CloudGlue removes tags from nuked articles
//		$code_rs = $db->Execute(sprintf("SELECT DISTINCT code AS code FROM kb_article WHERE id IN (%s)", $id_string));
//		while(!$code_rs->EOF) {
//			$code = $code_rs->fields['code'];
//			DAO_CloudGlue::deleteContentIds(UmKbApp::TAG_INDEX_PREFIX.$code, $ids);
//			$code_rs->MoveNext();
//		}

		// Articles
		$db->Execute(sprintf("DELETE QUICK FROM kb_article WHERE id IN (%s)", $id_string));
		
		// Categories
		$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_article_id IN (%s)", $id_string));
	}

	static function getCategoriesByArticleId($article_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($article_id))
			return array();
		
		$categories = array();
		
		$rs = $db->Execute(sprintf("SELECT kb_category_id ". 
			"FROM kb_article_to_category ".
			"WHERE kb_article_id = %d",
			$article_id
		));
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$cat_id = intval($rs->fields['kb_category_id']);
			$categories[$cat_id] = $cat_id;
			$rs->MoveNext();
		}
		
		return $categories;
	}
	
	static function setCategories($article_id,$category_ids,$replace=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($category_ids))
			$category_ids = array($category_ids);
		
		if($replace) {
			$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_article_id = %d",
				$article_id
			));
		}
		
		$categories = DAO_KbCategory::getWhere();
		
		if(is_array($category_ids) && !empty($category_ids)) {
			foreach($category_ids as $category_id) {
				$pid = $category_id;
				while($pid) {
					$top_category_id = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				
				$db->Replace(
					'kb_article_to_category',
					array('kb_article_id'=>$article_id,'kb_category_id'=>$category_id,'kb_top_category_id'=>$top_category_id),
					array('kb_article_id','kb_category_id'),
					false
				);
			}
		}
		
		return TRUE;
	}
	
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$fields = SearchFields_KbArticle::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			unset($sortBy);

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), $fields);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"kb.id as %s, ".
			"kb.title as %s, ".
			"kb.updated as %s, ".
			"kb.views as %s, ".
			"kb.format as %s, ".
			"kb.content as %s ".
			"FROM kb_article kb ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_KbArticle::ID,
			    SearchFields_KbArticle::TITLE,
			    SearchFields_KbArticle::UPDATED,
			    SearchFields_KbArticle::VIEWS,
			    SearchFields_KbArticle::FORMAT,
			    SearchFields_KbArticle::CONTENT
			).
			
			// [JAS]: Dynamic table joins
			(isset($tables['katc']) ? "LEFT JOIN kb_article_to_category katc ON (kb.id=katc.kb_article_id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_KbArticle::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
};

class SearchFields_KbArticle implements IDevblocksSearchFields {
	// Table
	const ID = 'kb_id';
	const TITLE = 'kb_title';
	const UPDATED = 'kb_updated';
	const VIEWS = 'kb_views';
	const FORMAT = 'kb_format';
	const CONTENT = 'kb_content';
	
	const CATEGORY_ID = 'katc_category_id';
	const TOP_CATEGORY_ID = 'katc_top_category_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'kb', 'id', null, $translate->_('kb_article.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'kb', 'title', null, $translate->_('kb_article.title')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'kb', 'updated', null, $translate->_('kb_article.updated')),
			self::VIEWS => new DevblocksSearchField(self::VIEWS, 'kb', 'views', null, $translate->_('kb_article.views')),
			self::FORMAT => new DevblocksSearchField(self::FORMAT, 'kb', 'format', null, $translate->_('kb_article.format')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'kb', 'content', null, $translate->_('kb_article.content')),
			
			self::CATEGORY_ID => new DevblocksSearchField(self::CATEGORY_ID, 'katc', 'kb_category_id'),
			self::TOP_CATEGORY_ID => new DevblocksSearchField(self::TOP_CATEGORY_ID, 'katc', 'kb_top_category_id', null, $translate->_('kb_article.topic')),
		);
	}
};	

class DAO_KbCategory extends DevblocksORMHelper {
	const ID = 'id';
	const PARENT_ID = 'parent_id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO kb_category (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'kb_category', $fields);
	}
	
	static function getTreeMap() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$categories = self::getWhere();
		$tree = array();

		// Fake recursion
		foreach($categories as $cat_id => $cat) {
			$pid = $cat->parent_id;
			if(!isset($tree[$pid])) {
				$tree[$pid] = array();
			}
				
			$tree[$pid][$cat_id] = 0;
		}
		
		// Add counts (and bubble up)
		$sql = "SELECT count(*) AS hits, kb_category_id FROM kb_article_to_category GROUP BY kb_category_id";
		$rs = $db->Execute($sql);
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$count_cat = intval($rs->fields['kb_category_id']);
			$count_hits = intval($rs->fields['hits']);
			
			$pid = $count_cat;
			while($pid) {
				@$parent_id = $categories[$pid]->parent_id;
				$tree[$parent_id][$pid] += $count_hits;
				$pid = $parent_id;
			}
			
			$rs->MoveNext();
		}
		
		// [TODO] Filter out empty categories on public
		
		return $tree;
	}
	
	static function getTree($root=0) {
		$levels = array();
		$map = self::getTreeMap();
		
		self::_recurseTree($levels,$map,$root);
		
		return $levels;
	}
	
	// [TODO] Move to Model_KbCategoryTree?
	static private function _recurseTree(&$levels,$map,$node=0,$level=-1) {
		if(!isset($map[$node]) || empty($map[$node]))
			return;

		$level++; // we're dropping down a node

		// recurse through children
		foreach($map[$node] as $pid => $children) {
			$levels[$pid] = $level;
			self::_recurseTree($levels,$map,$pid,$level);
		}
	}
	
	static public function getDescendents($root_id) {
		$tree = self::getTree($root_id);
		@$ids = array_merge(array($root_id),array_keys($tree));
		return $ids;
	}
	
	/**
	 * @param string $where
	 * @return Model_KbCategory[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, parent_id, name ".
			"FROM kb_category ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_KbCategory	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_KbCategory[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_KbCategory();
			$object->id = $rs->fields['id'];
			$object->parent_id = $rs->fields['parent_id'];
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM kb_category WHERE id IN (%s)", $ids_list));

		$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_category_id IN (%s)", $ids_list));
		
		return true;
	}
};

class C4_KbArticleView extends C4_AbstractView {
	const DEFAULT_ID = 'kb_overview';
	
	private $_CORE_TPL_PATH = '';
	private $_TPL_PATH = '';

	function __construct() {
		$this->_CORE_TPL_PATH = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		
		$this->id = self::DEFAULT_ID;
		$this->name = 'Articles';
		$this->renderSortBy = 'kb_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_KbArticle::TITLE,
			SearchFields_KbArticle::UPDATED,
			SearchFields_KbArticle::VIEWS,
		);
	}

	function getData() {
		$objects = DAO_KbArticle::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . $this->_TPL_PATH . 'view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_KbArticle::TITLE:
				$tpl->display('file:' . $this->_CORE_TPL_PATH . 'internal/views/criteria/__string.tpl');
				break;
			case SearchFields_KbArticle::UPDATED:
				$tpl->display('file:' . $this->_CORE_TPL_PATH . 'internal/views/criteria/__date.tpl');
				break;
			case SearchFields_KbArticle::VIEWS:
				$tpl->display('file:' . $this->_CORE_TPL_PATH . 'internal/views/criteria/__number.tpl');
				break;
			case SearchFields_KbArticle::CONTENT:
				$tpl->display('file:' . $this->_CORE_TPL_PATH . 'internal/views/criteria/__fulltext.tpl');
				break;
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
					DAO_KbCategory::PARENT_ID,
					0
				));
				$tpl->assign('topics', $topics);

				$tpl->display('file:' . $this->_TPL_PATH . 'search/criteria/kb_topic.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
					DAO_KbCategory::PARENT_ID,
					0
				));
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($topics[$val]))
						continue;
						$strings[] = $topics[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_KbArticle::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_KbArticle::ID]);
		unset($fields[SearchFields_KbArticle::FORMAT]);
		return $fields;
	}

	static function getColumns() {
		return self::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_KbArticle::TITLE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_KbArticle::VIEWS:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::CONTENT:
				$criteria = new DevblocksSearchCriteria($field, DevblocksSearchCriteria::OPER_FULLTEXT, $value);
				break;
				
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				@$topic_ids = DevblocksPlatform::importGPC($_REQUEST['topic_id'], 'array', array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $topic_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

class Model_KbArticle {
	public $id = 0;
	public $title = '';
	public $views = 0;
	public $updated = 0;
	public $format = 0;
	public $content = '';
	public $content_raw = '';
};

class Model_KbCategory {
	public $id;
	public $parent_id;
	public $name;
};
