<?php

namespace Miraheze\RottenLinks\Specials;

use HttpStatus;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\RottenLinks\RottenLinksPager;
use Wikimedia\Rdbms\IConnectionProvider;

class SpecialRottenLinks extends SpecialPage {

	private IConnectionProvider $connectionProvider;

	public function __construct( IConnectionProvider $connectionProvider ) {
		parent::__construct( 'RottenLinks' );
		$this->connectionProvider = $connectionProvider;
	}

	/**
	 * @param ?string $par
	 */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();
		$this->addHelpLink( 'Extension:RottenLinks' );

		$showBad = $this->getRequest()->getBool( 'showBad' );
		$stats = $this->getRequest()->getBool( 'stats' );

		$pager = new RottenLinksPager(
			$this->getConfig(),
			$this->getContext(),
			$this->getLinkRenderer(),
			$showBad
		);

		$formDescriptor = [
			'info' => [
				'type' => 'info',
				'default' => $this->msg( 'rottenlinks-header-info' )->text(),
			],
			'showBad' => [
				'type' => 'check',
				'name' => 'showBad',
				'label-message' => 'rottenlinks-showbad',
				'default' => $showBad,
			],
			'statistics' => [
				'type' => 'check',
				'name' => 'stats',
				'label-message' => 'rottenlinks-stats',
				'default' => $stats,
			],
			'limit' => [
				'type' => 'limitselect',
				'name' => 'limit',
				'label-message' => 'table_pager_limit_label',
				'default' => $pager->getLimit(),
				'options' => $pager->getLimitSelectList(),
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setMethod( 'get' )
			->setWrapperLegendMsg( 'rottenlinks-header' )
			->setSubmitTextMsg( 'search' )
			->prepareForm()
			->displayForm( false );

		if ( $stats ) {
			$statForm = HTMLForm::factory( 'ooui', $this->showStatistics(), $this->getContext(), 'rottenlinks' );
			$statForm
				->setMethod( 'get' )
				->suppressDefaultSubmit()
				->prepareForm()
				->displayForm( false );
			return;
		}

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	private function showStatistics(): array {
		$dbr = $this->connectionProvider->getReplicaDatabase();

		$statusNumbers = $dbr->newSelectQueryBuilder()
			->select( 'rl_respcode' )
			->distinct()
			->from( 'rottenlinks' )
			->caller( __METHOD__ )
			->fetchResultSet();

		$statDescriptor = [];

		foreach ( $statusNumbers as $num ) {
			$respCode = $num->rl_respcode;
			$count = (string)$dbr->newSelectQueryBuilder()
				->select( 'rl_respcode' )
				->from( 'rottenlinks' )
				->where( [ 'rl_respcode' => $respCode ] )
				->caller( __METHOD__ )
				->fetchRowCount();

			$statDescriptor[$respCode] = [
				'type' => 'info',
				'label' => "HTTP: {$respCode} " .
					( $respCode != 0 ? HttpStatus::getMessage( $respCode ) : 'No Response' ),
				'default' => $count,
				'section' => 'statistics',
			];
		}

		return $statDescriptor;
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'maintenance';
	}
}
