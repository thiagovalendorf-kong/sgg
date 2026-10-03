/* Busca da página inicial de Gestão. */
( function () {
	var busca = document.getElementById( 'sgc-hub-busca' );
	if ( busca ) {
		var limpar = function ( t ) {
			return ( t || '' ).toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).trim();
		};
		busca.addEventListener( 'input', function () {
			var q = limpar( busca.value );
			var achou = 0;
			document.querySelectorAll( '.sgc-hub__secao' ).forEach( function ( sec ) {
				if ( sec.getAttribute( 'data-fixa' ) ) {
					sec.hidden = q !== '';
					return;
				}
				var visiveis = 0;
				sec.querySelectorAll( '.sgc-hubc' ).forEach( function ( c ) {
					var ok = q === '' || limpar( c.getAttribute( 'data-busca' ) ).indexOf( q ) !== -1;
					c.hidden = ! ok;
					if ( ok ) { visiveis++; }
				} );
				sec.hidden = visiveis === 0;
				achou += visiveis;
			} );
			var vazio = document.querySelector( '.sgc-hub__vazio' );
			if ( vazio ) { vazio.hidden = q === '' || achou > 0; }
		} );
	}
} )();
