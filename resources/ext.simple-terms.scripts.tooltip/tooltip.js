( function () {
	var config = mw.config.get( 'wgSimpleTermsTippyConfig', '{}' ),
		allowHtml = mw.config.get( 'wgSimpleTermsAllowHtml', false );

	try {
		config = JSON.parse( config );
	} catch (e) {
		config = {};
	}
	config.allowHtml = allowHtml;

	// eslint-disable-next-line es/no-object-assign
	config = Object.assign( {
		inlinePositioning: true,
		interactiveBorder: 30
	}, config );

	window.tippy(
		document.querySelectorAll( '.mw-parser-output .simple-terms-tooltip' ),
		config
	);
}() );
