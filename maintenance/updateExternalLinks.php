<?php

use MediaWiki\MediaWikiServices;

require_once( __DIR__ . '/../../../maintenance/Maintenance.php' );

class UpdateExternalLinks extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Updates rottenlinks database table based on externallinks table.";
	}

	public function execute() {
		$time = time();

		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'rottenlinks' );
		$dbw = wfGetDB( DB_MASTER );

		$this->output( "Dropping all existing recorded entries\n" );

		$dbw->delete( 'rottenlinks',
			'*',
			__METHOD__
		);

		$res = $dbw->select(
			'externallinks',
			[
				'el_from',
				'el_to'
			]
		);

		$rottenlinksarray = [];

		foreach ( $res as $row ) {
			$rottenlinksarray[$row->el_to][] = (int)$row->el_from;
		}

		foreach ( $rottenlinksarray as $url => $pages ) {
			if ( substr( $url, 0, 2 ) === '//' ) {
				$url = 'https:' . $url;
			}

			$urlexp = explode( ':', $url );

			if ( isset( $urlexp[0] ) && in_array( $urlexp[0], (array)$config->get( 'RottenLinksExcludeProtocols' ) ) ) {
				continue;
			}

			$mainSite = explode( '/', $urlexp[1] );

			if ( isset( $mainSite[2] ) && in_array( $mainSite[2], (array)$config->get( 'RottenLinksExcludeWebsites' ) ) ) {
				continue;
			}

			$resp = RottenLinks::getResponse( $url );
			$pagecount = count( $pages );

			$dbw->insert( 'rottenlinks',
				[
					'rl_externallink' => $url,
					'rl_respcode' => $resp,
					'rl_pageusage' => json_encode( $pages )
				],
				__METHOD__
			);

			$this->output( "Added externallink ($url) used on $pagecount with code $resp\n" );
		}

		$time = time() - $time;

		$cache = ObjectCache::getLocalClusterInstance();
		$cache->set( $cache->makeKey( 'RottenLinks', 'lastRun' ), $dbw->timestamp() );
		$cache->set( $cache->makeKey( 'RottenLinks', 'runTime' ), $time );

		$this->output( 'Script took ' . $time . ' seconds.\n' );
	}
}

$maintClass = 'UpdateExternalLinks';
require_once( RUN_MAINTENANCE_IF_MAIN );
