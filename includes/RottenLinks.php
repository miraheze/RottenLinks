<?php

use MediaWiki\MediaWikiServices;

class RottenLinks {
	public static function getResponse( $url ) {
		$services = MediaWikiServices::getInstance();

		$config = $services->getConfigFactory()->makeConfig( 'rottenlinks' );

		// Make the protocol lowercase
		$urlexp = explode( '://', $url );
		$proto = strtolower( $urlexp[0] ) . '://';
		$site = $urlexp[1];
		$urlToUse = $proto . $site;

		$proxy = $config->get( 'RottenLinksHttpProxy' );
		$options = $proxy ? [ 'proxy' => $proxy ] : [];

		$request = $services->getHttpRequestFactory()->createMultiClient( $options )
			->run( [
				'url' => $urlToUse,
				'method' => 'HEAD',
				'headers' => [
					'user-agent' => 'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' . $config->get( 'Server' )
				]
			], [
				'reqTimeout' => $config->get( 'RottenLinksCurlTimeout' )
			]
		);

		return (int)$request['code'];
	}
}
