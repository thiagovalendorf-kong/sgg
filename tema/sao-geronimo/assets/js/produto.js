/* Página do produto: troca de foto, quantidade e cálculo de frete. */
( function () {
	'use strict';

	var $ = function ( s, r ) { return ( r || document ).querySelector( s ); };
	var $$ = function ( s, r ) { return Array.prototype.slice.call( ( r || document ).querySelectorAll( s ) ); };
	var esc = function ( s ) {
		return String( s ).replace( /[&<>"]/g, function ( m ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ m ];
		} );
	};

	/* ----- quantidade ----- */
	var qtd = $( '.qtd' );
	if ( qtd ) {
		var campo = $( '[data-qtd-campo]', qtd );
		var visor = $( '[data-qtd-v]', qtd );
		qtd.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( '[data-qtd]' );
			if ( ! b ) { return; }
			var v = parseInt( campo.value, 10 ) || 1;
			var max = parseInt( b.getAttribute( 'data-max' ) || $( '[data-qtd="+"]', qtd ).getAttribute( 'data-max' ) || '0', 10 );
			v += b.getAttribute( 'data-qtd' ) === '+' ? 1 : -1;
			if ( v < 1 ) { v = 1; }
			if ( max && v > max ) { v = max; }
			campo.value = v;
			visor.textContent = v;
		} );
	}

	/* ----- frete ----- */
	var form = $( '[data-frete]' );
	if ( ! form || ! window.SG_PRODUTO ) { return; }
	var res = $( '[data-frete-res]' );
	var cep = form.elements.cep;

	cep.addEventListener( 'input', function () {
		var n = cep.value.replace( /\D/g, '' ).slice( 0, 8 );
		cep.value = n.length > 5 ? n.slice( 0, 5 ) + '-' + n.slice( 5 ) : n;
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var n = cep.value.replace( /\D/g, '' );
		if ( n.length !== 8 ) {
			res.innerHTML = '<div style="color:#b4341f">Informe um CEP com 8 dígitos.</div>';
			return;
		}
		var q = $( '[data-qtd-campo]' );
		var dados = new FormData();
		dados.append( 'action', 'sg_frete' );
		dados.append( 'nonce', window.SG_PRODUTO.nonce );
		dados.append( 'produto', form.getAttribute( 'data-produto' ) );
		dados.append( 'qtd', q ? q.value : '1' );
		dados.append( 'cep', n );

		res.innerHTML = '<div><span>Calculando…</span></div>';
		fetch( window.SG_PRODUTO.ajax, { method: 'POST', body: dados, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( j ) {
				if ( ! j || ! j.success ) {
					res.innerHTML = '<div style="color:#b4341f">' + esc( ( j && j.data && j.data.msg ) || 'Não consegui calcular agora.' ) + '</div>';
					return;
				}
				var d = j.data;
				if ( ! d.opcoes.length ) {
					res.innerHTML = '<div><span>' + esc( d.aviso ) + '</span></div>' +
						( d.zap ? '<div><a href="' + esc( d.zap ) + '" target="_blank" rel="noopener">Perguntar pelo WhatsApp</a></div>' : '' );
					return;
				}
				res.innerHTML = d.opcoes.map( function ( o ) {
					return '<div><span>' + esc( o.nome ) + '</span><b>' + esc( o.preco ) + '</b></div>';
				} ).join( '' );
			} )
			.catch( function () {
				res.innerHTML = '<div style="color:#b4341f">Não consegui calcular agora. Tente de novo.</div>';
			} );
	} );
} )();
