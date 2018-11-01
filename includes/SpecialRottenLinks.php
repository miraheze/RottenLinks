<?php
class SpecialRottenLinks extends SpecialPage {
	function __construct() {
		parent::__construct( 'RottenLinks' );
	}

	function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$showBad = $this->getRequest()->getText( 'showBad' );
		$formDescriptor['showBad'] = [
			'type' => 'check',
			'name' => 'showBad',
			'label-message' => 'rottenlinks-showbad',
			'default' => ( $showBad ) ? $showBad : false
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'dummyProcess' ] )->setMethod( 'get' )->prepareForm()->show();

		$pager = new RottenLinksPager( $showBad );
		$table = $pager->getBody();

		$this->getOutput()->addHTML( $pager->getNavigationBar() . $table . $pager->getNavigationBar() );
	}

	static function dummyProcess( $formData ) {
		return false;
	}

	protected function getGroupName() {
		return 'maintenance';
	}
}
