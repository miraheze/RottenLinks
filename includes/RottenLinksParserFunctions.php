<?php

namespace Miraheze\RottenLinks;

use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use PPFrame;
use PPNode;
use Wikimedia\Rdbms\ILoadBalancer;

class RottenLinksParserFunctions {

	private ILoadBalancer $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * The function responsible for handling {{#rl_status}}.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function onRLStatus( Parser $parser, PPFrame $frame, array $args ): string {
		$url = trim( $frame->expand( $args[0] ?? '' ) );
		if ( $url === '' ) {
			return Html::element( 'strong', [
				'class' => 'error',
			], $parser->msg( 'rottenlinks-rlstatus-no-url' ) );
		}

		$dbr = $this->loadBalancer->getMaintenanceConnectionRef( DB_REPLICA );
		return (string)RottenLinks::getResponseFromDatabase( $dbr, $url );
	}

}
