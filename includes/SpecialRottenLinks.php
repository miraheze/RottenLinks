<?php
class SpecialRottenLinks extends SpecialPage {
	function __construct() {
		parent::__construct( 'RottenLinks' );
	}

	function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$showBad = $this->getRequest()->getText( 'showBad' );
		$stats = $this->getRequest()->getText( 'stats' );
		$limit = $this->getRequest()->getText( 'limit' );

		$formDescriptor = [
			'showBad' => [
				'type' => 'check',
				'name' => 'showBad',
				'label-message' => 'rottenlinks-showbad',
				'default' => ( $showBad ) ? $showBad : false
			],
			'statistics' => [
				'type' => 'check',
				'name' => 'stats',
				'label-message' => 'rottenlinks-stats',
				'default' => ( $stats ) ? $stats : false
			],
			'limit' => [
				'type' => 'select',
				'name' => 'limit',
				'label-message' => 'table_pager_limit_label',
				'default' => ( $limit ) ? $limit : 25,
				'options' => [
					'25' => 25,
					'50' => 50,
					'100' => 100,
					'250' => 250,
					'500' => 500,
				],
			]
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'dummyProcess' ] )->setMethod( 'get' )->prepareForm()->show();

		if ( $stats ) {
			$statForm = HTMLForm::factory( 'ooui', $this->showStatistics( $this->getContext() ), $this->getContext(), 'rottenlinks' );
			$statForm->setSubmitCallback( [ $this, 'dummyProcess' ] )->setMethod( 'get' )->suppressDefaultSubmit()->prepareForm()->show();
			return;
		}

		$pager = new RottenLinksPager( $showBad, $limit );
		$table = $pager->getBody();

		$this->getOutput()->addHTML( $pager->getNavigationBar() . $table . $pager->getNavigationBar() );
	}

	static function dummyProcess( $formData ) {
		return false;
	}

	static function showStatistics( IContextSource $context ) {
		$dbr = wfGetDB( DB_REPLICA );

		$statusNumbers = $dbr->select(
			'rottenlinks',
			'rl_respcode',
			[],
			__METHOD__,
			'DISTINCT'
		);

		$cache = ObjectCache::getLocalClusterInstance();

		$statDescriptor = [
			'runTime' => [
				'type' => 'info',
				'label-message' => 'rottenlinks-runtime',
				'default' => $cache->get( $cache->makeKey( 'RottenLinks', 'runTime' ) ) . ' seconds',
				'section' => 'metadata'
			],
			'runDate' => [
				'type' => 'info',
				'label-message' => 'rottenlinks-rundate',
				'default' => $context->getLanguage()->timeanddate( $cache->get( $cache->makeKey( 'RottenLinks', 'lastRan' ) ) ),
				'section' => 'metadata'
			]
		];

		foreach ( $statusNumbers as $num ) {
			$respCode = $num->rl_respcode;

			$count = (string)$dbr->selectRowCount(
				'rottenlinks',
				'rl_respcode',
				[
					'rl_respcode' => $respCode
				],
				__METHOD__
			);

			$statDescriptor[$respCode] = [
				'type' => 'info',
				'label' => ( $respCode != 0 ) ? HttpStatus::getMessage( $respCode ) : 'No Response',
				'default' => $count,
				'section' => 'statistics'
			];
		}

		return $statDescriptor;
	}

	protected function getGroupName() {
		return 'maintenance';
	}
}