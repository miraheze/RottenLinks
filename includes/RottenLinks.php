<?php

use MediaWiki\MediaWikiServices;

class RottenLinks {
	public static function getResponse( $url ) {
		$services = MediaWikiServices::getInstance();

		$config = $services->getConfigFactory()->makeConfig( 'rottenlinks' );

		$request = $services->getHttpRequestFactory()->create(
			$url, [ 
				'method' => 'HEAD', // return headers only
				'timeout' => $config->get( 'RottenLinksCurlTimeout' ),
				'userAgent' => 'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' . $config->get( 'Server' )
			],
			__METHOD__
		)->execute();

		return (int)$request->getStatusValue()->getValue();
	}
}
