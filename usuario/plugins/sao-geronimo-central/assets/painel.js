( function () {
	var corpo = document.body;
	var modo = ( window.SGCV && SGCV.modo ) || 'claro';

	document.addEventListener( 'click', function ( e ) {
		var alvo = e.target.closest ? e.target.closest( '#wp-admin-bar-sgcv-modo' ) : null;
		if ( ! alvo ) { return; }
		e.preventDefault();
		modo = modo === 'escuro' ? 'claro' : 'escuro';
		corpo.classList.remove( 'sgcv-claro', 'sgcv-escuro' );
		corpo.classList.add( 'sgcv-' + modo );

		var d = new FormData();
		d.append( 'action', 'sgcv_modo' );
		d.append( 'nonce', SGCV.nonce );
		d.append( 'modo', modo );
		fetch( SGCV.ajax, { method: 'POST', body: d, credentials: 'same-origin' } );
	} );
} )();
