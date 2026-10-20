/*!
 * VisualEditor UserInterface MWSignWritingDialog class.
 *
 * @license MIT
 */

/**
 * MediaWiki SignWriting dialog.
 *
 * @class
 * @extends ve.ui.MWExtensionPreviewDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWSignWritingDialog = function VeUiMWSignWritingDialog( config ) {
	// Parent constructor
	ve.ui.MWSignWritingDialog.super.call( this, config );

	this.iframe = null;
	this.messageHandler = null;
	this.isInsertion = false;
};

/* Inheritance */

OO.inheritClass(
	ve.ui.MWSignWritingDialog,
	ve.ui.MWExtensionPreviewDialog
);

/* Static properties */

ve.ui.MWSignWritingDialog.static.name = 'mwSignWritingDialog';

ve.ui.MWSignWritingDialog.static.title = 'SignWriting';

/*
 * Use the same window sizing mechanism as Math.
 */
ve.ui.MWSignWritingDialog.static.size = 'larger';

ve.ui.MWSignWritingDialog.static.modelClasses = [
	ve.dm.MWSignWritingNode
];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWSignWritingDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWSignWritingDialog.super.prototype.initialize.call( this );

	/*
	 * SignMaker is the complete editor.
	 *
	 * Put it directly into the dialog body rather than
	 * using the normal extension inspector form.
	 */
	this.$body.addClass( 've-ui-mwSignWritingDialog-content' );

};

/**
 * Get the whole <signwriting> tag body.
 *
 * @return {string}
 */
ve.ui.MWSignWritingDialog.prototype.getSource = function () {
	if ( this.isInsertion || !this.selectedNode ) {
		return '';
	}

	var mwData = this.selectedNode.getAttribute( 'mw' );

	if (
		mwData &&
		mwData.body &&
		typeof mwData.body.extsrc === 'string'
	) {
		return mwData.body.extsrc;
	}

	return '';
};

/**
 * Locate the clicked sign inside the tag body.
 *
 * Matches whole whitespace/tag-delimited tokens, so a sign can't
 * accidentally match inside a longer one (for example the M-part
 * of a sequence-prefixed sign).
 *
 * @param {string} source Tag body
 * @return {Object|null} { start, end } or null if there is no target
 */
ve.ui.MWSignWritingDialog.prototype.findSign = function ( source ) {
	if ( !this.signTarget ) {
		return null;
	}

	var re = /[^\s<>]+/g,
		n = 0,
		m;

	while ( ( m = re.exec( source ) ) !== null ) {
		if ( m[ 0 ] === this.signTarget.fsw ) {
			if ( n === this.signTarget.occurrence ) {
				return { start: m.index, end: m.index + m[ 0 ].length };
			}
			n++;
		}
	}

	return null;
};

/**
 * Get the FSW to edit: the clicked sign, or the whole body as a fallback.
 *
 * @return {string}
 */
ve.ui.MWSignWritingDialog.prototype.getFSW = function () {
	var source = this.getSource();

	return this.findSign( source ) ? this.signTarget.fsw : source;
};

/**
 * @inheritdoc
 */
ve.ui.MWSignWritingDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWSignWritingDialog.super.prototype
		.getSetupProcess.call( this, data )
		.next( function () {

			this.isInsertion = !!( data && data.insertion );

			/*
			 * Consume the sign recorded by the CE node's mousedown.
			 * If there is none (e.g. keyboard activation), the whole
			 * tag body is edited, as before.
			 */
			this.signTarget = null;

			if ( !this.isInsertion && this.selectedNode ) {
				this.signTarget = this.selectedNode.signTarget || null;
				this.selectedNode.signTarget = null;
			}

			/*
			 * Listen for SignMaker's save message on every open.
			 *
			 * VisualEditor reuses this dialog instance, but initialize()
			 * runs only once and the teardown step removes the listener,
			 * so registering it in initialize() works for the first
			 * open only.
			 */
			if ( this.messageHandler ) {
				window.removeEventListener(
					'message',
					this.messageHandler
				);
			}

			this.messageHandler =
				this.onSignMakerMessage.bind( this );

			window.addEventListener(
				'message',
				this.messageHandler
			);

			/*
			 * Create a new SignMaker iframe every time
			 * the dialog is opened.
			 */
			this.iframe = document.createElement( 'iframe' );

			this.iframe.src =
				mw.config.get( 'wgExtensionAssetsPath' ) +
				'/SignWriting/signmaker/index.html';

			this.iframe.setAttribute(
				'title',
				'SignWriting editor'
			);

			this.iframe.setAttribute(
				'frameborder',
				'0'
			);

			this.iframe.style.width = '100%';
			this.iframe.style.height = '100%';
			this.iframe.style.border = '0';
			this.iframe.style.display = 'block';

			this.$body.append( this.iframe );

			var fsw = this.getFSW();

			this.iframe.addEventListener(
				'load',
				function () {
					this.loadSignMaker( fsw );
				}.bind( this ),
				{ once: true }
			);

		}.bind( this ) );
};

/**
 * @inheritdoc
 */
ve.ui.MWSignWritingDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWSignWritingDialog.super.prototype
		.getTeardownProcess.call( this, data )
		.next( function () {

			if ( this.messageHandler ) {
				window.removeEventListener(
					'message',
					this.messageHandler
				);
			}

			this.messageHandler = null;

			if ( this.iframe ) {
				this.iframe.remove();
				this.iframe = null;
			}

		}.bind( this ) );
};

/**
 * SignWriting does not use the normal extension input.
 *
 * The FSW is written directly into mwData by
 * onSignMakerMessage().
 *
 * @param {Object} mwData MediaWiki data object
 */
ve.ui.MWSignWritingDialog.prototype.updateMwData = function ( mwData ) {
	/*
	 * SignMaker is the editor, so there is no input value
	 * to copy here.
	 *
	 * The mwData object is updated directly when SignMaker
	 * sends its save message.
	 */
};

/**
 * Send the current FSW to SignMaker.
 *
 * @param {string} fsw FSW notation
 */
ve.ui.MWSignWritingDialog.prototype.loadSignMaker = function ( fsw ) {
	if (
		!this.iframe ||
		!this.iframe.contentWindow
	) {
		return;
	}

	this.iframe.contentWindow.postMessage(
		{
			fsw: fsw
		},
		'*'
	);
};

/**
 * Handle messages from SignMaker.
 *
 * @param {MessageEvent} event Message event
 */
ve.ui.MWSignWritingDialog.prototype.onSignMakerMessage = function ( event ) {
	if (
		!event.data ||
		event.data.signmaker !== 'save'
	) {
		return;
	}

	if (
		!this.iframe ||
		event.source !== this.iframe.contentWindow
	) {
		return;
	}

	var fsw = event.data.fsw;

	if ( typeof fsw !== 'string' ) {
		return;
	}

	/*
	 * Insert a new inline extension node when the dialog
	 * was opened from the insertion tool.
	 */
	if ( this.isInsertion ) {
		var fragment = this.getFragment();

		fragment.insertContent( [
			{
				type: 'mwSignWriting',
				attributes: {
					mw: {
						name: 'signwriting',
						attrs: {},
						body: {
							extsrc: fsw
						}
					}
				}
			}
		] ).collapseRangeToEnd().select();

		this.close( { action: 'done' } );

		return;
	}

	var mwData = ve.copy(
		this.selectedNode.getAttribute( 'mw' )
	);

	if (
		!mwData ||
		!mwData.body
	) {
		return;
	}

	/*
	 * Store the new FSW in the MediaWiki data.
	 *
	 * If one sign of several was edited, replace only that sign.
	 */
	var source = this.getSource(),
		range = this.findSign( source );

	mwData.body.extsrc = range ?
		source.slice( 0, range.start ) + fsw + source.slice( range.end ) :
		fsw;

	/*
	 * Update the VisualEditor model while the dialog's
	 * staging transaction is still active.
	 */
	this.getFragment().changeAttributes( {
		mw: mwData
	} );

	/*
	 * Apply the staged change to the document model.
	 */
	this.insertOrUpdateNode();

	/*
	 * Close the SignWriting dialog.
	 */
	this.close( {
		action: 'done'
	} );
};

/**
 * Set the dialog body height.
 *
 * This follows the general approach used by the Math
 * VisualEditor dialog.
 *
 * @return {number}
 */
ve.ui.MWSignWritingDialog.prototype.getBodyHeight = function () {
	return 600;
};

/* Registration */

ve.ui.windowFactory.register(
	ve.ui.MWSignWritingDialog
);
