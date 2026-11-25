( function() {
	'use strict';
	var toggle = document.querySelector( '.menu-toggle' );
	var panel = document.getElementById( 'header-menu-panel' );
	var mobile = window.matchMedia( '(max-width: 75rem)' );

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

/* Account control only opens its dropdown. */
(function(){
'use strict';
var account=document.querySelector('.header-account');
if(!account)return;
var button=account.querySelector('button.header-account__trigger'),dropdown=account.querySelector('.header-account__dropdown');
if(!button||!dropdown)return;
var pinned=false,holdTimer,held=false;
function open(value){account.classList.toggle('is-open',value);button.setAttribute('aria-expanded',String(value));}
button.addEventListener('click',function(){if(held){held=false;return;}pinned=!pinned;open(pinned);});
account.addEventListener('pointerenter',function(e){if(e.pointerType==='mouse')open(true);});
account.addEventListener('pointerleave',function(e){if(e.pointerType==='mouse'&&!pinned)open(false);});
button.addEventListener('pointerdown',function(e){held=false;if(e.pointerType==='touch'||e.pointerType==='pen'){holdTimer=window.setTimeout(function(){held=true;pinned=true;open(true);},500);}});
button.addEventListener('pointerup',function(){window.clearTimeout(holdTimer);});
button.addEventListener('pointercancel',function(){window.clearTimeout(holdTimer);held=false;});
button.addEventListener('contextmenu',function(e){e.preventDefault();pinned=true;open(true);});
document.addEventListener('click',function(e){if(!account.contains(e.target)){pinned=false;open(false);}});
account.addEventListener('focusout',function(e){if(!account.contains(e.relatedTarget)){pinned=false;open(false);}});
account.addEventListener('keydown',function(e){if(e.key==='Escape'){e.preventDefault();pinned=false;open(false);button.focus();}if(e.key==='ArrowDown'&&e.target===button){e.preventDefault();pinned=true;open(true);var link=dropdown.querySelector('a');if(link)link.focus();}});
}());