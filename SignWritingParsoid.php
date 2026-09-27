<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\SignWriting;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class SignWritingParsoid extends ExtensionTagHandler {

	/**
	 * Convert <signwriting>...</signwriting> to Parsoid DOM.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param string $content
	 * @param array $args
	 * @return DocumentFragment
	 */
public function sourceToDom(
    ParsoidExtensionAPI $extApi,
    string $content,
    array $args
) {
    $fsw = trim( $content );

    $renderer = new SVGRenderer();

    try {
        $sign = FSWParser::parse( $fsw );
        $svg = $renderer->render( $sign, $fsw );
    } catch ( \Throwable $e ) {
        $svg =
            '<span class="signwriting-error">' .
            htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) .
            '</span>';
    }

    $html =
        '<span class="mw-signwriting" ' .
        'typeof="mw:Extension/signwriting" ' .
        'data-fsw="' .
        htmlspecialchars(
            $fsw,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) .
        '">' .
        $svg .
        '</span>';

    return $extApi->htmlToDom( $html );
}

/**
 * Convert the Parsoid DOM back to <signwriting>...</signwriting>.
 *
 * @param ParsoidExtensionAPI $extApi
 * @param Element $node
 * @param bool $wrapperUnmodified
 * @return string
 */
public function domToWikitext(
	ParsoidExtensionAPI $extApi,
	Element $node,
	bool $wrapperUnmodified
) {
	$dataMw = DOMDataUtils::getDataMw( $node );

	if (
		isset( $dataMw->body ) &&
		isset( $dataMw->body->extsrc ) &&
		is_string( $dataMw->body->extsrc )
	) {
		return '<signwriting>' .
			$dataMw->body->extsrc .
			'</signwriting>';
	}

	return false;
}
}
