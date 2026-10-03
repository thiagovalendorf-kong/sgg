( function () {
	var corpo = document.body;
	var modo = ( window.SGCV && SGCV.modo ) || 'claro';

	// Alterna claro/escuro e guarda a escolha.
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

	// Cabeçalhos de seção do menu "Gestão": só texto, sem clique nem foco.
	document.querySelectorAll( '#adminmenu li.sgc-secao > a' ).forEach( function ( a ) {
		a.removeAttribute( 'href' );
		a.setAttribute( 'tabindex', '-1' );
		a.setAttribute( 'aria-disabled', 'true' );
		a.setAttribute( 'role', 'presentation' );
	} );

	// Telas recolhidas para dentro de "Gestão": o item de topo fica marcado e aberto.
	var gestao = document.getElementById( 'toplevel_page_sgc-loja' );
	if ( gestao && gestao.querySelector( '.wp-submenu li.current' ) ) {
		var topo = gestao.querySelector( ':scope > a.menu-top' );
		document.querySelectorAll( '#adminmenu li.wp-has-current-submenu' ).forEach( function ( li ) {
			if ( li !== gestao ) {
				li.classList.remove( 'wp-has-current-submenu', 'wp-menu-open' );
				li.classList.add( 'wp-not-current-submenu' );
			}
		} );
		gestao.classList.remove( 'wp-not-current-submenu' );
		gestao.classList.add( 'wp-has-current-submenu', 'wp-menu-open' );
		if ( topo ) {
			topo.classList.remove( 'wp-not-current-submenu' );
			topo.classList.add( 'wp-has-current-submenu', 'wp-menu-open' );
		}
	}

	// Busca da página "Gestão": filtra os cartões.
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
