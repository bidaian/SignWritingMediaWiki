
/*!
 * VisualEditor SignWriting insertion tool.
 *
 * @license MIT
 */

( function () {
	'use strict';

	/**
	 * Tool for inserting a new SignWriting sign.
	 *
	 * @class
	 * @extends ve.ui.Tool
	 */
	ve.ui.MWSignWritingInsertTool = function VeUiMWSignWritingInsertTool() {
		ve.ui.MWSignWritingInsertTool.super.apply( this, arguments );
	};

	OO.inheritClass(
		ve.ui.MWSignWritingInsertTool,
		ve.ui.Tool
	);

	ve.ui.MWSignWritingInsertTool.static.name = 'mwSignWritingInsert';
	ve.ui.MWSignWritingInsertTool.static.group = 'insert';
	ve.ui.MWSignWritingInsertTool.static.icon = 'edit';
	ve.ui.MWSignWritingInsertTool.static.title = 'Insert SignWriting';
	ve.ui.MWSignWritingInsertTool.static.commandName = 'mwSignWritingInsert';

	ve.ui.toolFactory.register(
		ve.ui.MWSignWritingInsertTool
	);

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'mwSignWritingInsert',
		'window',
		'open',
		{
			args: [
				'mwSignWritingDialog',
				{ insertion: true }
			],
			supportedSelections: [ 'linear' ]
		}
	)
);
	// Diagnostic: confirm this module executed.
	console.log(
		'[SignWriting] Insert tool registered:',
		ve.ui.toolFactory.lookup( 'mwSignWritingInsert' )
	);

}() );

