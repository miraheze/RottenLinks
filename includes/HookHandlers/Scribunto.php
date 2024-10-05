<?php

namespace Miraheze\RottenLinks\HookHandlers;

use Miraheze\RottenLinks\RottenLinksLuaLibrary;
use MediaWiki\Extension\Scribunto\Hooks\ScribuntoExternalLibrariesHook;

class Scribunto implements ScribuntoExternalLibrariesHook {

	/**
	 * Handler for ScribuntoExternalLibraries hook.
	 * @param string $engine
	 * @param array &$externalLibraries
	 * @return bool
	 */
	public function onScribuntoExternalLibraries( $engine, &$externalLibraries ) {
		if ( $engine === 'lua' ) {
			$externalLibraries['mw.ext.rottenLinks'] = RottenLinksLuaLibrary::class;
		}

		return true;
	}

}
