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

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWSignWritingNode );
