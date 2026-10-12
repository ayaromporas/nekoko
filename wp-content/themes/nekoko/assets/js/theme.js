( function() {
	'use strict';
	var toggle = document.querySelector( '.menu-toggle' );
	var panel = document.getElementById( 'header-menu-panel' );
	var mobile = window.matchMedia( '(max-width: 64rem)' );

	if ( ! toggle || ! panel ) { return; }

	function setOpen( open, returnFocus ) {
		open = mobile.matches && open;
		toggle.setAttribute( 'aria-expanded', String( open ) );
		toggle.setAttribute( 'aria-label', open ? 'Zatvori meni' : 'Otvori meni' );
		panel.classList.toggle( 'is-open', open );
		panel.hidden = mobile.matches && ! open;
		if ( returnFocus && mobile.matches ) { toggle.focus(); }
	}
	toggle.addEventListener( 'click', function() {
		setOpen( toggle.getAttribute( 'aria-expanded' ) !== 'true', false );
	} );
	document.addEventListener( 'keydown', function( event ) {
		if ( event.key === 'Escape' && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
			event.preventDefault();
			setOpen( false, true );
		}
	} );
	panel.addEventListener( 'click', function( event ) {
		if ( mobile.matches && event.target.closest( 'a' ) ) { setOpen( false, true ); }
	} );
	function resetMenu() { setOpen( false, panel.contains( document.activeElement ) ); }
	if ( mobile.addEventListener ) { mobile.addEventListener( 'change', resetMenu ); }
	else { mobile.addListener( resetMenu ); }
	resetMenu();
}() );

/* Every native dialog on the site closes from its X control or its backdrop. */
( function() {
	'use strict';

	function closeDialog( dialog ) {
		if ( ! dialog || dialog.tagName !== 'DIALOG' ) { return; }
		if ( typeof dialog.close === 'function' ) { dialog.close(); }
		else { dialog.removeAttribute( 'open' ); }
	}

	document.addEventListener( 'click', function( event ) {
		var closeControl = event.target.closest( '[data-dialog-close], [data-listing-close-dialog]' );
		if ( closeControl ) {
			event.preventDefault();
			closeDialog( closeControl.closest( 'dialog' ) );
			return;
		}

		if ( event.target.tagName === 'DIALOG' ) {
			closeDialog( event.target );
		}
	} );
}() );
