( function() {
	'use strict';
	var toggle = document.querySelector( '.menu-toggle' );
	var panel = document.getElementById( 'header-menu-panel' );
	if ( ! toggle || ! panel ) { return; }
	var mobile = window.matchMedia( '(max-width: 64rem)' );

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
