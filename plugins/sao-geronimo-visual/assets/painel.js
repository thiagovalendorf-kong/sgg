( function () {
	var corpo = document.body;
	var modo = ( window.SGV && SGV.modo ) || 'claro';

	function aplicar( m ) {
		modo = m;
		corpo.classList.remove( 'sgv-claro', 'sgv-escuro' );
		corpo.classList.add( 'sgv-' + m );
	}

	document.addEventListener( 'click', function ( e ) {
		var alvo = e.target.closest ? e.target.closest( '#wp-admin-bar-sgv-modo' ) : null;
		if ( ! alvo ) {
			return;
		}
		e.preventDefault();
		var novo = modo === 'escuro' ? 'claro' : 'escuro';
		aplicar( novo );

		var dados = new FormData();
		dados.append( 'action', 'sgv_modo' );
		dados.append( 'nonce', SGV.nonce );
		dados.append( 'modo', novo );
		fetch( SGV.ajax, { method: 'POST', body: dados, credentials: 'same-origin' } );
	} );
} )();
