/**
 * Free Link Shortener – admin scripts.
 * Handles copy-to-clipboard, metabox AJAX creation, and AJAX row-action shorten.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( e ) {
		var copyBtn = e.target.closest( '.fls-copy-btn' );
		if ( copyBtn ) {
			e.preventDefault();
			copyToClipboard( copyBtn );
			return;
		}

		var createBtn = e.target.closest( '#fls-create-for-post' );
		if ( createBtn ) {
			e.preventDefault();
			createForPost( createBtn );
			return;
		}

		var shortenBtn = e.target.closest( '.fls-shorten-btn' );
		if ( shortenBtn ) {
			e.preventDefault();
			shortenRowAction( shortenBtn );
		}
	} );

	/**
	 * Copy the button's data-url to the clipboard with visual feedback.
	 *
	 * @param {HTMLElement} btn The clicked element.
	 */
	function copyToClipboard( btn ) {
		var url = btn.getAttribute( 'data-url' );
		var done = function () {
			var original = btn.textContent;
			btn.textContent = btn.getAttribute( 'data-copied' );
			setTimeout( function () {
				btn.textContent = original;
			}, 1500 );
		};

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( url ).then( done );
		} else {
			var ta = document.createElement( 'textarea' );
			ta.value = url;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild( ta );
			ta.select();
			try {
				document.execCommand( 'copy' );
				done();
			} catch ( err ) {}
			document.body.removeChild( ta );
		}
	}

	/**
	 * Create a short link for the current post via AJAX (metabox).
	 *
	 * @param {HTMLElement} btn The clicked button.
	 */
	function createForPost( btn ) {
		btn.disabled = true;
		btn.insertAdjacentHTML(
			'afterend',
			'<span class="spinner is-active fls-spinner" style="float:none;margin:0 6px;"></span>'
		);

		ajaxCreate( btn.getAttribute( 'data-post' ) )
			.then( function ( res ) {
				removeSpinner( btn );
				btn.disabled = false;
				if ( res.success ) {
					document.getElementById( 'fls-metabox-result' ).innerHTML =
						res.data.html;
				} else {
					alert( errorMessage( res ) );
				}
			} )
			.catch( function () {
				removeSpinner( btn );
				btn.disabled = false;
				alert( FLS.errorText );
			} );
	}

	/**
	 * Shorten a link from the posts/products list via AJAX (row action).
	 *
	 * @param {HTMLElement} link The clicked row-action link.
	 */
	function shortenRowAction( link ) {
		// Show an inline loader; keep layout from jumping.
		link.style.pointerEvents = 'none';
		link.style.opacity = '0.5';
		link.insertAdjacentHTML(
			'afterend',
			'<span class="spinner is-active fls-spinner" style="float:none;margin:0 4px;vertical-align:middle;"></span>'
		);

		ajaxCreate( link.getAttribute( 'data-post' ) )
			.then( function ( res ) {
				removeSpinner( link );
				if ( res.success ) {
					// Swap the "Shorten" link for a "Copy short link" action.
					var copy = document.createElement( 'a' );
					copy.href = '#';
					copy.className = 'fls-copy-btn';
					copy.setAttribute( 'data-url', res.data.short_url );
					copy.setAttribute(
						'data-copied',
						link.getAttribute( 'data-copied' )
					);
					copy.textContent = link.getAttribute( 'data-copy-label' );
					link.parentNode.replaceChild( copy, link );
				} else {
					restoreLink( link );
					alert( errorMessage( res ) );
				}
			} )
			.catch( function () {
				removeSpinner( link );
				restoreLink( link );
				alert( FLS.errorText );
			} );
	}

	/**
	 * Shared AJAX request to create a short link for a post.
	 *
	 * @param {string} postId The post ID.
	 * @return {Promise}
	 */
	function ajaxCreate( postId ) {
		var data = new FormData();
		data.append( 'action', 'fls_create_for_post' );
		data.append( 'post_id', postId );
		data.append( 'nonce', FLS.nonce );

		return fetch( FLS.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} ).then( function ( r ) {
			return r.json();
		} );
	}

	/**
	 * Remove the spinner that follows an element.
	 *
	 * @param {HTMLElement} el The reference element.
	 */
	function removeSpinner( el ) {
		var spinner = el.parentNode
			? el.parentNode.querySelector( '.fls-spinner' )
			: null;
		if ( spinner ) {
			spinner.parentNode.removeChild( spinner );
		}
	}

	/**
	 * Re-enable a row-action link after a failure.
	 *
	 * @param {HTMLElement} link The link element.
	 */
	function restoreLink( link ) {
		link.style.pointerEvents = '';
		link.style.opacity = '';
	}

	/**
	 * Extract a readable error message from an AJAX response.
	 *
	 * @param {Object} res The parsed response.
	 * @return {string}
	 */
	function errorMessage( res ) {
		return res.data && res.data.message ? res.data.message : FLS.errorText;
	}
} )();
