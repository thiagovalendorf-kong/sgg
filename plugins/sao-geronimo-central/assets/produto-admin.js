/* São Gerônimo: tela de produto. JS puro, sem dependências.
   1) Checklist do cadastro em tempo real.
   2) Resumo (foto, atributos, preço, estoque) no cabeçalho de cada variação.
   3) Teclado nas variações, primeira variação aberta, textos de apoio. */
( function () {
	'use strict';

	var $ = function ( s, r ) { return ( r || document ).querySelector( s ); };
	var $$ = function ( s, r ) { return Array.prototype.slice.call( ( r || document ).querySelectorAll( s ) ); };

	/* ------------------------------------------------------------------
	 * Utilidades
	 * ---------------------------------------------------------------- */
	function valor( el ) {
		return el && typeof el.value === 'string' ? el.value.trim() : '';
	}

	// Texto de um editor (visual ou HTML), sem etiquetas.
	function textoEditor( id ) {
		var texto = '';
		var ed = window.tinymce && window.tinymce.get ? window.tinymce.get( id ) : null;
		if ( ed && ! ed.isHidden() ) {
			texto = ed.getContent( { format: 'text' } ) || '';
		} else {
			texto = valor( document.getElementById( id ) );
		}
		return texto.replace( /<[^>]*>/g, ' ' ).replace( /&nbsp;/g, ' ' ).trim();
	}

	function visivel( el ) {
		return !! ( el && ( el.offsetWidth || el.offsetHeight || el.getClientRects().length ) );
	}

	function tipo() {
		var s = document.getElementById( 'product-type' );
		return s ? s.value : 'simple';
	}

	function marcado( id ) {
		var c = document.getElementById( id );
		return !! ( c && c.checked );
	}

	function abrirAba( href ) {
		var a = $( '#woocommerce-product-data ul.wc-tabs a[href="' + href + '"]' );
		if ( a && a.parentNode && a.parentNode.style.display !== 'none' ) {
			a.click();
			return true;
		}
		return false;
	}

	function irPara( alvo, foco ) {
		if ( ! alvo ) {
			return;
		}
		alvo.scrollIntoView( { behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth', block: 'center' } );
		var f = foco || alvo;
		if ( f && f.focus ) {
			try { f.focus( { preventScroll: true } ); } catch ( e ) { f.focus(); }
		}
	}

	function focarEditor( id, caixa ) {
		var ed = window.tinymce && window.tinymce.get ? window.tinymce.get( id ) : null;
		var cx = document.getElementById( caixa );
		if ( cx ) {
			cx.scrollIntoView( { block: 'center' } );
		}
		if ( ed && ! ed.isHidden() ) {
			ed.focus();
		} else {
			var t = document.getElementById( id );
			if ( t ) {
				t.focus();
			}
		}
	}

	/* ------------------------------------------------------------------
	 * 1) Checklist
	 * ---------------------------------------------------------------- */
	var itens = [
		{
			id: 'nome', rotulo: 'Nome do produto',
			ok: function () { return valor( document.getElementById( 'title' ) ) !== ''; },
			ir: function () { irPara( document.getElementById( 'title' ) ); }
		},
		{
			id: 'foto', rotulo: 'Foto principal',
			ok: function () {
				var v = valor( document.getElementById( '_thumbnail_id' ) );
				return v !== '' && v !== '-1' && v !== '0';
			},
			ir: function () { irPara( document.getElementById( 'postimagediv' ), $( '#set-post-thumbnail' ) || $( '#postimagediv a' ) ); }
		},
		{
			id: 'preco', rotulo: 'Preço',
			aplica: function () { return tipo() === 'simple' || tipo() === 'external'; },
			ok: function () { return valor( document.getElementById( '_regular_price' ) ) !== ''; },
			ir: function () {
				abrirAba( '#general_product_data' );
				irPara( document.getElementById( '_regular_price' ) );
			}
		},
		{
			id: 'categoria', rotulo: 'Categoria',
			ok: function () { return $$( '#product_catdiv input[type="checkbox"][name="tax_input[product_cat][]"]:checked' ).length > 0; },
			ir: function () { irPara( document.getElementById( 'product_catdiv' ), $( '#product_catdiv input[type="checkbox"]' ) ); }
		},
		{
			id: 'curta', rotulo: 'Descrição curta',
			ok: function () { return textoEditor( 'excerpt' ) !== ''; },
			ir: function () { focarEditor( 'excerpt', 'postexcerpt' ); }
		},
		{
			id: 'longa', rotulo: 'Descrição completa',
			ok: function () { return textoEditor( 'content' ) !== ''; },
			ir: function () { focarEditor( 'content', 'postdivrich' ); }
		},
		{
			id: 'frete', rotulo: 'Peso e medidas (frete)',
			aplica: function () {
				var t = tipo();
				return t !== 'grouped' && t !== 'external' && ! marcado( '_virtual' );
			},
			ok: function () {
				var campos = [ '_weight', 'product_length', 'product_width', 'product_height' ];
				var pai = campos.every( function ( c ) { return valor( document.getElementById( c ) ) !== ''; } );
				if ( pai ) {
					return true;
				}
				if ( tipo() !== 'variable' ) {
					return false;
				}
				var vars = $$( '.woocommerce_variation' );
				if ( ! vars.length ) {
					return false;
				}
				return vars.every( function ( v ) {
					return [ 'variable_weight', 'variable_length', 'variable_width', 'variable_height' ].every( function ( n ) {
						return valor( $( 'input[name^="' + n + '["]', v ) ) !== '';
					} );
				} );
			},
			ir: function () {
				abrirAba( '#shipping_product_data' );
				irPara( document.getElementById( '_weight' ) );
			}
		},
		{
			id: 'variacoes', rotulo: 'Pelo menos 1 variação com preço',
			aplica: function () { return tipo() === 'variable'; },
			ok: function () {
				var vars = $$( '.woocommerce_variation' );
				if ( ! vars.length ) {
					// Variações ainda não carregadas na tela: confia no total informado pelo WooCommerce.
					var lista = $( '.woocommerce_variations' );
					return !! ( lista && parseInt( lista.getAttribute( 'data-total' ), 10 ) > 0 );
				}
				return vars.some( function ( v ) { return valor( $( 'input[name^="variable_regular_price["]', v ) ) !== ''; } );
			},
			ir: function () {
				abrirAba( '#variable_product_options' );
				irPara( $( '#variable_product_options .toolbar-top' ) || document.getElementById( 'variable_product_options' ), $( '#variable_product_options .generate_variations' ) );
			}
		}
	];

	var caixa, lista, resumo, barra, botoes = {};

	function montarChecklist() {
		caixa = document.getElementById( 'sgp-check' );
		lista = document.getElementById( 'sgp-check-lista' );
		resumo = document.getElementById( 'sgp-check-resumo' );
		barra = document.getElementById( 'sgp-check-barra' );
		if ( ! caixa || ! lista ) {
			return;
		}
		itens.forEach( function ( it ) {
			var li = document.createElement( 'li' );
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'sgp-check__item';
			b.innerHTML = '<span class="sgp-check__marca" aria-hidden="true"></span><span class="sgp-check__txt"></span>';
			b.querySelector( '.sgp-check__txt' ).textContent = it.rotulo;
			b.addEventListener( 'click', function () { it.ir(); } );
			li.appendChild( b );
			lista.appendChild( li );
			botoes[ it.id ] = { li: li, b: b, ok: null };
		} );
		caixa.hidden = false;
	}

	function atualizarChecklist() {
		if ( ! caixa ) {
			return;
		}
		var total = 0, prontos = 0;
		itens.forEach( function ( it ) {
			var ref = botoes[ it.id ];
			var aplica = ! it.aplica || it.aplica();
			ref.li.hidden = ! aplica;
			if ( ! aplica ) {
				return;
			}
			var ok = false;
			try { ok = !! it.ok(); } catch ( e ) { ok = false; }
			total++;
			if ( ok ) {
				prontos++;
			}
			if ( ref.ok !== ok ) {
				ref.ok = ok;
				ref.b.classList.toggle( 'ok', ok );
				ref.b.querySelector( '.sgp-check__marca' ).textContent = ok ? '✓' : '!';
				ref.b.setAttribute( 'aria-label', it.rotulo + ( ok ? ': pronto' : ': falta preencher. Clique para ir ao campo.' ) );
			}
		} );
		var faltam = total - prontos;
		var txt = faltam === 0 ? 'Tudo certo! Seu produto está bem completo.' : prontos + ' de ' + total + ' itens prontos. Falta' + ( faltam > 1 ? 'm ' : ' ' ) + faltam + '.';
		if ( resumo.textContent !== txt ) {
			resumo.textContent = txt;
		}
		barra.style.width = ( total ? Math.round( prontos / total * 100 ) : 0 ) + '%';
	}

	/* ------------------------------------------------------------------
	 * 2) Variações: resumo no cabeçalho
	 * ---------------------------------------------------------------- */
	function esc( s ) {
		return String( s ).replace( /[&<>"]/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ]; } );
	}

	function dinheiro( v ) {
		v = ( v || '' ).trim();
		return v === '' ? '' : ( /R\$/.test( v ) ? v : 'R$ ' + v );
	}

	function resumirVariacao( v ) {
		var h3 = $( ':scope > h3', v );
		if ( ! h3 ) {
			return;
		}
		var foto = $( '.sgp-var-foto', h3 ), chips = $( '.sgp-var-chips', h3 ), preco = $( '.sgp-var-preco', h3 ), est = $( '.sgp-var-estoque', h3 );
		if ( ! foto ) {
			foto = document.createElement( 'img' );
			foto.className = 'sgp-var-foto';
			foto.alt = '';
			chips = document.createElement( 'span' );
			chips.className = 'sgp-var-chips';
			preco = document.createElement( 'span' );
			preco.className = 'sgp-var-preco';
			est = document.createElement( 'span' );
			est.className = 'sgp-var-estoque';
			h3.appendChild( foto );
			h3.appendChild( chips );
			h3.appendChild( preco );
			h3.appendChild( est );
			h3.setAttribute( 'tabindex', '0' );
			h3.setAttribute( 'role', 'button' );
		}
		h3.setAttribute( 'aria-expanded', v.classList.contains( 'closed' ) ? 'false' : 'true' );

		var img = $( '.upload_image img', v );
		var src = img ? img.getAttribute( 'src' ) : '';
		if ( src && foto.getAttribute( 'src' ) !== src ) {
			foto.setAttribute( 'src', src );
		}
		foto.style.visibility = src ? 'visible' : 'hidden';

		// Atributos (chips)
		var html = '';
		$$( 'select[name^="attribute_"]', h3 ).forEach( function ( s ) {
			var op = s.options[ s.selectedIndex ];
			if ( ! op ) {
				return;
			}
			html += '<span class="sgp-chip' + ( s.value === '' ? ' sgp-chip--qualquer' : '' ) + '">' + esc( op.text.trim() ) + '</span>';
		} );
		if ( chips.getAttribute( 'data-k' ) !== html ) {
			chips.setAttribute( 'data-k', html );
			chips.innerHTML = html;
		}

		// Preço
		var reg = valor( $( 'input[name^="variable_regular_price["]', v ) );
		var pro = valor( $( 'input[name^="variable_sale_price["]', v ) );
		var htmlPreco;
		if ( reg === '' ) {
			htmlPreco = '<span class="sgp-chip sgp-chip--aviso">Sem preço</span>';
		} else if ( pro !== '' ) {
			htmlPreco = '<s>' + esc( dinheiro( reg ) ) + '</s>' + esc( dinheiro( pro ) );
		} else {
			htmlPreco = esc( dinheiro( reg ) );
		}
		if ( preco.getAttribute( 'data-k' ) !== htmlPreco ) {
			preco.setAttribute( 'data-k', htmlPreco );
			preco.innerHTML = htmlPreco;
		}

		// Estoque e situação
		var htmlEst = '';
		var gerencia = $( 'input.variable_manage_stock', v );
		if ( gerencia && gerencia.checked ) {
			var q = valor( $( 'input[name^="variable_stock["]', v ) );
			var n = parseFloat( q.replace( ',', '.' ) );
			htmlEst = ( isNaN( n ) || n > 0 ) ? '<span class="sgp-chip sgp-chip--ok">Estoque: ' + esc( q === '' ? '0' : q ) + '</span>' : '<span class="sgp-chip sgp-chip--aviso">Sem estoque</span>';
		} else {
			var st = $( 'select[name^="variable_stock_status["]', v );
			if ( st && st.options[ st.selectedIndex ] ) {
				htmlEst = '<span class="sgp-chip ' + ( st.value === 'instock' ? 'sgp-chip--ok' : 'sgp-chip--aviso' ) + '">' + esc( st.options[ st.selectedIndex ].text.trim() ) + '</span>';
			}
		}
		var ativa = $( 'input[name^="variable_enabled["]', v );
		if ( ativa && ! ativa.checked ) {
			htmlEst += ' <span class="sgp-chip sgp-chip--aviso">Desativada</span>';
		}
		if ( est.getAttribute( 'data-k' ) !== htmlEst ) {
			est.setAttribute( 'data-k', htmlEst );
			est.innerHTML = htmlEst;
		}
	}

	var primeiraAberta = false;
	function atualizarVariacoes() {
		var vars = $$( '.woocommerce_variation' );
		vars.forEach( resumirVariacao );
		if ( ! primeiraAberta && vars.length ) {
			primeiraAberta = true;
			// Primeira variação aberta, se nenhuma veio aberta.
			if ( vars.every( function ( v ) { return v.classList.contains( 'closed' ); } ) ) {
				var v0 = vars[ 0 ];
				v0.classList.remove( 'closed' );
				v0.classList.add( 'open' );
				var c = $( ':scope > .wc-metabox-content', v0 );
				if ( c ) {
					c.style.display = 'block';
				}
				resumirVariacao( v0 );
			}
		}
	}

	/* ------------------------------------------------------------------
	 * 3) Teclado nas variações e atributos, textos de apoio
	 * ---------------------------------------------------------------- */
	document.addEventListener( 'keydown', function ( e ) {
		if ( ( e.key === 'Enter' || e.key === ' ' ) && e.target && e.target.matches && e.target.matches( '.wc-metabox > h3[role="button"]' ) ) {
			e.preventDefault();
			e.target.click();
		}
	} );
	// Ao abrir/fechar, atualiza aria-expanded (o WooCommerce troca a classe depois do clique).
	document.addEventListener( 'click', function ( e ) {
		if ( e.target && e.target.closest && e.target.closest( '.woocommerce_variation > h3' ) ) {
			setTimeout( atualizarVariacoes, 60 );
		}
	} );

	function dicas() {
		var ph = { _regular_price: '0,00', _sale_price: '0,00', _weight: '0,00' };
		Object.keys( ph ).forEach( function ( id ) {
			var el = document.getElementById( id );
			if ( el && ! el.getAttribute( 'placeholder' ) ) {
				el.setAttribute( 'placeholder', ph[ id ] );
			}
		} );
		var med = { product_length: 'Comprimento (cm)', product_width: 'Largura (cm)', product_height: 'Altura (cm)' };
		Object.keys( med ).forEach( function ( id ) {
			var el = document.getElementById( id );
			if ( el && ! el.getAttribute( 'placeholder' ) ) {
				el.setAttribute( 'placeholder', med[ id ] );
			}
		} );
	}

	/* ------------------------------------------------------------------
	 * Atualização
	 * ---------------------------------------------------------------- */
	var agendado = null;
	function atualizar() {
		agendado = null;
		atualizarChecklist();
		atualizarVariacoes();
	}
	function agendar() {
		if ( agendado === null ) {
			agendado = setTimeout( atualizar, 120 );
		}
	}

	var ligados = {};
	function ligarEditores() {
		[ 'content', 'excerpt' ].forEach( function ( id ) {
			var ed = window.tinymce && window.tinymce.get ? window.tinymce.get( id ) : null;
			if ( ed && ! ligados[ id ] ) {
				ligados[ id ] = true;
				ed.on( 'input keyup change SetContent undo redo', agendar );
			}
		} );
	}

	// Produto variável: abre direto em Variações (o fluxo é Atributos, depois Variações), sem trocar se a pessoa pediu outra aba pelo endereço.
	var abriuInicial = false;
	function abrirAbaInicial() {
		if ( tipo() !== 'variable' || window.location.hash ) {
			return;
		}
		if ( abriuInicial ) {
			return;
		}
		var ativa = $( '#woocommerce-product-data ul.wc-tabs li.active a' );
		if ( ativa && ativa.getAttribute( 'href' ) !== '#variable_product_options' ) {
			abriuInicial = abrirAba( '#variable_product_options' ) && !! $( '#woocommerce-product-data ul.wc-tabs li.active a[href="#variable_product_options"]' );
		} else {
			abriuInicial = true;
		}
	}

	function iniciar() {
		dicas();
		// O WooCommerce liga as abas depois deste script: tenta de novo se precisar.
		setTimeout( abrirAbaInicial, 300 );
		setTimeout( abrirAbaInicial, 1000 );
		montarChecklist();
		atualizar();
		[ 'input', 'change', 'keyup' ].forEach( function ( ev ) { document.addEventListener( ev, agendar, true ); } );
		document.addEventListener( 'click', function ( e ) {
			// Fotos e categorias mudam por clique/JS do WordPress.
			if ( e.target && e.target.closest && e.target.closest( '#postimagediv, #product_catdiv, #woocommerce-product-data' ) ) {
				agendar();
			}
		}, true );

		var painel = document.getElementById( 'variable_product_options' );
		if ( painel && window.MutationObserver ) {
			new MutationObserver( agendar ).observe( painel, { childList: true, subtree: true } );
		}
		// Rede de segurança: foto, editores e carregamento tardio.
		setInterval( function () {
			if ( document.hidden ) {
				return;
			}
			ligarEditores();
			atualizar();
		}, 1200 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', iniciar );
	} else {
		iniciar();
	}
}() );
