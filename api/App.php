<?php
class WgmGitHub_API {
	const OAUTH_ACCESS_TOKEN_URL = "https://github.com/login/oauth/access_token";
	const OAUTH_AUTHENTICATE_URL = "https://github.com/login/oauth/authorize";
	//const OAUTH_ACCESS_TOKEN_URL = "https://api.github.com/user?access_token=";
	
	static $_instance = null;
	private $_oauth = null;
	
	private function __construct() {
		$consumer_key = DevblocksPlatform::getPluginSetting('wgm.github','consumer_key','');
		$consumer_secret = DevblocksPlatform::getPluginSetting('wgm.github','consumer_secret','');
		$this->_oauth = new OAuth($consumer_key, $consumer_secret);
	}
	
	/**
	 * @return WgmGitHub_API
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmGitHub_API();
		}

		return self::$_instance;
	}
	
	public function setCredentials($token, $secret) {		
		$this->_oauth->setToken($token, $secret);
	}
	
	public function getAccessToken($consumer_key, $consumer_secret, $code, $redirect_uri=null) {
		return $this->_oauth->getAccessToken(sprintf("%s?client_id=%s&client_secret=%s&code=%s",
			self::OAUTH_ACCESS_TOKEN_URL,
			urlencode($consumer_key),
			urlencode($consumer_secret),
			urlencode($code)
		));
	}
	
// 	public function getRequestToken($callback_url) {
// 		return $this->_oauth->getRequestToken(self::OAUTH_REQUEST_TOKEN_URL, $callback_url);
// 	}
	
	public function post($url, $content) {
		$params = array(
			'status' => $content,		
		);
		
		return $this->_fetch($url, 'POST', $params);
	}
	
	public function get($url, $params=array()) {
		return $this->_fetch($url, 'GET', $params);
	}
	
	private function _fetch($url, $method = 'GET', $params = array()) {
		switch($method) {
			case 'POST':
				$method = OAUTH_HTTP_METHOD_POST;
				break;
				
			default:
				$method = OAUTH_HTTP_METHOD_GET;
				break;
		}

		$this->_oauth->fetch($url, $params, $method);
		
		//var_dump($this->_oauth->getLastResponseInfo());
		
		return $this->_oauth->getLastResponse();
	}
};

if(class_exists('Extension_PageMenuItem')):
class WgmGitHub_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmgithub.setup.menu.plugins.github';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.github::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmGitHub_SetupSection extends Extension_PageSection {
	const ID = 'wgmgithub.setup.github';
	
	function render() {
		// check whether extensions are loaded or not
		$extensions = array(
			'oauth' => extension_loaded('oauth')
		);
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'github');
		
		$params = array(
			'consumer_key' => DevblocksPlatform::getPluginSetting('wgm.github','consumer_key',''),
			'consumer_secret' => DevblocksPlatform::getPluginSetting('wgm.github','consumer_secret',''),
			'users' => json_decode(DevblocksPlatform::getPluginSetting('wgm.github', 'users', ''), TRUE),
		);
		$tpl->assign('params', $params);
		$tpl->assign('extensions', $extensions);
		
		$tpl->display('devblocks:wgm.github::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$consumer_key = DevblocksPlatform::importGPC($_REQUEST['consumer_key'],'string','');
			@$consumer_secret = DevblocksPlatform::importGPC($_REQUEST['consumer_secret'],'string','');
			
			if(empty($consumer_key) || empty($consumer_secret))
				throw new Exception("Both the API Auth Token and URL are required.");
			
			DevblocksPlatform::setPluginSetting('wgm.github','consumer_key',$consumer_key);
			DevblocksPlatform::setPluginSetting('wgm.github','consumer_secret',$consumer_secret);
			
		    echo json_encode(array('status'=>true,'message'=>'Saved!'));
		    return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
	
	function testRepoAction() {
		$github = WgmGitHub_API::getInstance();

		$users = json_decode(DevblocksPlatform::getPluginSetting('wgm.github', 'users', ''), true);
		$user = array_shift($users);
		
		$out = $github->get(
			sprintf("https://api.github.com/repos/%s/%s",
				urlencode('cerb-plugins'),
				urlencode('wgm.freshbooks')
			),
			array(
				'access_token' => $user['access_token'],
			)
		);
		
		if(false !== ($json = @json_decode($out, true))) {
			$fields = array(
				DAO_GitHubRepository::GITHUB_ID => $json['id'],
				DAO_GitHubRepository::NAME => $json['name'],
				DAO_GitHubRepository::CREATED_AT => strtotime($json['created_at']),
				DAO_GitHubRepository::BRANCH => @$json['master_branch'],
				DAO_GitHubRepository::DESCRIPTION => $json['description'],
				DAO_GitHubRepository::GITHUB_FORKS => $json['forks'],
				DAO_GitHubRepository::GITHUB_WATCHERS => $json['watchers'],
				DAO_GitHubRepository::PUSHED_AT => strtotime($json['pushed_at']),
				DAO_GitHubRepository::UPDATED_AT=> strtotime($json['updated_at']),
				DAO_GitHubRepository::URL => $json['html_url'],
				DAO_GitHubRepository::OWNER_GITHUB_ID => $json['owner']['id'],
				DAO_GitHubRepository::OWNER_GITHUB_NAME => $json['owner']['login'],
				DAO_GitHubRepository::SYNCED_AT => time(),
			);
			
			$matches = DAO_GitHubRepository::getWhere(
				sprintf("%s = %s",
					DAO_GitHubRepository::GITHUB_ID,
					DAO_GitHubRepository::qstr($json['id'])
				)
			);
			
			if(!empty($matches)) {
				DAO_GitHubRepository::update(key($matches), $fields);
				
			} else {
				DAO_GitHubRepository::create($fields);
			}
		}
	}
	
	function updateReposAction() {
		$github = WgmGitHub_API::getInstance();

		$users = json_decode(DevblocksPlatform::getPluginSetting('wgm.github', 'users', ''), true);
		$user = array_shift($users);
		
		$owners = DAO_GitHubRepository::getDistinctOwners();
		$repositories = DAO_GitHubRepository::getWhere();
		
		foreach($owners as $owner) {
			$out = $github->get(
				sprintf("https://api.github.com/users/%s/repos",
					$owner
				),
				array(
					'access_token' => $user['access_token'],
					'type' => 'owner',
				)
			);
			
			if(false !== ($json = @json_decode($out, true))) {
				foreach($json as $repo_json) {
					if(!isset($repo_json['id'])) {
						continue;
					}
					
					$fields = array(
						DAO_GitHubRepository::GITHUB_ID => $repo_json['id'],
						DAO_GitHubRepository::NAME => $repo_json['name'],
						DAO_GitHubRepository::CREATED_AT => strtotime($repo_json['created_at']),
						DAO_GitHubRepository::BRANCH => @$repo_json['master_branch'],
						DAO_GitHubRepository::DESCRIPTION => $repo_json['description'],
						DAO_GitHubRepository::GITHUB_FORKS => $repo_json['forks'],
						DAO_GitHubRepository::GITHUB_WATCHERS => $repo_json['watchers'],
						DAO_GitHubRepository::PUSHED_AT => strtotime($repo_json['pushed_at']),
						DAO_GitHubRepository::UPDATED_AT => strtotime($repo_json['updated_at']),
						DAO_GitHubRepository::SYNCED_AT => time(),
						DAO_GitHubRepository::URL => $repo_json['html_url'],
						DAO_GitHubRepository::OWNER_GITHUB_ID => $repo_json['owner']['id'],
						DAO_GitHubRepository::OWNER_GITHUB_NAME => $repo_json['owner']['login'],
					);
					
					$is_local = false;
					
					foreach($repositories as $repo) { /* @var $repo Model_GitHubRepository */
						if($repo->github_id == $fields[DAO_GitHubRepository::GITHUB_ID]) {
							$is_local = intval($repo->id);
							break;
						}
					}
						
					if($is_local) {
						DAO_GitHubRepository::update($is_local, $fields);
						
						// [TODO] Detect closed issues if existing and not in open issues
						
						// Check issues
						if($repo_json['has_issues'] == 'true') {
							$out_issues = $github->get(
								sprintf("https://api.github.com/repos/%s/%s/issues",
									urlencode($owner),
									urlencode($repo_json['name'])
								),
								array(
									'access_token' => $user['access_token'],
									'state' => 'open',
									'sort' => 'updated',
									'direction' => 'desc',
									'since' => gmdate('c', $repo->synced_at),
								)
							);
							
							if(false !== ($json_issues = @json_decode($out_issues, true))) {
								foreach($json_issues as $json_issue) {
									$fields = array(
										DAO_GitHubIssue::GITHUB_ID => $json_issue['id'],
										DAO_GitHubIssue::GITHUB_NUMBER => $json_issue['number'],
										DAO_GitHubIssue::GITHUB_REPOSITORY_ID => $is_local,
										DAO_GitHubIssue::TITLE => $json_issue['title'],
										DAO_GitHubIssue::IS_CLOSED => (0 == strcasecmp($json_issue['state'],'open') ? 0 : 1),
										DAO_GitHubIssue::REPORTER_NAME => $json_issue['user']['login'],
										DAO_GitHubIssue::REPORTER_GITHUB_ID => $json_issue['user']['id'],
										DAO_GitHubIssue::MILESTONE => $json_issue['milestone']['title'],
										DAO_GitHubIssue::CREATED_AT => strtotime($json_issue['created_at']),
										DAO_GitHubIssue::UPDATED_AT => strtotime($json_issue['updated_at']),
										DAO_GitHubIssue::CLOSED_AT => strtotime($json_issue['closed_at']) ?: 0,
										DAO_GitHubIssue::SYNCED_AT => time(),
									);
									
									$results = DAO_GitHubIssue::getWhere(sprintf("%s = %d", DAO_GitHubIssue::GITHUB_ID, $json_issue['id']));
									
									// Check if new or update
									if(empty($results)) {
										$issue_id = DAO_GitHubIssue::create($fields);
										
									} else {
										DAO_GitHubIssue::update(array_keys($results), $fields);
										
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	function authAction() {
		@$callback = DevblocksPlatform::importGPC($_REQUEST['_callback'], 'bool', 0);

		$github = WgmGitHub_API::getInstance();
		
		$url_writer = DevblocksPlatform::getUrlService();
		
		$consumer_key = DevblocksPlatform::getPluginSetting('wgm.github','consumer_key','');
		$consumer_secret = DevblocksPlatform::getPluginSetting('wgm.github','consumer_secret','');
		
		if($callback) {
			if(isset($_REQUEST['code'])) {
				$code = DevblocksPlatform::importGPC($_REQUEST['code'],'string','');
				
				$token = $github->getAccessToken($consumer_key, $consumer_secret, $code);

				if(isset($token['access_token'])) {
					$out = $github->get(sprintf("https://api.github.com/user?access_token=%s", $token['access_token']));
					
					if(false !== ($json = @json_decode($out, true))) {
						$json['access_token'] = $token['access_token'];
						
						//$users = json_decode(DevblocksPlatform::getPluginSetting('wgm.github', 'users', ''), true);
						
						$users = array(
							$json['id'] => $json 
						);
					}
				}
				
				DevblocksPlatform::setPluginSetting('wgm.github', 'users', json_encode($users));
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','github')));
			}
		} else {
			try {
				$oauth_callback_url = $url_writer->write('ajax.php?c=config&a=handleSectionAction&section=github&action=auth&_callback=true', true);
				
				header('Location: ' . sprintf("%s?client_id=%s&redirect_uri=%s",
					WgmGitHub_API::OAUTH_AUTHENTICATE_URL,
					$consumer_key,
					urlencode($oauth_callback_url)
				));
				exit;
				
			} catch(OAuthException $e) {
				echo "Exception: " . $e->getMessage();
			}
		}
	}
	
};
endif;