<?php

namespace Miraheze\RottenLinks\Maintenance;

use Maintenance;
use MediaWiki\ExternalLinks\LinkFilter;
use MediaWiki\MediaWikiServices;
use Miraheze\RottenLinks\RottenLinks;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class UpdateExternalLinks extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Updates rottenlinks database table based on externallinks table.' );

		$this->requireExtension( 'RottenLinks' );
	}

	public function execute() {
		$time = time();

		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'RottenLinks' );
		$dbw = $this->getDB( DB_PRIMARY );

		$this->output( "Dropping all existing recorded entries\n" );

		$dbw->delete( 'rottenlinks',
			'*',
			__METHOD__
		);

		$rottenlinksarray = [];

		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			$res = $dbw->newSelectQueryBuilder()
				->select( [
					'el_from',
					'el_to_domain_index',
					'el_to_path',
				] )
				->from( 'externallinks' )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $res as $row ) {
				// @phan-suppress-next-line PhanUndeclaredStaticMethod
				$elUrl = LinkFilter::reverseIndexes( $row->el_to_domain_index ) . $row->el_to_path;
				$rottenlinksarray[$elUrl][] = (int)$row->el_from;
			}
		} else {
			$res = $dbw->newSelectQueryBuilder()
				->select( [
					'el_from',
					'el_to',
				] )
				->from( 'externallinks' )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $res as $row ) {
				$rottenlinksarray[$row->el_to][] = (int)$row->el_from;
			}
		}

		$excludeProtocols = (array)$config->get( 'RottenLinksExcludeProtocols' );
		$excludeWebsites = (array)$config->get( 'RottenLinksExcludeWebsites' );

		foreach ( $rottenlinksarray as $url => $pages ) {
			$url = $this->decodeDomainName( $url );

			if ( substr( $url, 0, 2 ) === '//' ) {
				$url = 'https:' . $url;
			}

			$urlexp = explode( ':', $url );

			if ( isset( $urlexp[0] ) && in_array( strtolower( $urlexp[0] ), $excludeProtocols ) ) {
				continue;
			}

			$mainSite = explode( '/', $urlexp[1] );

			if ( isset( $mainSite[2] ) && in_array( $mainSite[2], $excludeWebsites ) ) {
				continue;
			}

			// This is to ensure duplicate links are not added,
			// since links are added after each edit that adds a url.
			$rottenLinksCount = $dbw->newSelectQueryBuilder()
				->select( 'rl_externallink' )
				->from( 'rottenlinks' )
				->where( [ 'rl_externallink' => $url ] )
				->caller( __METHOD__ )
				->fetchRowCount();

			if ( $rottenLinksCount > 0 ) {
				// Don't create duplicate entries
				continue;
			}

			$resp = RottenLinks::getResponse( $url );
			$pagecount = count( $pages );

			$dbw->insert( 'rottenlinks',
				[
					'rl_externallink' => $url,
					'rl_respcode' => $resp
				],
				__METHOD__
			);

			$this->output( "Added externallink ($url) used on $pagecount with code $resp\n" );
		}

		$time = time() - $time;

		$this->output( "Script took {$time} seconds.\n" );
	}

	/**
	 * Apparently, MediaWiki URL-encodes the whole URL, including the domain name,
	 * before storing it in the DB. This breaks non-ASCII domains.
	 * URL-decoding the domain part turns these URLs back into valid syntax.
	 *
	 * @param string $url The URL to decode.
	 *
	 * @return string The URL with the decoded domain name.
	 */
	private function decodeDomainName( string $url ): string {
		$urlexp = explode( '://', $url, 2 );
		if ( count( $urlexp ) === 2 ) {
			$locexp = explode( '/', $urlexp[1], 2 );
			$domain = urldecode( $locexp[0] );
			$url = $urlexp[0] . '://' . $domain;
			if ( count( $locexp ) === 2 ) {
				$url = $url . '/' . $locexp[1];
			}
		}

		return $url;
	}
}

$maintClass = UpdateExternalLinks::class;
require_once RUN_MAINTENANCE_IF_MAIN;
