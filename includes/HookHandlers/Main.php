<?php

namespace Miraheze\RottenLinks\HookHandlers;

use JobQueueGroup;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use Miraheze\RottenLinks\RottenLinksJob;
use Miraheze\RottenLinks\RottenLinksParserFunctions;
use Wikimedia\Rdbms\IConnectionProvider;

class Main implements LinksUpdateCompleteHook, ParserFirstCallInitHook {

	private JobQueueGroup $jobQueueGroup;
	private RottenLinksParserFunctions $parserFunctions;

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 * @param IConnectionProvider $connectionProvider
	 */
	public function __construct( JobQueueGroup $jobQueueGroup, IConnectionProvider $connectionProvider ) {
		$this->jobQueueGroup = $jobQueueGroup;
		$this->parserFunctions = new RottenLinksParserFunctions( $connectionProvider );
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

	/**
	 * Handler for ParserFirstCallInit hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'rl_status', [ $this->parserFunctions, 'onRLStatus' ], Parser::SFH_OBJECT_ARGS );
	}
}
