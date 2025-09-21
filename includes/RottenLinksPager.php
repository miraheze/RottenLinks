<?php

namespace Miraheze\RottenLinks;

use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\ExternalLinks\LinkFilter;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\TablePager;
use MediaWiki\SpecialPage\SpecialPage;
use Wikimedia\Http\HttpStatus;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class RottenLinksPager extends TablePager {

	private Config $config;
	private LinkRenderer $linkRenderer;
	private bool $showBad;

	public function __construct(
		Config $config,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		bool $showBad
	) {
		parent::__construct( $context, $linkRenderer );

		$this->config = $config;
		$this->linkRenderer = $linkRenderer;
		$this->showBad = $showBad;
	}

	/** @inheritDoc */
	public function getFieldNames(): array {
		return [
			'rl_externallink' => $this->msg( 'rottenlinks-table-external' )->text(),
			'rl_respcode' => $this->msg( 'rottenlinks-table-response' )->text(),
			'rl_pageusage' => $this->msg( 'rottenlinks-table-usage' )->text(),
		];
	}

	/** @inheritDoc */
	public function formatValue( $field, $value ): string {
		if ( $value === null ) {
			return '';
		}

		switch ( $field ) {
			case 'rl_externallink':
				$formatted = $this->linkRenderer->makeExternalLink(
					$value,
					substr( $value, 0, 50 ) . '...',
					SpecialPage::getTitleFor( 'RottenLinks' ), '',
					[ 'target' => $this->config->get( 'RottenLinksExternalLinkTarget' ) ]
				);
				break;
			case 'rl_respcode':
				$respCode = (int)$value;
				$color = in_array(
					$respCode,
					$this->config->get( 'RottenLinksBadCodes' ),
					true
				) ? '#8B0000' : '#008000';
				$formatted = $respCode !== 0
					? Html::element( 'font',
						[ 'color' => $color ],
						HttpStatus::getMessage( $respCode ) ?? "HTTP: $respCode"
					)
					: Html::element( 'font', [ 'color' => '#8B0000' ], 'No Response' );
				break;
			case 'rl_pageusage':
				$row = $this->getCurrentRow();
				$db = $this->getDatabase();

				$el = LinkFilter::makeIndexes( $row->rl_externallink );
				$pagesCount = $db->newSelectQueryBuilder()
					->select( ISQLPlatform::ALL_ROWS )
					->from( 'externallinks' )
					->where( [
						'el_to_domain_index' => substr( $el[0][0], 0, 255 ),
						'el_to_path' => $el[0][1],
					] )
					->caller( __METHOD__ )
					->fetchRowCount();

				$specialLinkSearch = SpecialPage::getTitleFor( 'LinkSearch' );
				$href = $specialLinkSearch->getFullURL( [ 'target' => $row->rl_externallink ] );
				$formatted = Html::element( 'a', [ 'href' => $href ], (string)$pagesCount );
				break;
			default:
				$formatted = Html::element( 'span', [], "Unable to format $field" );
				break;
		}

		return $formatted;
	}

	/** @inheritDoc */
	public function getQueryInfo(): array {
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

	/** @inheritDoc */
	public function getDefaultSort(): string {
		return 'rl_externallink';
	}

	/** @inheritDoc */
	public function isFieldSortable( $field ): bool {
		return $field !== 'rl_pageusage';
	}
}
