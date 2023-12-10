<?php

class RottenLinksHooks {
	/**
	 * Handler for LinksUpdateComplete hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 * @param LinksUpdate $linksUpdate
	 * @param mixed $ticket
	 */
	public function onLinksUpdateComplete( $linksUpdate, $ticket ) {
		$addedExternalLinks = $linksUpdate->getAddedExternalLinks();
		$removedExternalLinks = $linksUpdate->getRemovedExternalLinks();

		if ( $addedExternalLinks || $removedExternalLinks ) {
			$params = [
				'addedExternalLinks' => $addedExternalLinks,
				'removedExternalLinks' => $removedExternalLinks
			];
			MediaWikiServices::getInstance()->getJobQueueGroup()->push(
				new RottenLinksJob( $params )
		}
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function onRottenLinksSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'rottenlinks',
			__DIR__ . '/../sql/rottenlinks.sql' );

		$updater->addExtensionField( 'rottenlinks', 'rl_id',
			__DIR__ . '/../sql/patches/patch-add-rl_id.sql' );

		$updater->addExtensionIndex( 'rottenlinks', 'rl_externallink',
			__DIR__ . '/../sql/patches/20210215.sql' );
	}
}
