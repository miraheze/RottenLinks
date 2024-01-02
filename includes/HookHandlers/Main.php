<?php

namespace Miraheze\RottenLinks\HookHandlers;

use JobQueueGroup;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use Miraheze\RottenLinks\RottenLinksJob;

class Main implements LinksUpdateCompleteHook {

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct( JobQueueGroup $jobQueueGroup ) {
		$this->jobQueueGroup = $jobQueueGroup;
	}

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

			$this->jobQueueGroup->push( new RottenLinksJob( $params ) );
		}
	}
}
