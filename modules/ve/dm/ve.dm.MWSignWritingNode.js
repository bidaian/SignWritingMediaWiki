/*!
 * VisualEditor DataModel MWSignWritingNode class.
 *
 * @license MIT
 */

/**
 * DataModel MediaWiki SignWriting node.
 *
 * @class
 * @extends ve.dm.MWInlineExtensionNode
 *
 * @constructor
 * @param {Object} [element]
 */
ve.dm.MWSignWritingNode = function VeDmMWSignWritingNode() {
	// Parent constructor
	ve.dm.MWSignWritingNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass(
	ve.dm.MWSignWritingNode,
	ve.dm.MWInlineExtensionNode
);

/* Static properties */

ve.dm.MWSignWritingNode.static.name = 'mwSignWriting';

ve.dm.MWSignWritingNode.static.extensionName = 'signwriting';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWSignWritingNode );
