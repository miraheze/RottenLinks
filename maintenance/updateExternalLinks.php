<?php

require_once( __DIR__ . '/../../../maintenance/Maintenance.php' );

class UpdateExternalLinks extends Maintenance {
	function __construct() {
		parent::__construct();
		$this->mDescription = "Updates rottenlinks database table based on externallinks table.";
	}

	function execute() {
		global $wgRottenLinksExcludeProtocols, $wgRottenLinksExcludeNamespaces;

		$time = time();

		$dbw = wfGetDB( DB_MASTER );

		$this->output( "Dropping all existing recorded entries\n" );

		$dbw->delete( 'rottenlinks',
			'*',
			__METHOD__
		);

		if ( $wgRottenLinksExcludeNamespaces ) {
			$res = $dbw->select(
				[
					'externallinks',
					'page'
				],
				[
					'el_from',
					'el_to',
				],
				'page_namespace NOT IN (' . implode( ',', $wgRottenLinksExcludeNamespaces ) . ')',
				__METHOD__,
				[
					'INNER JOIN' => [
						'externallinks.el_from' => 'page.page_id'
					]
				]
			);
		} else {
			$res = $dbw->select(
				'externallinks',
				[
					'el_from',
					'el_to'
				]
			);
		}

		$rottenlinksarray = [];

		foreach ( $res as $row ) {
			$rottenlinksarray[$row->el_to][] = (int)$row->el_from;
		}

		foreach ( $rottenlinksarray as $url => $pages ) {
			$urlexp = explode( ':', $url );
			if ( !in_array( $urlexp[0], $wgRottenLinksExcludeProtocols ) ) {
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
		}

		$time = time() - $time;

		$cache = ObjectCache::getLocalClusterInstance();
		$cache->set( $cache->makeKey( 'RottenLinks', 'lastRun' ), $dbw->timestamp() );
		$cache->set( $cache->makeKey( 'RottenLinks', 'runTime' ), $time );

		$this->output( 'Script took ' . $time . ' seconds.\n' );
	}
}

$maintClass = 'UpdateExternalLinks';
require_once( DO_MAINTENANCE );
