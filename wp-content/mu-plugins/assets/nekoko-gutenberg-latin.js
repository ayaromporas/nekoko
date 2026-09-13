( function() {
	'use strict';

	var cyrillicPattern = /[\u0400-\u04ff]/;
	var characterMap = {
		'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Ђ': 'Đ',
		'Е': 'E', 'Ж': 'Ž', 'З': 'Z', 'И': 'I', 'Ј': 'J', 'К': 'K',
		'Л': 'L', 'Љ': 'Lj', 'М': 'M', 'Н': 'N', 'Њ': 'Nj', 'О': 'O',
		'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'Ћ': 'Ć', 'У': 'U',
		'Ф': 'F', 'Х': 'H', 'Ц': 'C', 'Ч': 'Č', 'Џ': 'Dž', 'Ш': 'Š',
		'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ђ': 'đ',
		'е': 'e', 'ж': 'ž', 'з': 'z', 'и': 'i', 'ј': 'j', 'к': 'k',
		'л': 'l', 'љ': 'lj', 'м': 'm', 'н': 'n', 'њ': 'nj', 'о': 'o',
		'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'ћ': 'ć', 'у': 'u',
		'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'č', 'џ': 'dž', 'ш': 'š'
	};
	var observedDocuments = new WeakSet();

	function toLatin( value ) {
		if ( ! value || ! cyrillicPattern.test( value ) ) {
			return value;
		}

		return value.replace( /[АБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФХЦЧЏШабвгдђежзијклљмнњопрстћуфхцчџш]/g, function( character ) {
			return characterMap[ character ] || character;
		} );
	}

	function isEditableText( element ) {
		return element && (
			element.isContentEditable ||
			element.tagName === 'INPUT' ||
			element.tagName === 'TEXTAREA'
		);
	}

	function insertLatinText( event, text ) {
		var element = event.target;
		var latin = toLatin( text );

		if ( latin === text || ! isEditableText( element ) ) {
			return;
		}

		event.preventDefault();

		if ( element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' ) {
			var start = element.selectionStart;
			var end = element.selectionEnd;
			element.setRangeText( latin, start, end, 'end' );
			element.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			return;
		}

		var ownerDocument = element.ownerDocument;
		ownerDocument.execCommand( 'insertText', false, latin );
	}

	function handleBeforeInput( event ) {
		if ( event.isComposing || ! event.data ) {
			return;
		}

		insertLatinText( event, event.data );
	}

	function handlePaste( event ) {
		var clipboard = event.clipboardData;
		var text = clipboard ? clipboard.getData( 'text/plain' ) : '';

		if ( text ) {
			insertLatinText( event, text );
		}
	}

	function shouldSkipTextNode( node ) {
		var parent = node.parentElement;
		return ! parent || Boolean( parent.closest( 'script, style, code, pre, textarea, input, [contenteditable="true"], [contenteditable="plaintext-only"]' ) );
	}

	function transliterateElement( element ) {
		if ( element.nodeType === Node.TEXT_NODE ) {
			if ( ! shouldSkipTextNode( element ) ) {
				element.nodeValue = toLatin( element.nodeValue );
			}
			return;
		}

		if ( element.nodeType !== Node.ELEMENT_NODE ) {
			return;
		}

		[ 'aria-label', 'title', 'placeholder', 'data-label' ].forEach( function( attribute ) {
			if ( element.hasAttribute( attribute ) ) {
				element.setAttribute( attribute, toLatin( element.getAttribute( attribute ) ) );
			}
		} );

		var walker = element.ownerDocument.createTreeWalker( element, NodeFilter.SHOW_TEXT );
		var textNode;
		while ( ( textNode = walker.nextNode() ) ) {
			if ( ! shouldSkipTextNode( textNode ) ) {
				textNode.nodeValue = toLatin( textNode.nodeValue );
			}
		}
	}

	function attachToDocument( targetDocument ) {
		if ( ! targetDocument || observedDocuments.has( targetDocument ) ) {
			return;
		}

		observedDocuments.add( targetDocument );
		targetDocument.addEventListener( 'beforeinput', handleBeforeInput, true );
		targetDocument.addEventListener( 'paste', handlePaste, true );

		if ( targetDocument.documentElement ) {
			transliterateElement( targetDocument.documentElement );
		}

		var observer = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.addedNodes.forEach( transliterateElement );
			} );
		} );

		observer.observe( targetDocument.documentElement, { childList: true, subtree: true } );
	}

	function attachToEditorFrames() {
		document.querySelectorAll( 'iframe' ).forEach( function( frame ) {
			try {
				attachToDocument( frame.contentDocument );
				frame.addEventListener( 'load', function() {
					attachToDocument( frame.contentDocument );
				} );
			} catch ( error ) {
				// Gutenberg editor frames are same-origin; ignore unrelated frames.
			}
		} );
	}

	function start() {
		attachToDocument( document );
		attachToEditorFrames();

		new MutationObserver( attachToEditorFrames ).observe( document.documentElement, {
			childList: true,
			subtree: true
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
