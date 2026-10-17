/*!
 * VisualEditor MWSignWritingContextItem class.
 *
 * @license MIT
 */

/**
 * Context item for a SignWriting node.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWSignWritingContextItem = function VeUiMWSignWritingContextItem(
	context,
	model,
	config
) {
	// Parent constructor
	ve.ui.MWSignWritingContextItem.super.apply(
		this,
		arguments
	);
};

/* Inheritance */

OO.inheritClass(
	ve.ui.MWSignWritingContextItem,
	ve.ui.LinearContextItem
);

/* Static properties */

ve.ui.MWSignWritingContextItem.static.name =
	'mwSignWriting';

ve.ui.MWSignWritingContextItem.static.icon =
	'edit';

ve.ui.MWSignWritingContextItem.static.label =
	'SignWriting';

ve.ui.MWSignWritingContextItem.static.modelClasses = [
	ve.dm.MWSignWritingNode
];

ve.ui.MWSignWritingContextItem.static.embeddable = false;

ve.ui.MWSignWritingContextItem.static.commandName =
	'mwSignWriting';

ve.ui.MWSignWritingContextItem.static.inlineEditCommand =
	'mwSignWritingDialog';

/* Registration */

ve.ui.contextItemFactory.register(
	ve.ui.MWSignWritingContextItem
);

/*
 * Command used by the context item.
 */
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'mwSignWriting',
		'window',
		'open',
		{
			args: [ 'mwSignWritingDialog' ],
			supportedSelections: [ 'linear' ]
		}
	)
);
