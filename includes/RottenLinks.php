<?php
class RottenLinks {
	public static function getResponse( $url ) {
		global $wgServer, $wgRottenLinksCurlTimeout;

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_USERAGENT, "RottenLink, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on $wgServer" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $wgRottenLinksCurlTimeout );
		curl_exec( $ch );
		$result = curl_getinfo( $ch );
		curl_close( $ch );

		return (int)$result['http_code'];
	}
}
