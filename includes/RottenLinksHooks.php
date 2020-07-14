<?php
class RottenLinksHooks {
	public static function fnRottenLinksSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'rottenlinks',
			__DIR__ . '/../sql/rottenlinks.sql' );
	}
}
