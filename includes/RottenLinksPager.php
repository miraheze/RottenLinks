<?php

use MediaWiki\MediaWikiServices;

class RottenLinksPager extends TablePager {
	/** @var Config */
	private $config;

	/** @var bool */
	private $showBad;

	public function __construct( $page, $showBad ) {
		parent::__construct( $page->getContext() );
		$this->showBad = $showBad;
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'rottenlinks' );
	}

	public function getFieldNames() {
		static $headers = null;

		$headers = [
			'rl_externallink' => 'rottenlinks-table-external',
			'rl_respcode' => 'rottenlinks-table-response',
			'rl_pageusage' => 'rottenlinks-table-usage',
		];

		foreach ( $headers as &$msg ) {
			$msg = $this->msg( $msg )->text();
		}

		return $headers;
	}

	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'rl_externallink':
				$formatted = Linker::makeExternalLink( (string)$row->rl_externallink, ( substr( (string)$row->rl_externallink, 0, 50 ) . '...' ), true, '', [ 'target' => $this->config->get( 'RottenLinksExternalLinkTarget' ) ] );
				break;
			case 'rl_respcode':
				$respCode = (int)$row->rl_respcode;
				$colour = ( in_array( $respCode, $this->config->get( 'RottenLinksBadCodes' ) ) ) ? "#8B0000" : "#008000";
				$formatted = ( $respCode != 0 )
					? HTML::element( 'font', [ 'color' => $colour ], HttpStatus::getMessage( $respCode ) ?? "HTTP: ${respCode}" )
					: HTML::element( 'font', [ 'color' => '#8B0000' ], 'No Response' );
				break;
			case 'rl_pageusage':
				$number = count( json_decode( $row->rl_pageusage, true ) );
				$specialLinkSearch = SpecialPage::getTitleFor( 'LinkSearch' );
				$href = $specialLinkSearch->getInternalURL( [ 'target' => $row->rl_externallink ] );
				$formatted = HTML::element( 'a', [ 'href' => $href ], (string)$number );
				break;
			default:
				$formatted = HTML::element( 'span', [], "Unable to format $name" );
				break;
		}

		return $formatted;
	}

	public function getQueryInfo() {
		$info = [
			'tables' => [ 'rottenlinks' ],
			'fields' => [ 'rl_externallink', 'rl_respcode', 'rl_pageusage' ],
			'conds' => [],
			'joins_conds' => [],
		];

		if ( $this->showBad ) {
			$info['conds']['rl_respcode'] = $this->config->get( 'RottenLinksBadCodes' );
		}

		return $info;
	}

	public function getDefaultSort() {
		return 'rl_externallink';
	}

	public function isFieldSortable( $name ) {
		return $name !== 'rl_pageusage';
	}
}
