( function () {

	'use strict';

	function loadSignMaker( iframe ) {

		var fsw = iframe.dataset.fsw;

		if ( !fsw ) {
			return;
		}

		iframe.contentWindow.postMessage(
			{
				fsw: fsw
			},
			'*'
		);
	}

	function initialize() {

		var iframes = document.querySelectorAll(
			'.signwriting-signmaker'
		);

		iframes.forEach( function ( iframe ) {

			iframe.addEventListener(
				'load',
				function () {
					loadSignMaker( iframe );
				}
			);

		} );
	}

	window.addEventListener( 'message', function ( event ) {

		if ( !event.data ) {
			return;
		}

		if ( event.data.signmaker !== 'save' ) {
			return;
		}

		console.log(
			'SignMaker returned FSW:',
			event.data.fsw
		);

	} );

	$( initialize );

}() );
