/*!

* VisualEditor UserInterface MWSignWritingInspector class.
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
  ve.ui.MWSignWritingInspector = function VeUiMWSignWritingInspector( config ) {
  // Parent constructor
  ve.ui.MWSignWritingInspector.super.call( this, config );

  this.iframe = null;
  this.messageHandler = null;
  };

/* Inheritance */

OO.inheritClass(
ve.ui.MWSignWritingInspector,
ve.ui.MWExtensionPreviewDialog
);

/* Static properties */

ve.ui.MWSignWritingInspector.static.name = 'mwSignWritingInspector';

ve.ui.MWSignWritingInspector.static.title = 'SignWriting';

/*

* Use the same window sizing mechanism as Math.
  */
  ve.ui.MWSignWritingInspector.static.size = 'larger';

ve.ui.MWSignWritingInspector.static.modelClasses = [
ve.dm.MWSignWritingNode
];

/* Methods */

/**

* @inheritdoc
  */
  ve.ui.MWSignWritingInspector.prototype.initialize = function () {
  // Parent method
  ve.ui.MWSignWritingInspector.super.prototype.initialize.call( this );

  /*

  * SignMaker is the complete editor.
  *
  * Put it directly into the dialog body rather than
  * using the normal extension inspector form.
    */
    this.$body.addClass( 've-ui-mwSignWritingDialog-content' );

  this.messageHandler =
  this.onSignMakerMessage.bind( this );

  window.addEventListener(
  'message',
  this.messageHandler
  );
  };

/**

* Get the current FSW source.
*
* @return {string}
  */
  ve.ui.MWSignWritingInspector.prototype.getFSW = function () {
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

* @inheritdoc
  */
  ve.ui.MWSignWritingInspector.prototype.getSetupProcess = function ( data ) {
  return ve.ui.MWSignWritingInspector.super.prototype
  .getSetupProcess.call( this, data )
  .next( function () {
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
  ve.ui.MWSignWritingInspector.prototype.getTeardownProcess = function ( data ) {
  return ve.ui.MWSignWritingInspector.super.prototype
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
  ve.ui.MWSignWritingInspector.prototype.updateMwData = function ( mwData ) {
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
  ve.ui.MWSignWritingInspector.prototype.loadSignMaker = function ( fsw ) {
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
  ve.ui.MWSignWritingInspector.prototype.onSignMakerMessage = function ( event ) {
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
    */
    mwData.body.extsrc = fsw;

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
  ve.ui.MWSignWritingInspector.prototype.getBodyHeight = function () {
  return 600;
  };

/* Registration */

ve.ui.windowFactory.register(
ve.ui.MWSignWritingInspector
);

