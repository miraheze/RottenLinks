<?php

namespace Miraheze\RottenLinks;

use GenericParameterJob;
use Job;
use MediaWiki\ExternalLinks\LinkFilter;
use MediaWiki\MediaWikiServices;

class RottenLinksJob extends Job implements GenericParameterJob {

	/** @var array */
	private $addedExternalLinks;

	/** @var array */
	private $removedExternalLinks;

	/**
	 * @param array $params Job parameters.
	 */
	public function __construct( array $params ) {
		parent::__construct( 'RottenLinksJob', $params );

		$this->addedExternalLinks = $params['addedExternalLinks'] ?? [];
		$this->removedExternalLinks = $params['removedExternalLinks'] ?? [];
	}

	/**
	 * Execute the job, updating the 'rottenlinks' table based on added and removed external links.
	 *
	 * @return bool True on success.
	 */
	public function run() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'RottenLinks' );

		if ( $this->addedExternalLinks ) {
			$dbw = MediaWikiServices::getInstance()
				->getDBLoadBalancer()
				->getMaintenanceConnectionRef( DB_PRIMARY );

			$excludeProtocols = (array)$config->get( 'RottenLinksExcludeProtocols' );
			$excludeWebsites = (array)$config->get( 'RottenLinksExcludeWebsites' );

			foreach ( $this->addedExternalLinks as $url ) {
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

				$dbw->insert( 'rottenlinks',
					[
						'rl_externallink' => $url,
						'rl_respcode' => $resp
					],
					__METHOD__
				);
			}
		}

		if ( $this->removedExternalLinks ) {
			$dbw = MediaWikiServices::getInstance()
				->getDBLoadBalancer()
				->getMaintenanceConnectionRef( DB_PRIMARY );

			foreach ( $this->removedExternalLinks as $url ) {
				$url = $this->decodeDomainName( $url );

				if ( substr( $url, 0, 2 ) === '//' ) {
					$url = 'https:' . $url;
				}

				$el = LinkFilter::makeIndexes( $url );
				$externalLinksCount = $dbw->newSelectQueryBuilder()
					->select( '*' )
					->from( 'externallinks' )
					->where( [
						'el_to_domain_index' => substr( $el[0][0], 0, 255 ),
						'el_to_path' => $el[0][1]
					] )
					->caller( __METHOD__ )
					->fetchRowCount();

				if ( $externalLinksCount > 0 ) {
					// Don't delete if the link exists on other pages.
					continue;
				}

				$dbw->delete( 'rottenlinks', [ 'rl_externallink' => $url ], __METHOD__ );
			}
		}

		return true;
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
