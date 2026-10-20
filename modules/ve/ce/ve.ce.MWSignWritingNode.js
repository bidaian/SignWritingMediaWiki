/*!
 * VisualEditor ContentEditable MWSignWritingNode class.
 *
 * @license MIT
 */

/**
 * ContentEditable MediaWiki SignWriting node.
 *
 * @class
 * @extends ve.ce.MWInlineExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWSignWritingNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWSignWritingNode = function VeCeMWSignWritingNode() {
	// Parent constructor
	ve.ce.MWSignWritingNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass(
	ve.ce.MWSignWritingNode,
	ve.ce.MWInlineExtensionNode
);

/* Static properties */

ve.ce.MWSignWritingNode.static.name = 'mwSignWriting';

ve.ce.MWSignWritingNode.static.primaryCommandName = 'mwSignWriting';

/* Methods */

/**
 * Remember which sign was pressed, so the dialog can edit just that sign.
 *
 * VisualEditor draws a highlight overlay over focusable nodes, and mouse
 * events on that overlay do not necessarily pass through the node or the
 * surface element. So listen on the document in the capture phase, which
 * sees every mousedown first, and find the sign under the pointer by
 * coordinates.
 *
 * @inheritdoc
 */
ve.ce.MWSignWritingNode.prototype.onSetup = function () {
	var node = this;

	// Parent method
	ve.ce.MWSignWritingNode.super.prototype.onSetup.apply( this, arguments );

	this.onSignMouseDown = function ( e ) {
		var hit = null;

		node.$element.find( '.signwriting-item' ).each( function () {
			var r = this.getBoundingClientRect();
			if (
				e.clientX >= r.left && e.clientX <= r.right &&
				e.clientY >= r.top && e.clientY <= r.bottom
			) {
				hit = this;
				return false;
			}
		} );

		if ( !hit ) {
			// Press was elsewhere: forget any earlier target.
			node.model.signTarget = null;
			return;
		}

		var fsw = hit.getAttribute( 'data-fsw' );

		// Which occurrence of this exact FSW (a sign can repeat)
		var occurrence = node.$element.find( '.signwriting-item' )
			.filter( function () {
				return this.getAttribute( 'data-fsw' ) === fsw;
			} )
			.index( hit );

		// Plain property on the DM node: not an attribute, so it never
		// enters a transaction or the saved wikitext.
		node.model.signTarget = { fsw: fsw, occurrence: occurrence };
	};

	document.addEventListener( 'mousedown', this.onSignMouseDown, true );
};

/**
 * @inheritdoc
 */
ve.ce.MWSignWritingNode.prototype.onTeardown = function () {
	if ( this.onSignMouseDown ) {
		document.removeEventListener( 'mousedown', this.onSignMouseDown, true );
	}
	this.onSignMouseDown = null;

	// Parent method
	ve.ce.MWSignWritingNode.super.prototype.onTeardown.apply( this, arguments );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWSignWritingNode );
