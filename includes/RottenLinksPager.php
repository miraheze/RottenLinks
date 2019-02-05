<?php
class RottenLinksPager extends TablePager {
	function __construct( $showBad ) {
		parent::__construct( $this->getContext() );
		$this->showBad = $showBad;
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
		global $wgScriptPath, $wgRottenLinksExternalLinkTarget, $wgRottenLinksBadCodes;

		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'rl_externallink':
				$formatted = Linker::makeExternalLink( (string)$row->rl_externallink, (string)$row->rl_externallink, $attribs = [ 'target' => $wgRottenLinksExternalLinkTarget ] );
				break;
			case 'rl_respcode':
				$respCode = (int)$row->rl_respcode;
				$colour = ( in_array( $respCode, $wgRottenLinksBadCodes ) ) ? "#8B0000" : "#008000";
				$formatted = ( $respCode != 0 ) ? "<font color=\"{$colour}\">" . HttpStatus::getMessage( $respCode ) . "</font>" : '<font color="#8B0000">No Response</font>';
				break;
			case 'rl_pageusage':
				$number = count( json_decode( $row->rl_pageusage, true ) );
				$formatted = "<a href=\"{$wgScriptPath}/index.php?title=Special%3ALinkSearch&target={$row->rl_externallink}\">{$number}</a>";
				break;
			default:
				$formatted = "Unable to format $name";
				break;
		}

		return $formatted;
	}

	function getQueryInfo() {
		global $wgRottenLinksBadCodes;

		$info = [
			'tables' => [ 'rottenlinks' ],
			'fields' => [ 'rl_externallink', 'rl_respcode', 'rl_pageusage' ],
			'conds' => [],
			'joins_conds' => [],
		];

		if ( $this->showBad ) {
			$info['conds']['rl_respcode'] = $wgRottenLinksBadCodes;
		}

		return $info;
	}

	function getDefaultSort() {
		return 'rl_externallink';
	}

	function isFieldSortable( $name ) {
		return true;
	}
}
