<?php

namespace Miraheze\RottenLinks;

use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;
use WikiMedia\Rdbms\IReadableDatabase;

class RottenLinks {
	/**
	 * Get the HTTP response status code for a given URL.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return int The HTTP status code.
	 */
	public static function getResponse( string $url ) {
		$services = MediaWikiServices::getInstance();

		$config = $services->getConfigFactory()->makeConfig( 'RottenLinks' );

		// Make the protocol lowercase
		$urlexp = explode( '://', $url, 2 );
		$proto = strtolower( $urlexp[0] ) . '://';
		$site = $urlexp[1];
		$urlToUse = $proto . $site;

		$status = self::getHttpStatus( $urlToUse, 'HEAD', $services, $config );
		// Some websites return 4xx or 5xx on HEAD requests but GET with the same URL gives a 200.
		if ( $status >= 400 ) {
			$status = self::getHttpStatus( $urlToUse, 'GET', $services, $config );
		}

		return $status;
	}

	/**
	 * Get the HTTP status code for a given URL using a specified method.
	 *
	 * @param string $url The URL to check.
	 * @param string $method The HTTP method to use ('HEAD' or 'GET').
	 * @param MediaWikiServices $services MediaWiki service instance.
	 * @param Config $config Configuration instance.
	 *
	 * @return int The HTTP status code.
	 */
	private static function getHttpStatus(
		string $url,
		string $method,
		MediaWikiServices $services,
		Config $config
	) {
		$httpProxy = $config->get( 'RottenLinksHTTPProxy' );

		$userAgent = $config->get( 'RottenLinksUserAgent' ) ?:
			'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' .
				$config->get( 'Server' );

		$request = $services->getHttpRequestFactory()->createMultiClient( [ 'proxy' => $httpProxy ] )
			->run( [
				'url' => $url,
				'method' => $method,
				'headers' => [
					'user-agent' => $userAgent
				]
			], [
				'reqTimeout' => $config->get( 'RottenLinksCurlTimeout' )
			]
		);

		return (int)$request['code'];
	}

	/**
	 * Get the HTTP response status for a given URL from the database.
	 *
	 * @param IReadableDatabase $dbr
	 * @param string $url
	 *
	 * @return ?int null if the URL is not in the database, 0 if there was no response, or the response code
	 */
	public static function getResponseFromDatabase( IReadableDatabase $dbr, string $url ): ?int {
		$statusCode = $dbr->newSelectQueryBuilder()
			->select( 'rl_respcode' )
			->from( 'rottenlinks' )
			->where( [
				'rl_externallink' => $url,
			] )
			->caller( __METHOD__ )
			->fetchField();

		return $statusCode !== false
			? $statusCode
			: null;
	}
}
