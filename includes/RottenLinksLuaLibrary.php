<?php

namespace Miraheze\RottenLinks;

use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaError;
use Wikimedia\Rdbms\ILoadBalancer;

class RottenLinksLuaLibrary extends LibraryBase {

	private ILoadBalancer $loadBalancer;

	/**
	 * @param LuaEngine $engine
	 */
	public function __construct( $engine ) {
		parent::__construct( $engine );
		// Unfortunately, Scribunto currently does not offer us any options to do
		// dependency injection, so we have to pretend that we do. Luckily, there
		// is already an upstream task: https://phabricator.wikimedia.org/T375835
		$this->loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
	}

	/**
	 * @param ?string $url
	 * @return array
	 * @internal
	 */
	public function onGetStatus( ?string $url = null ) {
		$name = 'mw.ext.rottenLinks.getStatus';
		$this->checkType( $name, 1, $url, 'string' );
		// I think Lua errors are untranslated? LibraryBase::checkType() returns
		// a plain ol' English string too.
		if ( $url === '' ) {
			throw new LuaError( "bad argument #1 to '{$name}' (url is empty)" );
		}

		$dbr = $this->loadBalancer->getMaintenanceConnectionRef( DB_REPLICA );
		return [ RottenLinks::getResponseFromDatabase( $dbr, $url ) ];
	}

	/**
	 * @return array
	 */
	public function register() {
		$functions = [
			'getStatus' => [ $this, 'onGetStatus' ],
		];
		$arguments = [];

		return $this->getEngine()->registerInterface( __DIR__ . '/mw.ext.rottenLinks.lua', $functions, $arguments );
	}

}
