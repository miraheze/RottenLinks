<?php

namespace Miraheze\CreateWiki;

use GenericParameterJob;
use Job;
use MediaWiki\MediaWikiServices;

class RottenLinksJob0 extends Job implements GenericParameterJob {

	/** @var array */
	private $addedExternalLinks;

	/** @var array */
	private $removedExternalLinks;

	public function __construct( array $params ) {
		parent::__construct( 'RottenLinksJob', $params );

		$this->addedExternalLinks = $params['addedExternalLinks'] ?? [];
		$this->removedExternalLinks $params['removedExternalLinks'] ?? [];
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
			}
		}

		if ( $this->removedExternalLinks ) {
		}

		return true;
	}
}
