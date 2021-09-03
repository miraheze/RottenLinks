<?php

class RottenLinksHooks {
	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function fnRottenLinksSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'rottenlinks',
			__DIR__ . '/../sql/rottenlinks.sql' );

		$updater->addExtensionField( 'rottenlinks', 'rl_id',
			__DIR__ . '/../sql/patches/patch-add-rl_id.sql' );

		$updater->addExtensionIndex( 'rottenlinks', 'rl_externallink',
			__DIR__ . '/../sql/patches/20210215.sql' );
	}
}
