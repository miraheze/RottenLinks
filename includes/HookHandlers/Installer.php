<?php

namespace Miraheze\RottenLinks\HookHandlers;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class Installer implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__ . '/../../sql';

		$updater->addExtensionTable( 'rottenlinks', "$dir/rottenlinks.sql" );

		$updater->addExtensionField( 'rottenlinks', 'rl_id', "$dir/patches/patch-add-rl_id.sql" );

		$updater->addExtensionIndex( 'rottenlinks', 'rl_externallink', "$dir/patches/20210215.sql" );

		$updater->dropExtensionField( 'rottenlinks', 'rl_pageusage', "$dir/patches/patch-drop-rl_pageusage.sql" );
	}
}
