<?php

namespace Miraheze\RottenLinks\Jobs;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\ExternalLinks\LinkFilter;
use MediaWiki\JobQueue\Job;
use Miraheze\RottenLinks\RottenLinks;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class RottenLinksJob extends Job {

	public const JOB_NAME = 'RottenLinksJob';

	private Config $config;
	private IConnectionProvider $connectionProvider;

	private array $addedExternalLinks;
	private array $removedExternalLinks;

	public function __construct(
		array $params,
		ConfigFactory $configFactory,
		IConnectionProvider $connectionProvider
	) {
		parent::__construct( self::JOB_NAME, $params );

		$this->addedExternalLinks = $params['addedExternalLinks'];
		$this->removedExternalLinks = $params['removedExternalLinks'];

		$this->config = $configFactory->makeConfig( 'RottenLinks' );
		$this->connectionProvider = $connectionProvider;
	}

	public function run(): bool {
		$dbr = $this->connectionProvider->getReplicaDatabase();
		$dbw = $this->connectionProvider->getPrimaryDatabase();

		if ( $this->addedExternalLinks ) {
			$excludeProtocols = (array)$this->config->get( 'RottenLinksExcludeProtocols' );
			$excludeWebsites = (array)$this->config->get( 'RottenLinksExcludeWebsites' );

			foreach ( $this->addedExternalLinks as $url ) {
				$url = $this->decodeDomainName( $url );

				if ( substr( $url, 0, 2 ) === '//' ) {
					$url = 'https:' . $url;
				}

				$urlexp = explode( ':', $url );

				if ( isset( $urlexp[0] ) && in_array( strtolower( $urlexp[0] ), $excludeProtocols, true ) ) {
					continue;
				}

				$mainSite = explode( '/', $urlexp[1] );

				if ( isset( $mainSite[2] ) && in_array( $mainSite[2], $excludeWebsites, true ) ) {
					continue;
				}

				$rottenLinksCount = $dbr->newSelectQueryBuilder()
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

				$dbw->newInsertQueryBuilder()
					->insertInto( 'rottenlinks' )
					->row( [
						'rl_externallink' => $url,
						'rl_respcode' => $resp,
					] )
					->caller( __METHOD__ )
					->execute();
			}
		}

		if ( $this->removedExternalLinks ) {
			foreach ( $this->removedExternalLinks as $url ) {
				$url = $this->decodeDomainName( $url );

				if ( substr( $url, 0, 2 ) === '//' ) {
					$url = 'https:' . $url;
				}

				$el = LinkFilter::makeIndexes( $url );
				$externalLinksCount = $dbr->newSelectQueryBuilder()
					->select( ISQLPlatform::ALL_ROWS )
					->from( 'externallinks' )
					->where( [
						'el_to_domain_index' => substr( $el[0][0], 0, 255 ),
						'el_to_path' => $el[0][1],
					] )
					->caller( __METHOD__ )
					->fetchRowCount();

				if ( $externalLinksCount > 0 ) {
					// Don't delete if the link exists on other pages.
					continue;
				}

				$dbw->newDeleteQueryBuilder()
					->deleteFrom( 'rottenlinks' )
					->where( [ 'rl_externallink' => $url ] )
					->caller( __METHOD__ )
					->execute();
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
