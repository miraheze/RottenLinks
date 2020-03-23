<?php

use MediaWiki\MediaWikiServices;

class RottenLinks {
	public static function getResponse( $url ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'rottenlinks' );

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' . $config->get( 'Server' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $config->get( 'RottenLinksCurlTimeout' ) );
		curl_exec( $ch );
		$result = curl_getinfo( $ch );
		curl_close( $ch );

		return (int)$result['http_code'];
	}
}
