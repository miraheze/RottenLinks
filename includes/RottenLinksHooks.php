<?php
class RottenLinksHooks {
	public static function fnRottenLinksSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'rottenlinks',
			__DIR__ . '/../sql/rottenlinks.sql' );
		/*
		$updater->modifyExtensionField( 'rottenlinks', 'rl_externallink',
 			__DIR__ . '/../sql/patches/patch-primary-key.sql');
		$updater->addExtensionField( 'rottenlinks', 'rl_id',
			__DIR__ . '/../sql/patches/patch-add-rl_id.sql');
		$updater->modifyExtensionField( 'rottenlinks', 'rl_id',
 			__DIR__ . '/../sql/patches/patch-modify-rl_id.sql');
		$updater->modifyExtensionField( 'rottenlinks', 'rl_externallink',
 			__DIR__ . '/../sql/patches/convert-to-blob-rl_externallink.sql');
		*/
	}
}
