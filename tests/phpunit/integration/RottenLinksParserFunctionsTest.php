<?php

namespace Miraheze\RottenLinks\Tests\Integration;

use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use MediaWikiIntegrationTestCase;
use ParserOptions;

/**
 * @coversDefaultClass Miraheze\RottenLinks\RottenLinksParserFunctions
 * @group Database
 */
class RottenLinksParserFunctionsTest extends MediaWikiIntegrationTestCase {

	public function addDBDataOnce(): void {
		$this->getDB()->newInsertQueryBuilder()
			->insertInto( 'rottenlinks' )
			->rows( [
				[ 'rl_externallink' => 'https://ooo.eeeee.ooo/', 'rl_respcode' => 418 ],
				[ 'rl_externallink' => 'https://witix777.neocities.org/', 'rl_respcode' => 0 ],
			] )
			->execute();
	}

	/**
	 * Data provider to test RottenLinksParserFunctions::onRLStatus()
	 *
	 * @return array
	 */
	public static function provideOnRLStatus(): array {
		return [
			[ '', Html::element( 'strong', [ 'class' => 'error' ], '(rottenlinks-rlstatus-no-url)' ) ],
			[ 'https://rainverse.wiki/', '' ],
			[ 'https://ooo.eeeee.ooo/', '418' ],
			[ 'https://witix777.neocities.org/', '0' ],
		];
	}

	/**
	 * Test RottenLinksParserFunctions::onRLStatus()
	 *
	 * @dataProvider provideOnRLStatus
	 * @param string $url
	 * @param string $expected
	 * @covers ::onRLStatus
	 */
	public function testOnRLStatus( string $url, string $expected ): void {
		// Set the target language to "qqx", mainly so that we can test no URL
		// without having to either hardcode the English string, or having to do
		// some file read shenanigans to get the string.
		$services = $this->getServiceContainer();
		$options = ParserOptions::newFromAnon();
		$options->setTargetLanguage( $services->getLanguageFactory()->getLanguage( 'qqx' ) );

		$parser = $services->getParserFactory()->create();
		$parser->startExternalParse( null, $options, Parser::OT_WIKI );
		$frame = $parser->getPreprocessor()->newFrame();

		$output = $parser->callParserFunction( $frame, '#rl_status', [ $url ] );
		$this->assertTrue( $output['found'] );
		$this->assertSame( $expected, $output['text'] );
	}
}
