/* São Gerônimo — rede de segurança de contraste do painel.
 *
 * Plugins (WooCommerce, Mercado Pago, SEO…) pintam caixas de branco e textos de
 * cinza-escuro sem saber que o painel pode estar no modo escuro. Aqui o painel
 * confere, depois de carregar, tudo o que está na tela:
 *   1) modo escuro: caixa com fundo claro  -> vira fundo escuro do painel;
 *   2) qualquer modo: texto que quase não se lê (contraste baixo) -> ganha
 *      a cor de texto certa para aquele fundo.
 * Não mexe em imagens, no editor de blocos nem na prévia do site.
 */
( function () {
	'use strict';

	var corpo = document.body;
	if ( ! corpo || ! corpo.classList.contains( 'sgcv' ) || corpo.classList.contains( 'block-editor-page' ) ) { return; }

	var IGNORA = 'img,svg,canvas,video,iframe,picture,script,style,noscript,template,option,source,' +
		'.mce-edit-area,.wp-color-result,.color-option,.sgc,.sgcv-ignora,[data-sgcv-ignora],' +
		'#wpadminbar,.media-modal-backdrop,.wp-editor-area-fake,.swatch,.sgp-cor,.cor-amostra';
	var PULA_TAG = /^(SCRIPT|STYLE|NOSCRIPT|SVG|PATH|IMG|CANVAS|VIDEO|IFRAME|PICTURE|SOURCE|OPTION|TEMPLATE|HEAD|META|LINK|BR|HR)$/;
	var MIN = 3.2;                 // contraste mínimo aceito
	var marcados = [];             // [elemento, tipo, valorAnterior]
	var fila = [];
	var rodando = false;

	function escuro() { return corpo.classList.contains( 'sgcv-escuro' ); }

	function parse( c ) {
		var m = /rgba?\(([^)]+)\)/.exec( c );
		if ( ! m ) { return null; }
		var v = m[ 1 ].split( /[ ,\/]+/ ).filter( Boolean ).map( Number );
		return { r: v[ 0 ], g: v[ 1 ], b: v[ 2 ], a: v.length > 3 ? v[ 3 ] : 1 };
	}
	function lum( c ) {
		var f = function ( x ) { x /= 255; return x <= 0.03928 ? x / 12.92 : Math.pow( ( x + 0.055 ) / 1.055, 2.4 ); };
		return 0.2126 * f( c.r ) + 0.7152 * f( c.g ) + 0.0722 * f( c.b );
	}
	function mistura( topo, base ) {
		return { r: topo.r * topo.a + base.r * ( 1 - topo.a ), g: topo.g * topo.a + base.g * ( 1 - topo.a ), b: topo.b * topo.a + base.b * ( 1 - topo.a ), a: 1 };
	}
	function razao( a, b ) {
		var x = lum( a ), y = lum( b );
		return ( Math.max( x, y ) + 0.05 ) / ( Math.min( x, y ) + 0.05 );
	}

	/* Cor de fundo de verdade de um elemento (soma as camadas de cima para baixo). */
	function fundoReal( el ) {
		var camadas = [];
		for ( var e = el; e && e.nodeType === 1; e = e.parentElement ) {
			var cs = getComputedStyle( e );
			var bg = parse( cs.backgroundColor );
			if ( bg && bg.a > 0 ) { camadas.push( bg ); if ( bg.a >= 1 ) { break; } }
		}
		var base = escuro() ? { r: 14, g: 20, b: 38, a: 1 } : { r: 252, g: 250, b: 245, a: 1 };
		for ( var i = camadas.length - 1; i >= 0; i-- ) { base = mistura( camadas[ i ], base ); }
		return base;
	}

	function marca( el, tipo, anterior ) { marcados.push( [ el, tipo, anterior ] ); }

	function ehCampo( el ) { return /^(INPUT|TEXTAREA|SELECT)$/.test( el.tagName ); }

	/* Passo 1 (só no escuro): caixas de fundo claro viram fundo escuro do painel. */
	function fundo( el ) {
		if ( el.hasAttribute( 'data-sgcv-fb' ) ) { return; }
		var cs = getComputedStyle( el );
		var bg = parse( cs.backgroundColor );
		if ( ! bg || bg.a < 0.9 || lum( bg ) < 0.5 ) { return; }
		if ( el.tagName === 'INPUT' && /^(checkbox|radio|color|range|file|hidden|image)$/.test( el.type ) ) { return; }
		if ( el.matches( '.button-primary, .button-primary *, .sgp-btn--principal' ) ) { return; }
		el.setAttribute( 'data-sgcv-fb', ehCampo( el ) ? 'campo' : 'cartao' );
		marca( el, 'fb', null );
	}

	/* Passo 2: texto ilegível ganha a cor certa. */
	function texto( el ) {
		if ( el.hasAttribute( 'data-sgcv-ft' ) ) { return; }
		var tem = ehCampo( el );
		if ( ! tem ) {
			for ( var i = 0; i < el.childNodes.length; i++ ) {
				var n = el.childNodes[ i ];
				if ( n.nodeType === 3 && n.textContent.trim() ) { tem = true; break; }
			}
		}
		if ( ! tem ) { return; }
		var cs = getComputedStyle( el );
		if ( cs.visibility === 'hidden' || cs.display === 'none' ) { return; }
		var c = parse( cs.color );
		if ( ! c ) { return; }
		var f = fundoReal( el );
		var t = c.a < 1 ? mistura( c, f ) : c;
		if ( razao( t, f ) >= MIN ) { return; }
		// escolhe a cor (clara ou escura) que mais contrasta com o fundo
		var claro = { r: 233, g: 236, b: 246 }, escura = { r: 27, g: 42, b: 74 };
		var novo = razao( claro, f ) >= razao( escura, f ) ? claro : escura;
		el.setAttribute( 'data-sgcv-ft', '1' );
		el.style.setProperty( 'color', 'rgb(' + novo.r + ',' + novo.g + ',' + novo.b + ')', 'important' );
		marca( el, 'ft', null );
	}

	function elementos( raiz ) {
		var lista = [];
		if ( raiz.nodeType !== 1 ) { return lista; }
		if ( raiz.matches && raiz.matches( IGNORA ) ) { return lista; }
		if ( ! PULA_TAG.test( raiz.tagName ) ) { lista.push( raiz ); }
		var todos = raiz.querySelectorAll( '*' );
		for ( var i = 0; i < todos.length; i++ ) {
			var e = todos[ i ];
			if ( PULA_TAG.test( e.tagName ) ) { continue; }
			if ( e.closest( IGNORA ) ) { continue; }
			lista.push( e );
		}
		return lista;
	}

	function processa( lista ) {
		var dark = escuro();
		if ( dark ) { lista.forEach( fundo ); }
		lista.forEach( texto );
	}

	/* Trabalha aos poucos para não travar a tela. */
	function esvazia() {
		rodando = true;
		var lote = fila.splice( 0, 450 );
		processa( lote );
		if ( fila.length ) {
			( window.requestAnimationFrame || setTimeout )( esvazia );
		} else {
			rodando = false;
		}
	}
	function enfileira( lista ) {
		for ( var i = 0; i < lista.length; i++ ) { fila.push( lista[ i ] ); }
		if ( ! rodando ) { ( window.requestAnimationFrame || setTimeout )( esvazia ); }
	}

	/* Ao trocar claro/escuro: desfaz o que foi marcado e confere tudo de novo. */
	function refaz() {
		marcados.forEach( function ( m ) {
			var el = m[ 0 ];
			el.removeAttribute( m[ 1 ] === 'fb' ? 'data-sgcv-fb' : 'data-sgcv-ft' );
			if ( m[ 1 ] === 'ft' ) { el.style.removeProperty( 'color' ); }
		} );
		marcados = [];
		fila = [];
		enfileira( elementos( corpo ) );
	}

	function inicia() {
		// 1) a área principal é conferida de uma vez, antes de a tela aparecer (sem clarão branco);
		// 2) o resto (menus, barras) vai aos poucos, porque o CSS já cobre quase tudo ali.
		var principal = document.getElementById( 'wpbody-content' );
		if ( principal ) {
			processa( elementos( principal ) );
			enfileira( elementos( corpo ).filter( function ( e ) { return ! principal.contains( e ); } ) );
		} else {
			enfileira( elementos( corpo ) );
		}
		// conteúdo que entra depois (abas, janelas, select2, variações carregadas por AJAX)
		var espera = null, novos = [];
		new MutationObserver( function ( lista ) {
			lista.forEach( function ( m ) {
				if ( m.type === 'childList' ) {
					m.addedNodes.forEach( function ( n ) { if ( n.nodeType === 1 ) { novos.push( n ); } } );
				}
			} );
			if ( novos.length && ! espera ) {
				espera = setTimeout( function () {
					var pacote = novos; novos = []; espera = null;
					pacote.forEach( function ( n ) { if ( document.contains( n ) ) { enfileira( elementos( n ) ); } } );
				}, 180 );
			}
		} ).observe( corpo, { childList: true, subtree: true } );

		// troca do botão claro/escuro
		var anterior = escuro();
		new MutationObserver( function () {
			if ( escuro() !== anterior ) { anterior = escuro(); setTimeout( refaz, 30 ); }
		} ).observe( corpo, { attributes: true, attributeFilter: [ 'class' ] } );

		// passagens extras para o que carrega tarde (editores, plugins)
		window.addEventListener( 'load', function () {
			setTimeout( function () { enfileira( elementos( corpo ) ); }, 400 );
			setTimeout( function () { enfileira( elementos( corpo ) ); }, 1800 );
		} );
	}

	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', inicia ); } else { inicia(); }
}() );
