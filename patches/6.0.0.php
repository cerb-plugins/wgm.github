<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// github_repository 

if(!isset($tables['github_repository'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS github_repository (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			github_id INT UNSIGNED NOT NULL DEFAULT 0,
			github_watchers INT UNSIGNED NOT NULL DEFAULT 0,
			github_forks INT UNSIGNED NOT NULL DEFAULT 0,
			owner_github_id INT UNSIGNED NOT NULL DEFAULT 0,
			owner_github_name VARCHAR(255) NOT NULL DEFAULT '',
			name VARCHAR(255) NOT NULL DEFAULT '',
			description VARCHAR(255) NOT NULL DEFAULT '',
			branch VARCHAR(255) NOT NULL DEFAULT '',
			url VARCHAR(255) NOT NULL DEFAULT '',
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			pushed_at INT UNSIGNED NOT NULL DEFAULT 0,
			synced_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX updated_at (updated_at)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['github_repository'] = 'github_repository';
}

// ===========================================================================
// github_issue

if(!isset($tables['github_issue'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS github_issue (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			github_id INT UNSIGNED NOT NULL DEFAULT 0,
			github_number MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
			github_repository_id INT UNSIGNED NOT NULL DEFAULT 0,
			title VARCHAR(255) NOT NULL DEFAULT '',
			is_closed TINYINT UNSIGNED NOT NULL DEFAULT 0,
			reporter_name VARCHAR(255) NOT NULL DEFAULT '',
			reporter_github_id INT UNSIGNED NOT NULL DEFAULT 0,
			milestone VARCHAR(255) NOT NULL DEFAULT '',
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			closed_at INT UNSIGNED NOT NULL DEFAULT 0,
			synced_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX github_repository_id (github_repository_id),
			INDEX is_closed (is_closed),
			INDEX updated_at (updated_at)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['github_issue'] = 'github_issue';
}

// ===========================================================================
// Enable scheduled task and give defaults

/*
if(null != ($cron = DevblocksPlatform::getExtension('wgm.github.cron', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '15');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 23:45'));
}
*/

return TRUE;