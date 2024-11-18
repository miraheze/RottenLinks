<?php

namespace Miraheze\RottenLinks;

use HttpStatus;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;
use Wikimedia\Rdbms\ILoadBalancer;

class SpecialRottenLinks extends SpecialPage {

	private Config $config;
	private ILoadBalancer $dbLoadBalancer;

	public function __construct(
		ConfigFactory $configFactory,
		ILoadBalancer $dbLoadBalancer
	) {
		parent::__construct( 'RottenLinks' );

		$this->config = $configFactory->makeConfig( 'RottenLinks' );
		$this->dbLoadBalancer = $dbLoadBalancer;
	}

	/**
	 * @param ?string $par
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();
		$this->addHelpLink( 'Extension:RottenLinks' );

		$showBad = $this->getRequest()->getBool( 'showBad' );
		$stats = $this->getRequest()->getBool( 'stats' );

		$pager = new RottenLinksPager( $this->getContext(), $this->config, $showBad );

		$formDescriptor = [
			'info' => [
				'type' => 'info',
				'default' => $this->msg( 'rottenlinks-header-info' )->text(),
			],
			'showBad' => [
				'type' => 'check',
				'name' => 'showBad',
				'label-message' => 'rottenlinks-showbad',
				'default' => $showBad
			],
			'statistics' => [
				'type' => 'check',
				'name' => 'stats',
				'label-message' => 'rottenlinks-stats',
				'default' => $stats
			],
			'limit' => [
				'type' => 'limitselect',
				'name' => 'limit',
				'label-message' => 'table_pager_limit_label',
				'default' => $pager->getLimit(),
				'options' => $pager->getLimitSelectList()
			]
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

	/**
	 * Display statistics related to RottenLinks.
	 *
	 * @return array Array with statistics information.
	 */
	private function showStatistics() {
		$dbr = $this->dbLoadBalancer->getMaintenanceConnectionRef( DB_REPLICA );

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
				'section' => 'statistics'
			];
		}

		return $statDescriptor;
	}

	/**
	 * Get the group name for the special page.
	 *
	 * @return string Group name.
	 */
	protected function getGroupName() {
		return 'maintenance';
	}
}
