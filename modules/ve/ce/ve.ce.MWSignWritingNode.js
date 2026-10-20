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

	// Never register twice for the same node
	this.removeSignListener();

	this.onSignMouseDown = function ( e ) {
		var hit = null,
			model = node.model;

		// The node was destroyed without a teardown (this happens to the
		// preview node inside the dialog): stop listening.
		if ( !model || !node.$element ) {
			node.removeSignListener();
			return;
		}

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
			model.signTarget = null;
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
		model.signTarget = { fsw: fsw, occurrence: occurrence };
	};

	document.addEventListener( 'mousedown', this.onSignMouseDown, true );
};

/**
 * Stop listening for presses. Safe to call more than once.
 */
ve.ce.MWSignWritingNode.prototype.removeSignListener = function () {
	if ( this.onSignMouseDown ) {
		document.removeEventListener( 'mousedown', this.onSignMouseDown, true );
	}
	this.onSignMouseDown = null;
};

/**
 * @inheritdoc
 */
ve.ce.MWSignWritingNode.prototype.onTeardown = function () {
	this.removeSignListener();

	// Parent method
	ve.ce.MWSignWritingNode.super.prototype.onTeardown.apply( this, arguments );
};

/**
 * Also clean up when the node is destroyed without a teardown,
 * as the dialog's preview node is.
 *
 * @inheritdoc
 */
ve.ce.MWSignWritingNode.prototype.destroy = function () {
	this.removeSignListener();

	// Parent method
	ve.ce.MWSignWritingNode.super.prototype.destroy.apply( this, arguments );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWSignWritingNode );
