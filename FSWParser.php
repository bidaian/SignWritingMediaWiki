<?php

namespace MediaWiki\Extension\SignWriting;

class FSWParser {

	public static function parse( string $fsw ): array {
		require_once __DIR__
			. '/lib/SuttonSignWriting/src/Functions/fsw.php';

		return fsw_parse_sign( trim( $fsw ) );
	}
}
