( function () {
	'use strict';

	function renderSignWritingTitle() {
		var placeholders = document.querySelectorAll(
			'.mw-signwriting-title'
		);

		var svg = mw.config.get( 'wgSignWritingTitleSvg' );

		if ( !svg ) {
			return;
		}

		placeholders.forEach( function ( placeholder ) {
			placeholder.innerHTML = svg;
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener(
			'DOMContentLoaded',
			renderSignWritingTitle
		);
	} else {
		renderSignWritingTitle();
	}
}() );
