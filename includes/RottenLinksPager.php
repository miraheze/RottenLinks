<?php
class RottenLinksPager extends TablePager {
	function __construct() {
		parent::__construct( $this->getContext() );
		$this->mLimit = 25;
	}

	function getFieldNames() {
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

	function formatValue( $name, $value ) {
		global $wgScriptPath;

		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'rl_externallink':
				$formatted = Linker::makeExternalLink( (string)$row->rl_externallink, (string)$row->rl_externallink );
				break;
			case 'rl_respcode':
				$formatted = ( (int)$row->rl_respcode != 0 ) ? HttpStatus::getMessage( (int)$row->rl_respcode ) : 'No Response';
				break;
			case 'rl_pageusage':
				$number = count( json_decode( $row->rl_pageusage, true ) );
				$formatted = "<a href='$wgScriptPath/Special:LinkSearch/$row->rl_externallink'>$number</a>";
				break;
			default:
				$formatted = "Unable to format $name";
				break;
		}

		return $formatted;
	}

	function getQueryInfo() {
		$info = [
			'tables' => [ 'rottenlinks' ],
			'fields' => [ 'rl_externallink', 'rl_respcode', 'rl_pageusage' ],
			'conds' => [],
			'joins_conds' => [],
		];

		return $info;
	}

	function getDefaultSort() {
		return 'rl_externallink';
	}

	function isFieldSortable( $name ) {
		return true;
	}
}
