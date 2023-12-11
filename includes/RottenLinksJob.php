<?php

use MediaWiki\MediaWikiServices;

class RottenLinksJob extends Job implements GenericParameterJob {

	/** @var array */
	private $addedExternalLinks;

	/** @var array */
	private $removedExternalLinks;

	public function __construct( array $params ) {
		parent::__construct( 'RottenLinksJob', $params );

		$this->addedExternalLinks = $params['addedExternalLinks'] ?? [];
		$this->removedExternalLinks = $params['removedExternalLinks'] ?? [];
	}

	public function run() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'rottenlinks' );

		if ( $this->addedExternalLinks ) {
			$dbw = MediaWikiServices::getInstance()
				->getDBLoadBalancer()
				->getMaintenanceConnectionRef( DB_PRIMARY );

			foreach ( $this->addedExternalLinks as $url ) {

				$url = $this->decodeDomainName( $url );

				if ( substr( $url, 0, 2 ) === '//' ) {
					$url = 'https:' . $url;
				}

				$urlexp = explode( ':', $url );

				if ( isset( $urlexp[0] ) && in_array( strtolower( $urlexp[0] ), (array)$config->get( 'RottenLinksExcludeProtocols' ) ) ) {
					continue;
				}

				$mainSite = explode( '/', $urlexp[1] );

				if ( isset( $mainSite[2] ) && in_array( $mainSite[2], (array)$config->get( 'RottenLinksExcludeWebsites' ) ) ) {
					continue;
				}

				$rottenLinksCount = $dbw->selectRowCount( 'rottenlinks', 'rl_externallink', [ 'rl_externallink' => $url ], __METHOD__ );
				if ( $rottenLinksCount > 0 ) {
					// Don't create duplicate entires
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
				$externalLinksCount = $dbw->selectRowCount(
					'externallinks',
					'*',
					[
						'el_to_domain_index' => substr( $el[0][0], 0, 255 ),
						'el_to_path' => $el[0][1]
					]
				);
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
	 */
	private function decodeDomainName( $url ) {
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
