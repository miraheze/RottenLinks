<?php
class SpecialRottenLinks extends SpecialPage {
	function __construct() {
		parent::__construct( 'RottenLinks' );
	}

	function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$pager = new RottenLinksPager();
		$table = $pager->getBody();

		$this->getOutput()->addHTML( $pager->getNavigationBar() . $table . $pager->getNavigationBar() );
	}

	protected function getGroupName() {
		return 'maintenance';
	}
}
