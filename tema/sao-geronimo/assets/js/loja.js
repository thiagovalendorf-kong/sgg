/* Loja: filtros laterais (AJAX com URL, folha no celular, Ver mais, busca, preço com dois marcadores).
   Sem JS o formulário GET continua funcionando normalmente. */
( function () {
	var corpo = document.body;
	var aside = document.getElementById( 'filtros' );
	var form = document.querySelector( '[data-filtros-form]' );
	var celular = function () { return window.matchMedia( '(max-width: 900px)' ).matches; };
	var ultimoFoco = null;

	/* ---------- altura do cabeçalho (para o sticky) ---------- */
	var cab = document.querySelector( '.cab' );
	function medirCab() {
		if ( cab ) { document.documentElement.style.setProperty( '--cab-h', cab.offsetHeight + 'px' ); }
	}
	medirCab();
	window.addEventListener( 'resize', medirCab );

	/* ---------- folha do celular ---------- */
	function focaveis() {
		return Array.prototype.filter.call( aside.querySelectorAll( 'a[href], button, input, summary' ), function ( el ) {
			return ! el.disabled && ! el.hidden && el.offsetParent !== null;
		} );
	}
	function abrir() {
		if ( ! aside ) { return; }
		ultimoFoco = document.activeElement;
		corpo.classList.add( 'filtros-aberto' );
		aside.setAttribute( 'role', 'dialog' );
		aside.setAttribute( 'aria-modal', 'true' );
		aside.setAttribute( 'aria-labelledby', 'filtros-titulo' );
		var x = aside.querySelector( '.filtros__x' );
		if ( x ) { x.focus(); }
	}
	function fechar() {
		if ( ! corpo.classList.contains( 'filtros-aberto' ) ) { return; }
		corpo.classList.remove( 'filtros-aberto' );
		if ( aside ) { aside.removeAttribute( 'role' ); aside.removeAttribute( 'aria-modal' ); aside.removeAttribute( 'aria-labelledby' ); }
		if ( ultimoFoco && document.contains( ultimoFoco ) ) { ultimoFoco.focus(); }
	}
	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-filtros-abrir]' ) ) { abrir(); }
		if ( e.target.closest( '[data-filtros-fechar]' ) ) { fechar(); }
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) { fechar(); }
		if ( e.key === 'Tab' && corpo.classList.contains( 'filtros-aberto' ) && celular() ) {
			var f = focaveis();
			if ( ! f.length ) { return; }
			var a = f[ 0 ], z = f[ f.length - 1 ];
			if ( e.shiftKey && document.activeElement === a ) { z.focus(); e.preventDefault(); }
			else if ( ! e.shiftKey && document.activeElement === z ) { a.focus(); e.preventDefault(); }
		}
	} );
	window.matchMedia( '(min-width: 901px)' ).addEventListener( 'change', function ( e ) { if ( e.matches ) { fechar(); } } );

	if ( ! form || ! aside ) { return; }
	aside.classList.add( 'filtros--js' );

	/* ---------- utilidades ---------- */
	var fmt = new Intl.NumberFormat( 'pt-BR' );
	function reais( n ) { return 'R$ ' + fmt.format( n ); }
	function semAcento( s ) { return s.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase(); }
	function $$( sel, raiz ) { return Array.prototype.slice.call( ( raiz || form ).querySelectorAll( sel ) ); }

	/* ---------- listas: Ver mais, rolagem, busca ---------- */
	$$( '[data-lista]' ).forEach( function ( lista ) {
		var bloco = lista.closest( '.fb__corpo' );
		var mais = bloco.querySelector( '[data-mais]' );
		var rol = lista.querySelector( '[data-rolagem]' );
		var busca = bloco.querySelector( '[data-busca]' );
		var vazio = lista.querySelector( '[data-vazio]' );
		var itens = $$( '.fo', lista );
		var total = mais ? mais.getAttribute( 'data-total' ) : 0;

		function sombra() {
			var resta = rol.scrollHeight - rol.scrollTop - rol.clientHeight > 4;
			lista.classList.toggle( 'fl--mais', lista.classList.contains( 'fl--aberta' ) && resta );
		}
		rol.addEventListener( 'scroll', sombra, { passive: true } );

		if ( mais ) {
			mais.hidden = false;
			mais.addEventListener( 'click', function () {
				var aberta = lista.classList.toggle( 'fl--aberta' );
				lista.classList.toggle( 'fl--recolhida', ! aberta );
				mais.setAttribute( 'aria-expanded', aberta ? 'true' : 'false' );
				mais.textContent = aberta ? 'Ver menos' : 'Ver mais (' + total + ')';
				sombra();
			} );
		}
		if ( busca ) {
			busca.hidden = false;
			var campo = busca.querySelector( 'input' );
			campo.addEventListener( 'input', function () {
				var q = semAcento( campo.value.trim() );
				var achou = 0;
				lista.classList.toggle( 'fl--buscando', q !== '' );
				itens.forEach( function ( it ) {
					var ok = q === '' || it.getAttribute( 'data-texto' ).indexOf( q ) !== -1;
					it.hidden = ! ok;
					if ( ok ) { achou++; }
				} );
				if ( vazio ) { vazio.hidden = achou > 0; }
				if ( mais ) { mais.hidden = q !== ''; }
				sombra();
			} );
			// Enter na busca não envia o formulário.
			campo.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Enter' ) { e.preventDefault(); } } );
		}
	} );

	/* ---------- preço: dois marcadores + campos ---------- */
	var preco = form.querySelector( '[data-preco]' );
	var pMin, pMax, rMin, rMax, barra, leitura, limMin, limMax;
	function precoDesenha() {
		var a = Number( rMin.value ), b = Number( rMax.value );
		var span = Math.max( 1, limMax - limMin );
		barra.style.left = ( ( a - limMin ) / span * 100 ) + '%';
		barra.style.right = ( 100 - ( b - limMin ) / span * 100 ) + '%';
		leitura.textContent = reais( a ) + ' – ' + reais( b );
	}
	if ( preco ) {
		limMin = Number( preco.getAttribute( 'data-min' ) );
		limMax = Number( preco.getAttribute( 'data-max' ) );
		pMin = preco.querySelector( 'input[name=sg_min]' );
		pMax = preco.querySelector( 'input[name=sg_max]' );
		rMin = preco.querySelector( '[data-r=min]' );
		rMax = preco.querySelector( '[data-r=max]' );
		barra = preco.querySelector( '[data-barra]' );
		leitura = preco.querySelector( '[data-leitura]' );
		preco.querySelector( '[data-trilho]' ).hidden = false;
		leitura.hidden = false;
		precoDesenha();
	}
	// Valor vazio = sem limite. Os marcadores nunca se cruzam.
	function doMarcador( qual ) {
		var a = Number( rMin.value ), b = Number( rMax.value );
		if ( a > b ) { if ( qual === 'min' ) { rMin.value = b; } else { rMax.value = a; } }
		a = Number( rMin.value ); b = Number( rMax.value );
		pMin.value = a <= limMin ? '' : a;
		pMax.value = b >= limMax ? '' : b;
		precoDesenha();
		desmarcaFaixas();
	}
	function doCampo() {
		var a = pMin.value === '' ? limMin : Math.max( limMin, Math.min( limMax, Number( pMin.value ) ) );
		var b = pMax.value === '' ? limMax : Math.max( limMin, Math.min( limMax, Number( pMax.value ) ) );
		if ( a > b ) { var t = a; a = b; b = t; }
		rMin.value = a; rMax.value = b;
		precoDesenha();
		desmarcaFaixas();
	}
	function desmarcaFaixas() {
		$$( 'input[name=sg_faixa]' ).forEach( function ( r ) { r.checked = false; } );
	}
	function marcaFaixaCerta() {
		if ( ! preco ) { return; }
		var a = pMin.value === '' ? null : Number( pMin.value );
		var b = pMax.value === '' ? null : Number( pMax.value );
		$$( 'input[name=sg_faixa]' ).forEach( function ( r ) {
			var de = Number( r.getAttribute( 'data-de' ) ), ate = r.getAttribute( 'data-ate' );
			r.checked = ( a !== null || b !== null ) && ( a === null ? 0 : a ) === de && ( ate === '' ? b === null : b === Number( ate ) );
		} );
	}

	/* ---------- estado do formulário <-> endereço ---------- */
	function paramsDoForm() {
		var p = new URLSearchParams();
		$$( 'input[type=checkbox]:checked' ).forEach( function ( c ) { p.append( c.name, c.value ); } );
		if ( preco ) {
			if ( pMin.value !== '' && Number( pMin.value ) > 0 ) { p.set( 'sg_min', Math.round( Number( pMin.value ) ) ); }
			if ( pMax.value !== '' ) { p.set( 'sg_max', Math.round( Number( pMax.value ) ) ); }
		}
		var o = new URLSearchParams( location.search ).get( 'orderby' );
		if ( o ) { p.set( 'orderby', o ); }
		return p;
	}
	function urlDe( params ) {
		var s = params.toString().replace( /%5B%5D/g, '[]' );
		return form.getAttribute( 'action' ) + ( s ? '?' + s : '' );
	}
	function contaAtivos() {
		var n = $$( 'input[type=checkbox]:checked' ).length;
		if ( preco && ( pMin.value !== '' || pMax.value !== '' ) ) { n++; }
		return n;
	}
	// Atualiza selos de quantidade nos títulos dos blocos e o botão "Limpar".
	function atualizaSelos() {
		$$( '.fb' ).forEach( function ( b ) {
			var n = $$( 'input[type=checkbox]:checked', b ).length;
			if ( b.getAttribute( 'data-bloco' ) === 'preco' ) { n = ( pMin.value !== '' || pMax.value !== '' ) ? 1 : 0; }
			var s = b.querySelector( 'summary' ), sel = s.querySelector( '.fb__n' );
			if ( n && ! sel ) { sel = document.createElement( 'b' ); sel.className = 'fb__n'; s.insertBefore( sel, s.querySelector( 'span' ).nextSibling ); }
			if ( sel ) { if ( n ) { sel.textContent = n; } else { sel.remove(); } }
		} );
		var lim = form.querySelector( '[data-limpar]' );
		if ( lim ) { lim.hidden = contaAtivos() === 0; }
	}
	// Preenche o formulário a partir de um endereço (botão voltar, etiquetas, "Limpar").
	function formDoUrl( url ) {
		var p = new URL( url, location.href ).searchParams;
		$$( 'input[type=checkbox]' ).forEach( function ( c ) {
			c.checked = p.getAll( c.name ).indexOf( c.value ) !== -1;
		} );
		if ( preco ) {
			var mn = p.get( 'sg_min' ), mx = p.get( 'sg_max' ), fx = p.get( 'sg_faixa' );
			if ( ! mn && ! mx && fx && /^\d*-\d*$/.test( fx ) ) { var q = fx.split( '-' ); mn = q[ 0 ]; mx = q[ 1 ]; }
			pMin.value = mn ? Math.round( Number( mn ) ) : '';
			pMax.value = mx ? Math.round( Number( mx ) ) : '';
			doCampo();
			marcaFaixaCerta();
		}
		atualizaSelos();
	}

	/* ---------- AJAX ---------- */
	var vivo = document.createElement( 'div' );
	vivo.className = 'sr-vivo';
	vivo.setAttribute( 'role', 'status' );
	vivo.setAttribute( 'aria-live', 'polite' );
	corpo.appendChild( vivo );

	var btnVer = form.querySelector( '[data-ver]' );
	var seq = 0, ctrl = null, pendente = null, timer = null;

	function textoContagem( n ) {
		return n === 0 ? 'Nenhum produto encontrado' : ( n === 1 ? '1 produto encontrado' : n + ' produtos encontrados' );
	}
	function contagemDoc( doc ) {
		var el = doc.querySelector( '[data-contagem]' );
		var m = el ? el.textContent.match( /\d+/ ) : null;
		return m ? Number( m[ 0 ] ) : 0;
	}
	function rotuloBotao( n ) {
		if ( ! btnVer ) { return; }
		btnVer.textContent = n === 0 ? 'Nenhum produto' : ( n === 1 ? 'Ver 1 produto' : 'Ver ' + fmt.format( n ) + ' produtos' );
	}
	// As contagens ao lado das opções vêm do servidor já considerando os outros filtros marcados.
	function atualizaContagens( doc ) {
		var novo = doc.querySelector( '[data-filtros-form]' );
		if ( ! novo ) { return; }
		$$( '.fo' ).forEach( function ( fo ) {
			var inp = fo.querySelector( 'input' ), n = fo.querySelector( '.fo__n' );
			if ( ! inp || ! n || ! inp.name || inp.name === 'sg_faixa' ) { return; }
			var alvo = Array.prototype.filter.call( novo.querySelectorAll( 'input[name="' + inp.name + '"]' ), function ( i ) { return i.value === inp.value; } )[ 0 ];
			var nn = alvo && alvo.closest( '.fo' ) ? alvo.closest( '.fo' ).querySelector( '.fo__n' ) : null;
			if ( nn ) { n.textContent = nn.textContent; }
		} );
	}
	function aplica( doc, url, empurra ) {
		var novo = doc.querySelector( '.loja-main' ), atual = document.querySelector( '.loja-main' );
		if ( ! novo || ! atual ) { location.href = url; return; }
		atual.innerHTML = novo.innerHTML;
		atual.classList.remove( 'is-carregando' );
		atual.removeAttribute( 'aria-busy' );
		if ( doc.title ) { document.title = doc.title; }
		if ( empurra ) { history.pushState( { sgFiltros: 1 }, '', url ); }
		var n = contagemDoc( doc );
		vivo.textContent = '';
		setTimeout( function () { vivo.textContent = textoContagem( n ); }, 50 );
		rotuloBotao( n );
		atualizaContagens( doc );
		aside.setAttribute( 'data-ativos', contaAtivos() );
	}
	// Busca o endereço. aplicar=false: só descobre a contagem (celular, antes de tocar em "Ver").
	function busca( url, opcoes ) {
		opcoes = opcoes || {};
		var meu = ++seq;
		if ( ctrl ) { ctrl.abort(); }
		ctrl = new AbortController();
		var main = document.querySelector( '.loja-main' );
		if ( opcoes.aplicar && main ) { main.classList.add( 'is-carregando' ); main.setAttribute( 'aria-busy', 'true' ); }
		return fetch( url, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin', signal: ctrl.signal } )
			.then( function ( r ) { if ( ! r.ok ) { throw new Error( r.status ); } return r.text(); } )
			.then( function ( html ) {
				if ( meu !== seq ) { return; }
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				if ( opcoes.aplicar ) {
					aplica( doc, url, opcoes.empurra );
					pendente = null;
				} else {
					pendente = { doc: doc, url: url };
					rotuloBotao( contagemDoc( doc ) );
					atualizaContagens( doc );
				}
			} )
			.catch( function ( err ) {
				if ( err && err.name === 'AbortError' ) { return; }
				location.href = url; // falhou: navegação normal
			} );
	}

	function mudou( imediato ) {
		atualizaSelos();
		var url = urlDe( paramsDoForm() );
		clearTimeout( timer );
		timer = setTimeout( function () {
			if ( celular() ) { busca( url, { aplicar: false } ); }
			else { busca( url, { aplicar: true, empurra: true } ); }
		}, imediato ? 0 : 350 );
	}

	form.addEventListener( 'change', function ( e ) {
		var t = e.target;
		if ( t.type === 'search' ) { return; }
		if ( t.name === 'sg_faixa' ) {
			pMin.value = Number( t.getAttribute( 'data-de' ) ) > 0 ? t.getAttribute( 'data-de' ) : '';
			pMax.value = t.getAttribute( 'data-ate' );
			doCampo();
			marcaFaixaCerta();
		}
		mudou( t.type === 'checkbox' || t.type === 'radio' );
	} );
	if ( preco ) {
		// Arrastando: só ajusta a tela; ao soltar (change) aplica.
		[ rMin, rMax ].forEach( function ( r ) {
			r.addEventListener( 'input', function () { doMarcador( r.getAttribute( 'data-r' ) ); } );
		} );
		[ pMin, pMax ].forEach( function ( c ) {
			c.addEventListener( 'input', function () { doCampo(); mudou( false ); } );
			c.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Enter' ) { e.preventDefault(); mudou( true ); } } );
		} );
	}

	// "Ver N produtos" no celular aplica o que está na tela e fecha a folha.
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var url = urlDe( paramsDoForm() );
		clearTimeout( timer );
		if ( celular() ) {
			if ( pendente && pendente.url === url ) { aplica( pendente.doc, url, true ); pendente = null; }
			else { busca( url, { aplicar: true, empurra: true } ); }
			fechar();
			rolaAteLista();
		} else {
			busca( url, { aplicar: true, empurra: true } );
		}
	} );

	function rolaAteLista() {
		var m = document.querySelector( '.loja-main' );
		if ( m ) { window.scrollTo( { top: Math.max( 0, m.getBoundingClientRect().top + window.pageYOffset - ( cab ? cab.offsetHeight : 0 ) - 16 ), behavior: matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth' } ); }
	}

	// "Limpar" do cabeçalho do bloco.
	form.addEventListener( 'click', function ( e ) {
		var l = e.target.closest( '[data-limpar]' );
		if ( ! l ) { return; }
		e.preventDefault();
		var o = new URLSearchParams( location.search ).get( 'orderby' );
		var url = form.getAttribute( 'action' ) + ( o ? '?orderby=' + encodeURIComponent( o ) : '' );
		formDoUrl( url );
		if ( celular() ) { busca( url, { aplicar: false } ); } else { busca( url, { aplicar: true, empurra: true } ); }
	} );

	// Etiquetas (×), "Limpar tudo" e paginação: tudo por AJAX, mantendo os filtros.
	document.addEventListener( 'click', function ( e ) {
		if ( e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey ) { return; }
		var a = e.target.closest( '.loja-main [data-chip], .loja-main .woocommerce-pagination a' );
		if ( ! a ) { return; }
		e.preventDefault();
		var url = a.href;
		if ( a.hasAttribute( 'data-chip' ) ) { formDoUrl( url ); }
		busca( url, { aplicar: true, empurra: true } ).then( rolaAteLista );
	} );

	// Ordenação: troca por AJAX.
	function ordena( valor ) {
		var u = new URL( location.href );
		u.searchParams.set( 'orderby', valor );
		u.searchParams.delete( 'paged' );
		u.pathname = u.pathname.replace( /\/page\/\d+\/?$/, '/' );
		busca( u.pathname + u.search, { aplicar: true, empurra: true } );
	}
	document.addEventListener( 'change', function ( e ) {
		if ( e.target.matches && e.target.matches( '.woocommerce-ordering select.orderby' ) ) {
			e.stopImmediatePropagation();
			ordena( e.target.value );
		}
	}, true );
	document.addEventListener( 'submit', function ( e ) {
		var f = e.target.closest ? e.target.closest( '.woocommerce-ordering' ) : null;
		if ( f ) { e.preventDefault(); ordena( f.querySelector( 'select.orderby' ).value ); }
	}, true );

	// Botão voltar/avançar do navegador.
	window.addEventListener( 'popstate', function () {
		formDoUrl( location.href );
		busca( location.pathname + location.search, { aplicar: true, empurra: false } );
	} );
	history.replaceState( { sgFiltros: 1 }, '', location.href );

	// Estado inicial.
	atualizaSelos();
	marcaFaixaCerta();
	( function () {
		var n = document.querySelector( '[data-contagem]' );
		var m = n ? n.textContent.match( /\d+/ ) : null;
		if ( m ) { rotuloBotao( Number( m[ 0 ] ) ); }
	} )();
} )();
