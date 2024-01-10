<?php

namespace Miraheze\RottenLinks;

use Config;
use Html;
use HttpStatus;
use IContextSource;
use Linker;
use MediaWiki\ExternalLinks\LinkFilter;
use SpecialPage;
use TablePager;

class RottenLinksPager extends TablePager {

	/** @var Config */
	private $config;

	/** @var bool */
	private $showBad;

	/**
	 * @param IContextSource $context The context source.
	 * @param Config $config RottenLinks config factory instance.
	 * @param bool $showBad Whether to show only links with bad status.
	 */
	public function __construct(
		IContextSource $context,
		Config $config,
		bool $showBad
	) {
		parent::__construct( $context );

		$this->showBad = $showBad;
		$this->config = $config;
	}

	/**
	 * Get the field names for the table header.
	 *
	 * @return array Field names and their corresponding messages.
	 */
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

	/**
	 * Format the values for each field in the table.
	 *
	 * @param string $name Field name.
	 * @param mixed $value Field value.
	 *
	 * @return string Formatted HTML for the field.
	 */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		$db = $this->getDatabase();
		switch ( $name ) {
			case 'rl_externallink':
				$formatted = Linker::makeExternalLink(
					(string)$row->rl_externallink,
					( substr( (string)$row->rl_externallink, 0, 50 ) . '...' ),
					true, '',
					[ 'target' => $this->config->get( 'RottenLinksExternalLinkTarget' ) ]
				);
				break;
			case 'rl_respcode':
				$respCode = (int)$row->rl_respcode;
				$colour = ( in_array(
					$respCode,
					$this->config->get( 'RottenLinksBadCodes' )
				) ) ? "#8B0000" : "#008000";
				$formatted = ( $respCode != 0 )
					? Html::element( 'font',
						[ 'color' => $colour ],
						HttpStatus::getMessage( $respCode ) ?? "HTTP: {$respCode}"
					)
					: Html::element( 'font', [ 'color' => '#8B0000' ], 'No Response' );
				break;
			case 'rl_pageusage':
				$el = LinkFilter::makeIndexes( $row->rl_externallink );
				$pagesCount = $db->newSelectQueryBuilder()
					->select( '*' )
					->from( 'externallinks' )
					->where( [
						'el_to_domain_index' => substr( $el[0][0], 0, 255 ),
						'el_to_path' => $el[0][1]
					] )
					->caller( __METHOD__ )
					->fetchRowCount();

				$specialLinkSearch = SpecialPage::getTitleFor( 'LinkSearch' );
				$href = $specialLinkSearch->getFullURL( [ 'target' => $row->rl_externallink ] );
				$formatted = Html::element( 'a', [ 'href' => $href ], (string)$pagesCount );
				break;
			default:
				$formatted = Html::element( 'span', [], "Unable to format $name" );
				break;
		}

		return $formatted;
	}

	/**
	 * Get the query information for the pager.
	 *
	 * @return array Query information.
	 */
	public function getQueryInfo() {
		$info = [
			'tables' => [ 'rottenlinks' ],
			'fields' => [ 'rl_externallink', 'rl_respcode' ],
			'conds' => [],
			'joins_conds' => [],
		];

		if ( $this->showBad ) {
			$info['conds']['rl_respcode'] = $this->config->get( 'RottenLinksBadCodes' );
		}

		return $info;
	}

	/**
	 * Get the default sorting field for the table.
	 *
	 * @return string Default sorting field.
	 */
	public function getDefaultSort() {
		return 'rl_externallink';
	}

	/**
	 * Check if a field is sortable.
	 *
	 * @param string $name Field name.
	 *
	 * @return bool True if the field is sortable, false otherwise.
	 */
	public function isFieldSortable( $name ) {
		return $name !== 'rl_pageusage';
	}
}
