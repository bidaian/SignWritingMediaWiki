<?php

namespace MediaWiki\Extension\SignWriting;

use MediaWiki\MediaWikiServices;
use PDO;
use RuntimeException;

class SVGRenderer {

	/**
	 * Increase this number whenever the SVG rendering implementation
	 * changes in a way that makes existing cached SVGs obsolete.
	 */
	private const CACHE_VERSION = 2;

	/**
	 * Number of seconds to keep generated SVGs in the cache.
	 */
	private const CACHE_TTL = 30 * 24 * 60 * 60;

	private PDO $db;

	public function __construct() {
		$database = __DIR__
			. '/lib/SuttonSignWriting/font-db/iswa2010.db';

		if ( !is_readable( $database ) ) {
			throw new RuntimeException(
				'SignWriting symbol database not found.'
			);
		}

		$this->db = new PDO(
			'sqlite:' . $database
		);

		$this->db->setAttribute(
			PDO::ATTR_ERRMODE,
			PDO::ERRMODE_EXCEPTION
		);
	}

	/**
	 * Render a parsed FSW sign as SVG.
	 *
	 * When the original FSW string is supplied, the generated SVG
	 * is stored in MediaWiki's WANObjectCache.
	 *
	 * @param array $sign Parsed FSW sign
	 * @param string|null $fsw Original FSW string
	 *
	 * @return string SVG markup
	 */
	public function render(
		array $sign,
		?string $fsw = null
	): string {
		/*
		 * If we have the original FSW string, use it to create a
		 * stable cache key.
		 */
		if ( $fsw !== null ) {
			$cache = MediaWikiServices::getInstance()
				->getMainWANObjectCache();

			$cacheKey = $cache->makeKey(
				'signwriting',
				'svg',
				'v' . self::CACHE_VERSION,
				hash(
					'sha256',
					trim( $fsw )
				)
			);

			return $cache->getWithSetCallback(
				$cacheKey,
				self::CACHE_TTL,
				function () use ( $sign ): string {
					return $this->renderUncached( $sign );
				}
			);
		}

		/*
		 * If no original FSW string was supplied, simply render
		 * without using the per-sign cache.
		 */
		return $this->renderUncached( $sign );
	}

	/**
	 * Build the SVG without using the cache.
	 *
	 * Sutton SignWriting FSW coordinates are used directly as
	 * the x/y coordinates of the individual symbols.
	 *
	 * @param array $sign Parsed FSW sign
	 *
	 * @return string SVG markup
	 */
	private function renderUncached( array $sign ): string {
		$symbols = $sign['spatials'] ?? [];

		if ( $symbols === [] ) {
			return '';
		}

		$max = $sign['max'] ?? null;

		if (
			!is_array( $max ) ||
			count( $max ) !== 2
		) {
			throw new RuntimeException(
				'Invalid SignWriting sign box.'
			);
		}

		$maxX = (int)$max[0];
		$maxY = (int)$max[1];

		$minX = PHP_INT_MAX;
		$minY = PHP_INT_MAX;

		$rendered = [];

		$query = $this->db->prepare(
			'SELECT svg
			 FROM symbol
			 WHERE symkey = :symkey'
		);

		foreach ( $symbols as $spatial ) {
			if (
				!isset( $spatial['symbol'] ) ||
				!isset( $spatial['coord'] ) ||
				!is_array( $spatial['coord'] ) ||
				count( $spatial['coord'] ) !== 2
			) {
				continue;
			}

			$symbol = $spatial['symbol'];

			$x = (int)$spatial['coord'][0];
			$y = (int)$spatial['coord'][1];

			$query->execute( [
				':symkey' => $symbol,
			] );

			$row = $query->fetch(
				PDO::FETCH_ASSOC
			);

			if ( !$row ) {
				throw new RuntimeException(
					'SignWriting symbol not found: ' . $symbol
				);
			}

			/*
			 * The SVG fragment from iswa2010.db already contains
			 * its own internal coordinate transformation.
			 *
			 * Therefore the FSW coordinates are used directly.
			 */
			$rendered[] = sprintf(
				'<svg x="%d" y="%d">%s</svg>',
				$x,
				$y,
				$row['svg']
			);

			$minX = min(
				$minX,
				$x
			);

			$minY = min(
				$minY,
				$y
			);
		}

		if ( $rendered === [] ) {
			return '';
		}

		/*
		 * The sign box gives the maximum coordinates.
		 *
		 * Keep the original coordinate calculation. Do not use
		 * the SQLite glyph width/height here because those values
		 * do not directly describe the transformed SVG bounds.
		 */
		$width = $maxX - $minX;
		$height = $maxY - $minY;

		/*
		 * Always provide a valid SVG viewBox.
		 */
		$width = max(
			1,
			$width
		);

		$height = max(
			1,
			$height
		);

		$body = implode(
			"\n",
			$rendered
		);

		/*
		 * The outer SVG has only a viewBox.
		 *
		 * This allows CSS to control the rendered size while
		 * preserving the natural aspect ratio.
		 */
return sprintf(
	'<span class="signwriting">'
	. '<svg xmlns="http://www.w3.org/2000/svg" '
	. 'width="%d" '
	. 'height="%d" '
	. 'viewBox="%d %d %d %d" '
	. 'preserveAspectRatio="xMidYMid meet" '
	. 'role="img">'
	. '%s'
	. '</svg>'
	. '</span>',
	$width,
	$height,
	$minX,
	$minY,
	$width,
	$height,
	$body
);
	}
}
