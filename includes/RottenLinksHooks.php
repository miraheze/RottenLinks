<?php
class RottenLinksHooks {
	public static function fnRottenLinksSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'rottenlinks',
			__DIR__ . '/../sql/rottenlinks.sql' );
		$updater->modifyExtensionField( 'rottenlinks', 'rl_externallink',
			__DIR__ . '/../sql/patches/patch-primary-key.sql');
	}
}
