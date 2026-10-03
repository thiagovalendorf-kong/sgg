/* Página do produto: abas/acordeão, "ler mais", variações em botões, quantidade. */
( function () {
	'use strict';
	var $ = function ( s, r ) { return ( r || document ).querySelector( s ); };
	var $$ = function ( s, r ) { return Array.prototype.slice.call( ( r || document ).querySelectorAll( s ) ); };
	var celular = window.matchMedia ? window.matchMedia( '(max-width: 768px)' ) : { matches: false };

	/* ------------------------------------------------------------ abas */
	function abas( raiz ) {
		var lista = $$( '.sg-abas__aba', raiz );
		var paineis = $$( '.sg-aba', raiz );
		if ( ! lista.length ) { return; }
		raiz.classList.add( 'sg-js' );

		function painel( k ) { return $( '.sg-aba[data-aba="' + k + '"]', raiz ); }

		function abrir( k, foco ) {
			paineis.forEach( function ( p ) {
				var on = p.getAttribute( 'data-aba' ) === k;
				if ( ! celular.matches ) { p.classList.toggle( 'is-aberta', on ); }
				else if ( on ) { p.classList.add( 'is-aberta' ); }
				sincPainel( p );
			} );
			lista.forEach( function ( b ) {
				var on = b.getAttribute( 'data-aba' ) === k;
				b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				b.tabIndex = on ? 0 : -1;
				if ( on && foco ) { b.focus(); }
			} );
		}
		function sincPainel( p ) {
			var cab = $( '.sg-aba__cab', p );
			cab.setAttribute( 'aria-expanded', p.classList.contains( 'is-aberta' ) ? 'true' : 'false' );
		}
		function modo() {
			paineis.forEach( function ( p ) {
				var k = p.getAttribute( 'data-aba' );
				if ( celular.matches ) {
					p.setAttribute( 'role', 'region' );
					p.setAttribute( 'aria-labelledby', 'cab-' + k );
					p.removeAttribute( 'tabindex' );
				} else {
					p.setAttribute( 'role', 'tabpanel' );
					p.setAttribute( 'aria-labelledby', 'aba-' + k );
					p.tabIndex = 0;
				}
			} );
			var atual = lista.filter( function ( b ) { return b.getAttribute( 'aria-selected' ) === 'true'; } )[ 0 ] || lista[ 0 ];
			var k = atual.getAttribute( 'data-aba' );
			if ( ! celular.matches ) { paineis.forEach( function ( p ) { p.classList.remove( 'is-aberta' ); } ); }
			abrir( k, false );
		}

		lista.forEach( function ( b, i ) {
			b.addEventListener( 'click', function () { abrir( b.getAttribute( 'data-aba' ), false ); } );
			b.addEventListener( 'keydown', function ( e ) {
				var n = -1;
				if ( e.key === 'ArrowRight' ) { n = ( i + 1 ) % lista.length; }
				else if ( e.key === 'ArrowLeft' ) { n = ( i - 1 + lista.length ) % lista.length; }
				else if ( e.key === 'Home' ) { n = 0; }
				else if ( e.key === 'End' ) { n = lista.length - 1; }
				if ( n > -1 ) { e.preventDefault(); abrir( lista[ n ].getAttribute( 'data-aba' ), true ); }
			} );
		} );
		paineis.forEach( function ( p ) {
			$( '.sg-aba__cab', p ).addEventListener( 'click', function () {
				p.classList.toggle( 'is-aberta' );
				sincPainel( p );
			} );
		} );

		// Links para #reviews / #comments abrem a aba de avaliações.
		function porHash( rolar ) {
			var h = location.hash;
			if ( ! /^#(reviews|comments|respond|tab-reviews|comment-\d+)/.test( h ) || ! painel( 'reviews' ) ) { return; }
			abrir( 'reviews', false );
			painel( 'reviews' ).classList.add( 'is-aberta' );
			sincPainel( painel( 'reviews' ) );
			if ( rolar ) { raiz.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
		}
		document.addEventListener( 'click', function ( e ) {
			var a = e.target.closest ? e.target.closest( 'a[href="#reviews"], a.woocommerce-review-link' ) : null;
			if ( a && painel( 'reviews' ) ) {
				e.preventDefault();
				history.replaceState( null, '', '#reviews' );
				porHash( true );
			}
		} );
		if ( celular.addEventListener ) { celular.addEventListener( 'change', modo ); }
		modo();
		abrir( lista[ 0 ].getAttribute( 'data-aba' ), false );
		porHash( false );
	}

	/* ------------------------------------------------------- ler mais */
	function lerMais( box ) {
		var txt = $( '.pdp__curta-txt', box );
		if ( ! txt ) { return; }
		box.classList.add( 'sg-js' );
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'pdp__curta-mais';
		btn.setAttribute( 'aria-controls', 'pdp-curta' );
		btn.setAttribute( 'aria-expanded', 'false' );
		btn.textContent = 'Ler mais';
		btn.hidden = true;
		box.appendChild( btn );
		function medir() {
			if ( box.classList.contains( 'is-aberta' ) ) { return; }
			btn.hidden = ! ( txt.scrollHeight > txt.clientHeight + 2 );
		}
		btn.addEventListener( 'click', function () {
			var on = box.classList.toggle( 'is-aberta' );
			btn.setAttribute( 'aria-expanded', on ? 'true' : 'false' );
			btn.textContent = on ? 'Ler menos' : 'Ler mais';
			if ( ! on ) { medir(); }
		} );
		medir();
		window.addEventListener( 'resize', medir );
		if ( document.fonts && document.fonts.ready ) { document.fonts.ready.then( medir ); }
	}

	/* ------------------------------------------------------ quantidade */
	function quantidade( q ) {
		var inp = $( 'input.qty', q );
		if ( ! inp || inp.type === 'hidden' || q.classList.contains( 'sg-qtd' ) ) { return; }
		q.classList.add( 'sg-qtd' );
		function mk( sinal, rot ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'sg-qtd__bt';
			b.setAttribute( 'aria-label', rot );
			b.textContent = sinal > 0 ? '+' : '−';
			b.addEventListener( 'click', function () {
				var passo = parseFloat( inp.step ) || 1;
				var min = inp.min !== '' ? parseFloat( inp.min ) : 0;
				var max = inp.max !== '' ? parseFloat( inp.max ) : Infinity;
				var v = ( parseFloat( inp.value ) || 0 ) + sinal * passo;
				inp.value = Math.min( max, Math.max( min, v ) );
				inp.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
			return b;
		}
		q.insertBefore( mk( -1, 'Diminuir quantidade' ), q.firstChild );
		q.appendChild( mk( 1, 'Aumentar quantidade' ) );
	}

	/* ------------------------------------------------------ variações */
	var CORES = {
		branc: '#ffffff', pret: '#1a1a1a', vermelh: '#c62828', azul: '#1e40af', verd: '#2e7d32', amarel: '#f2c230',
		ros: '#f48fb1', rox: '#6a1b9a', lil: '#b39ddb', laranj: '#ef6c00', marrom: '#6d4c41', dourad: '#d6a32f',
		pratead: '#b0b7c0', cinz: '#9e9e9e', beg: '#e3d3b4', violet: '#7e57c2', turquesa: '#26a69a', vinho: '#6d1b2b',
		bord: '#6d1b2b', nude: '#e8c9b0', cream: '#f5efe0', crem: '#f5efe0', ciano: '#00acc1', natural: '#d8c9a8',
		bronze: '#a9743f', cobre: '#b87333', transparente: '#e8eef5', incolor: '#e8eef5', ambar: '#d98e04', salmao: '#f4a58a'
	};
	function semAcento( t ) { return String( t ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase().trim(); }
	function corDe( nome ) {
		var n = semAcento( nome ).replace( /^off\s*/, '' );
		for ( var k in CORES ) { if ( n.indexOf( k ) === 0 ) { return CORES[ k ]; } }
		return '';
	}
	function moeda( h ) { return h; }

	function variacoes( form ) {
		var selects = $$( '.variations select', form );
		if ( ! selects.length ) { return; }
		var dados = [];
		try { dados = JSON.parse( form.getAttribute( 'data-product_variations' ) || '[]' ) || []; } catch ( e ) { dados = []; }
		var porId = {};
		dados.forEach( function ( v ) { porId[ v.variation_id ] = v; } );
		var botao = $( '.single_add_to_cart_button', form );
		var rotuloBotao = botao ? botao.textContent.trim() : '';
		var resumo = $( '.summary' ) || document;
		var precoEl = $( '.price', resumo );
		var condEl = $( '[data-sg-cond]', resumo );
		var offEl = $( '[data-sg-off]', resumo );
		var original = {
			preco: precoEl ? precoEl.innerHTML : '',
			cond: condEl ? condEl.textContent : '',
			off: offEl ? offEl.textContent : '',
			offOculto: offEl ? offEl.hidden : true
		};
		var grupos = [];

		selects.forEach( function ( sel ) {
			var opcoes = $$( 'option', sel ).filter( function ( o ) { return o.value !== ''; } ).map( function ( o ) { return { v: o.value, t: o.textContent.trim() }; } );
			var tr = sel.closest( 'tr' );
			var rotulo = tr ? $( 'th label, th', tr ) : null;
			var nomeAttr = rotulo ? rotulo.textContent.trim() : '';
			if ( opcoes.length < 1 || opcoes.length > 10 ) { return; }
			var conhecidas = opcoes.filter( function ( o ) { return corDe( o.t ); } ).length;
			var eCor = /(^|\s)(cor|color|cores)(\s|$)/i.test( semAcento( nomeAttr ) ) ? conhecidas > 0 : conhecidas >= Math.ceil( opcoes.length * 0.6 );

			var g = document.createElement( 'div' );
			g.className = 'sg-chips';
			g.setAttribute( 'role', 'radiogroup' );
			if ( rotulo && rotulo.id ) { g.setAttribute( 'aria-labelledby', rotulo.id ); } else { g.setAttribute( 'aria-label', nomeAttr ); }
			var chips = opcoes.map( function ( o ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'sg-chip';
				b.setAttribute( 'role', 'radio' );
				b.setAttribute( 'aria-checked', 'false' );
				b.setAttribute( 'data-valor', o.v );
				if ( eCor && corDe( o.t ) ) {
					var bol = document.createElement( 'i' );
					bol.className = 'sg-chip__cor';
					bol.style.background = corDe( o.t );
					bol.setAttribute( 'aria-hidden', 'true' );
					b.appendChild( bol );
				}
				var s = document.createElement( 'span' );
				s.textContent = o.t;
				b.appendChild( s );
				b.addEventListener( 'click', function () {
					if ( b.getAttribute( 'aria-disabled' ) === 'true' ) { return; }
					sel.value = sel.value === o.v ? '' : o.v;
					sel.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					sincAgora();
				} );
				return { el: b, v: o.v, t: o.t };
			} );
			chips.forEach( function ( c ) { g.appendChild( c.el ); } );
			g.addEventListener( 'keydown', function ( e ) {
				var vivos = chips.filter( function ( c ) { return c.el.getAttribute( 'aria-disabled' ) !== 'true'; } );
				var i = vivos.map( function ( c ) { return c.el; } ).indexOf( document.activeElement );
				var n = -1;
				if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) { n = i + 1; }
				else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) { n = i - 1; }
				if ( n > -1 && vivos.length ) { e.preventDefault(); vivos[ ( n + vivos.length ) % vivos.length ].el.focus(); }
			} );
			sel.parentNode.insertBefore( g, sel );
			sel.classList.add( 'sg-select-oculto' );
			sel.setAttribute( 'tabindex', '-1' );
			sel.setAttribute( 'aria-hidden', 'true' );
			var escolhido = document.createElement( 'span' );
			escolhido.className = 'sg-escolhido';
			if ( rotulo ) { rotulo.appendChild( escolhido ); }
			grupos.push( { sel: sel, chips: chips, escolhido: escolhido } );
		} );

		function disponivel( attr, valor ) {
			if ( ! dados.length ) { return true; }
			return dados.some( function ( v ) {
				if ( v.is_in_stock === false || v.variation_is_active === false ) { return false; }
				var a = v.attributes[ attr ];
				if ( a && a !== valor ) { return false; }
				return selects.every( function ( s ) {
					var nome = s.getAttribute( 'name' );
					if ( nome === attr || ! s.value ) { return true; }
					var x = v.attributes[ nome ];
					return ! x || x === s.value;
				} );
			} );
		}

		function aplicar( v ) {
			if ( precoEl ) {
				var h = v ? ( $( '.woocommerce-variation-price', form ) ? $( '.woocommerce-variation-price', form ).innerHTML : '' ) : '';
				var ph = v && v.price_html ? v.price_html : h;
				if ( v && ph ) {
					var tmp = document.createElement( 'div' );
					tmp.innerHTML = ph;
					var p = $( '.price', tmp );
					precoEl.innerHTML = p ? p.innerHTML : ph;
				} else { precoEl.innerHTML = original.preco; }
			}
			if ( condEl ) { condEl.textContent = v && v.sg_cond ? v.sg_cond : original.cond; }
			if ( offEl ) {
				if ( v && v.sg_off ) { offEl.textContent = '-' + v.sg_off + '%'; offEl.hidden = false; }
				else { offEl.textContent = original.off; offEl.hidden = original.offOculto; }
			}
		}

		var ultimo = -1;
		function sync() {
			grupos.forEach( function ( g ) {
				var atual = g.sel.value;
				var nome = g.sel.getAttribute( 'name' );
				var txtSel = '';
				g.chips.forEach( function ( c ) {
					var on = c.v === atual;
					var presente = $$( 'option', g.sel ).some( function ( o ) { return o.value === c.v && ! o.disabled; } );
					var livre = presente && disponivel( nome, c.v );
					c.el.setAttribute( 'aria-checked', on ? 'true' : 'false' );
					c.el.classList.toggle( 'is-on', on );
					c.el.tabIndex = on || ( ! atual && c === g.chips[ 0 ] ) ? 0 : -1;
					if ( ! livre && ! on ) {
						c.el.setAttribute( 'aria-disabled', 'true' );
						c.el.classList.add( 'is-off' );
						c.el.title = 'Indisponível nesta combinação';
					} else {
						c.el.removeAttribute( 'aria-disabled' );
						c.el.classList.remove( 'is-off' );
						c.el.removeAttribute( 'title' );
					}
					if ( on ) { txtSel = c.t; }
				} );
				g.escolhido.textContent = txtSel ? ': ' + txtSel : '';
			} );
			// botão de compra
			if ( botao ) {
				var travado = botao.classList.contains( 'disabled' ) || botao.classList.contains( 'wc-variation-selection-needed' ) || botao.disabled;
				if ( travado ) {
					if ( botao.textContent.trim() !== 'Escolha as opções' ) { rotuloBotao = botao.textContent.trim() === 'Escolha as opções' ? rotuloBotao : botao.textContent.trim(); botao.textContent = 'Escolha as opções'; }
					botao.setAttribute( 'aria-disabled', 'true' );
				} else {
					if ( botao.textContent.trim() === 'Escolha as opções' ) { botao.textContent = rotuloBotao || 'Adicionar ao carrinho'; }
					botao.removeAttribute( 'aria-disabled' );
				}
			}
			// preço da variação
			var idEl = $( 'input.variation_id', form );
			var id = idEl ? parseInt( idEl.value, 10 ) || 0 : 0;
			if ( id !== ultimo ) {
				ultimo = id;
				aplicar( id && porId[ id ] ? porId[ id ] : ( id ? { price_html: '' } : null ) );
			}
		}
		function sincAgora() { setTimeout( sync, 0 ); setTimeout( sync, 120 ); }

		selects.forEach( function ( s ) {
			s.addEventListener( 'change', sincAgora );
			new MutationObserver( sincAgora ).observe( s, { childList: true, attributes: true, subtree: true } );
		} );
		if ( botao ) { new MutationObserver( sincAgora ).observe( botao, { attributes: true, attributeFilter: [ 'class', 'disabled' ] } ); }
		var info = $( '.single_variation', form );
		if ( info ) { new MutationObserver( sincAgora ).observe( info, { childList: true } ); }
		var reset = $( '.reset_variations', form );
		if ( reset ) { reset.addEventListener( 'click', sincAgora ); }
		if ( window.jQuery ) {
			window.jQuery( form ).on( 'found_variation', function ( e, v ) { ultimo = v ? v.variation_id : -1; if ( v ) { aplicar( v.sg_cond !== undefined ? v : ( porId[ v.variation_id ] || v ) ); } } )
				.on( 'reset_data hide_variation', sincAgora );
		}
		sync();
	}

	function iniciar() {
		$$( '[data-sg-abas]' ).forEach( abas );
		$$( '[data-sg-curta]' ).forEach( lerMais );
		$$( 'form.cart .quantity' ).forEach( quantidade );
		$$( 'form.variations_form' ).forEach( variacoes );
	}
	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', iniciar ); } else { iniciar(); }
}() );
