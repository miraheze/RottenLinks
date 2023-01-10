<?php

use MediaWiki\MediaWikiServices;

class RottenLinks {
	public static function getResponse( $url ) {
		$services = MediaWikiServices::getInstance();

		$config = $services->getConfigFactory()->makeConfig( 'rottenlinks' );

		// Make the protocol lowercase
		$urlexp = explode( '://', $url, 2 );
		$proto = strtolower( $urlexp[0] ) . '://';
		$site = $urlexp[1];
		$urlToUse = $proto . $site;

		// Cache the responses to avoid making multiple requests to the same URL
		$cache = ObjectCache::getLocalClusterInstance();
		$cacheKey = $cache->makeKey( 'RottenLinks', 'getResponse', md5( $url ) );
		$cachedResponse = $cache->get( $cacheKey );
		if ( $cachedResponse !== false ) {
			return $cachedResponse;
		}

		$status = self::getHttpStatus( $urlToUse, 'HEAD', $services, $config );
		// Some websites return 4xx or 5xx on HEAD requests but GET with the same URL gives a 200.
		if ( $status >= 400 ) {
			$status = self::getHttpStatus( $urlToUse, 'GET', $services, $config );
		}

		// Cache the response for one hour
		$cache->set( $cacheKey, $status, 3600 );

		return $status;
	}

	private static function getHttpStatus( $url, $method, $services, $config ) {
		$httpProxy = $config->get( 'RottenLinksHTTPProxy' );

		$userAgent = $config->get( 'RottenLinksUserAgent' ) ?:
			'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' . $config->get( 'Server' );

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
}
