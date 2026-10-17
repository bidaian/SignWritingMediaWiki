<?php

namespace MediaWiki\Extension\SignWriting;

use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use Wikimedia\HtmlArmor\HtmlArmor;
use Parser;
use PPFrame;

class SignWriting {

	public static function onParserFirstCallInit( Parser $parser ): void {
		$parser->setHook(
			'signwriting',
			[ self::class, 'renderSignWriting' ]
		);
	}

	/**
	 * Replace an FSW page title with a SignWriting SVG.
	 */
	public static function onOutputPageParserOutput(
		OutputPage $outputPage,
		ParserOutput $parserOutput
	): void {
		$title = $outputPage->getTitle();

		if ( !$title ) {
			return;
		}

		$fsw = $title->getText();

		if ( $fsw === '' ) {
			return;
		}

		/*
		 * Try to parse the page title as FSW.
		 *
		 * FSWParser is the authority for deciding whether the
		 * title is a valid SignWriting string.
		 */
		try {
			$sign = FSWParser::parse( $fsw );
		} catch ( \Throwable $e ) {
			return;
		}

		/*
		 * Use the same SVGRenderer used by <signwriting>.
		 */
		try {
			$renderer = new SVGRenderer();

			$svg = $renderer->render(
				$sign,
				$fsw
			);
		} catch ( \Throwable $e ) {
			return;
		}

		if ( $svg === '' ) {
			return;
		}

		/*
		 * The SVG itself cannot safely survive the display-title
		 * processing pipeline, so put a harmless placeholder there.
		 */
		$parserOutput->setDisplayTitle(
			'<span class="mw-signwriting-title"></span>'
		);

		/*
		 * Pass the already-rendered SVG to JavaScript.
		 *
		 * OutputPage::addJsConfigVars() is used here because this
		 * is the mechanism verified to work with this MediaWiki
		 * version.
		 */
		$outputPage->addJsConfigVars(
			'wgSignWritingTitleSvg',
			$svg
		);

		/*
		 * Load the title integration module.
		 */
		$outputPage->addModules(
			[ 'ext.signwriting.title' ]
		);

		/*
		 * Make the existing SignWriting CSS available.
		 */
		$outputPage->addModuleStyles(
			[ 'ext.signwriting.styles' ]
		);
	}

	/**
	 * Replace the contents of an FSW wikilink with its SignWriting SVG.
	 *
	 * MediaWiki itself continues to generate the <a> element, so
	 * the resulting SVG remains clickable.
	 *
	 * @param LinkRenderer $linkRenderer
	 * @param LinkTarget $target
	 * @param bool $isKnown
	 * @param string|HtmlArmor &$text
	 * @param array &$attribs
	 * @param string &$ret
	 */
	public static function onHtmlPageLinkRendererEnd(
		LinkRenderer $linkRenderer,
		LinkTarget $target,
		bool $isKnown,
		&$text,
		&$attribs,
		&$ret
	): void {
		/*
		 * The link target itself is the FSW string.
		 *
		 * This means the hook works regardless of what the visible
		 * link text originally contained.
		 */
		$fsw = $target->getText();

		if ( $fsw === '' ) {
			return;
		}

		/*
		 * Determine whether the target is valid FSW.
		 *
		 * Invalid/non-FSW page links are left completely untouched.
		 */
		try {
			$sign = FSWParser::parse( $fsw );
		} catch ( \Throwable $e ) {
			return;
		}

		/*
		 * Render using the existing SignWriting renderer.
		 */
		try {
			$renderer = new SVGRenderer();

			$svg = $renderer->render(
				$sign,
				$fsw
			);
		} catch ( \Throwable $e ) {
			return;
		}

		if ( $svg === '' ) {
			return;
		}

		/*
		 * Replace only the contents of the link.
		 *
		 * MediaWiki will still generate:
		 *
		 * <a href="..."> ...our SVG... </a>
		 *
		 * HtmlArmor tells MediaWiki that the SVG is already safe HTML
		 * and must not be escaped.
		 */
		$text = new HtmlArmor(
			'<span class="mw-signwriting-link">'
			. $svg
			. '</span>'
		);
	}

	public static function renderSignWriting(
		string $input,
		array $args,
		Parser $parser,
		PPFrame $frame
	): string {
		$parser->getOutput()->addModuleStyles(
			[ 'ext.signwriting.styles' ]
		);

		$renderer = new SVGRenderer();

		/*
		 * If the input contains paragraph tags, render each
		 * paragraph separately.
		 */
		if ( preg_match( '/<p\b[^>]*>/i', $input ) ) {
			return self::renderParagraphs(
				$input,
				$renderer
			);
		}

		/*
		 * No <p> tags: treat the input as one paragraph.
		 */
		return '<p>'
			. '<span class="signwriting-paragraph">'
			. '<span class="signwriting-flow">'
			. self::renderSignSequence(
				$input,
				$renderer
			)
			. '</span>'
			. '</span>'
			. '</p>';
	}

	/**
	 * Render input containing one or more <p> elements.
	 */
	private static function renderParagraphs(
		string $input,
		SVGRenderer $renderer
	): string {
		$parts = preg_split(
			'/(<p\b[^>]*>.*?<\/p>)/is',
			$input,
			-1,
			PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
		);

		$output = [];

		foreach ( $parts as $part ) {

			/*
			 * Complete paragraph.
			 */
			if ( preg_match(
				'/^<p\b([^>]*)>(.*?)<\/p>$/is',
				$part,
				$matches
			) ) {
				$attributes = $matches[1];
				$content = $matches[2];

				$signs = self::renderSignSequence(
					$content,
					$renderer
				);

				$output[] =
					'<p' . $attributes . '>'
					. '<span class="signwriting-paragraph">'
					. '<span class="signwriting-flow">'
					. $signs
					. '</span>'
					. '</span>'
					. '</p>';

				continue;
			}

			/*
			 * Ignore whitespace outside paragraphs.
			 */
			if ( trim( $part ) === '' ) {
				continue;
			}

			$output[] = $part;
		}

		return implode( '', $output );
	}

	/**
	 * Render whitespace-separated FSW signs.
	 */
	private static function renderSignSequence(
		string $input,
		SVGRenderer $renderer
	): string {
		$parts = preg_split(
			'/\s+/',
			trim( $input ),
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		$output = [];

		foreach ( $parts as $fsw ) {
			$output[] =
				'<span class="signwriting-item">'
				. self::renderFSW(
					$fsw,
					$renderer
				)
				. '</span>';
		}

		return implode( '', $output );
	}

	/**
	 * Parse and render one FSW sign.
	 */
	private static function renderFSW(
		string $fsw,
		SVGRenderer $renderer
	): string {
		try {
			$sign = FSWParser::parse( $fsw );

			return $renderer->render(
				$sign,
				$fsw
			);

		} catch ( \Throwable $e ) {
			return '<span class="signwriting-error">'
				. htmlspecialchars(
					$e->getMessage(),
					ENT_QUOTES | ENT_SUBSTITUTE,
					'UTF-8'
				)
				. '</span>';
		}
	}
}
