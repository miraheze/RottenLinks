<?php

use MediaWiki\MediaWikiServices;

class RottenLinks {
	private $config = null;

	public function __construct() {
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'rottenlinks' );
	}

	public static function getResponse( $url ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' . $this->config->get( 'Server' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->config->get( 'RottenLinksCurlTimeout' ) );
		curl_exec( $ch );
		$result = curl_getinfo( $ch );
		curl_close( $ch );

		return (int)$result['http_code'];
	}
}
