<?php

namespace MediaWiki\Extension\SignWriting;

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class SpecialSignWritingEdit extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SignWritingEdit' );
	}

	public function execute( $subPage ) {
		$this->setHeaders();

		$output = $this->getOutput();
		$request = $this->getRequest();

		$output->addModules( [
			'ext.signWriting.editor'
		] );

		$output->addHTML(
			'<h2>SignWriting Editor</h2>'
		);

		$pageName = $request->getText( 'page' );

		if ( $pageName === '' ) {
			$output->addHTML(
				'<p>No page was specified.</p>'
			);
			return;
		}

		$title = Title::newFromText( $pageName );

		if ( !$title ) {
			$output->addHTML(
				'<p>Invalid page title.</p>'
			);
			return;
		}

		$wikiPage = MediaWikiServices::getInstance()
			->getWikiPageFactory()
			->newFromTitle( $title );

		$content = $wikiPage->getContent();

		if ( !$content ) {
			$output->addHTML(
				'<p>The page has no content.</p>'
			);
			return;
		}

		$wikitext = $content->serialize();

		$output->addHTML(
			'<h3>Page</h3>'
		);

		$output->addHTML(
			'<p>' .
			htmlspecialchars(
				$title->getPrefixedText(),
				ENT_QUOTES
			) .
			'</p>'
		);

		/*
		 * Find all <signwriting>...</signwriting> blocks.
		 */
		$pattern = '/<signwriting\b[^>]*>(.*?)<\/signwriting>/is';

		preg_match_all(
			$pattern,
			$wikitext,
			$matches
		);

		if ( empty( $matches[1] ) ) {
			$output->addHTML(
				'<p>No SignWriting found on this page.</p>'
			);
			return;
		}

		$output->addHTML(
			'<h3>SignWriting</h3>'
		);

		$signMakerUrl =
			$this->getConfig()->get( 'ScriptPath' ) .
			'/extensions/SignWriting/signmaker/index.html';
foreach ( $matches[1] as $blockIndex => $block ) {

	$fsws = preg_split(
		'/\s+/',
		trim( $block ),
		-1,
		PREG_SPLIT_NO_EMPTY
	);

	foreach ( $fsws as $signIndex => $fsw ) {

		$output->addHTML(
			'<h4>Sign ' .
			( $signIndex + 1 ) .
			'</h4>'
		);

		$output->addHTML(
			'<pre>' .
			htmlspecialchars(
				$fsw,
				ENT_QUOTES
			) .
			'</pre>'
		);

		$output->addHTML(
			'<div
				class="signwriting-signmaker-container"
				style="
					display: block;
					width: 100%;
					margin: 0 0 30px 0;
				">
				<iframe
					class="signwriting-signmaker"
					data-fsw="' .
					htmlspecialchars(
						$fsw,
						ENT_QUOTES
					) .
					'"
					src="' .
					htmlspecialchars(
						$signMakerUrl,
						ENT_QUOTES
					) .
					'"
					style="
						display: block;
						width: 100%;
						height: 900px;
						border: 1px solid #a2a9b1;
					">
				</iframe>
			</div>'
		);
	}
}

	}
}
