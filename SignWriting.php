<?php

namespace MediaWiki\Extension\SignWriting;

use Parser;
use PPFrame;

class SignWriting {

	public static function onParserFirstCallInit( Parser $parser ): void {
		$parser->setHook(
			'signwriting',
			[ self::class, 'renderSignWriting' ]
		);
	}

	public static function renderSignWriting(
		string $input,
		array $args,
		Parser $parser,
		PPFrame $frame
	): string {
		/*
		 * Load the stylesheet when the <signwriting> tag is used.
		 */
		$parser->getOutput()->addModuleStyles(
			[ 'ext.signwriting.styles' ]
		);

		$renderer = new SVGRenderer();

		/*
		 * If the input contains paragraph tags, process each
		 * paragraph independently.
		 */
		if ( preg_match( '/<p\b[^>]*>/i', $input ) ) {
			return self::renderParagraphs(
				$input,
				$renderer
			);
		}

		/*
		 * No <p> tags were supplied.
		 *
		 * Treat the entire contents as one paragraph.
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
		/*
		 * Match complete paragraph elements.
		 */
		$parts = preg_split(
			'/(<p\b[^>]*>.*?<\/p>)/is',
			$input,
			-1,
			PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
		);

		$output = [];

		foreach ( $parts as $part ) {

			/*
			 * A complete paragraph.
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

			/*
			 * Preserve other content outside paragraphs.
			 */
			$output[] = $part;
		}

		return implode( '', $output );
	}

	/**
	 * Render whitespace-separated FSW signs.
	 *
	 * Any whitespace can separate signs:
	 *
	 *     FSW1 FSW2
	 *
	 *     FSW1
	 *     FSW2
	 *
	 *     FSW1    FSW2
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
			/*
			 * Give every sign its own independent box.
			 *
			 * This is important for the CSS multi-column layout:
			 * the wrapper is the actual column item, so its border,
			 * spacing, and centering belong to one complete sign.
			 */
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
