/* São Gerônimo — Central de Controle */
( function () {
	'use strict';

	var C = window.SGC;
	var raiz = document.getElementById( 'sgc' );
	if ( ! C || ! raiz ) { return; }

	var $ = function ( s, r ) { return ( r || document ).querySelector( s ); };
	var $$ = function ( s, r ) { return Array.prototype.slice.call( ( r || document ).querySelectorAll( s ) ); };

	var form = $( '#sgc-form' );
	var iframe = $( '[data-iframe]' );
	var tela = $( '[data-tela]' );
	var palco = $( '[data-palco]' );
	var carregando = $( '[data-carregando]' );
	var status = $( '[data-status]' );
	var toastEl = $( '[data-toast]' );

	var LARG = { desktop: 1200, tablet: 820, mobile: 390 };
	var disp = 'desktop';
	var previaUrl = '';
	var ancoraPendente = '';
	var scrollPendente = null;
	var secaoAtual = '';
	var timer = null;
	var salvando = false;
	var refazer = false;
	var travado = false;

	/* ------------------------------------------------------------ avisos */
	function toast( msg, tipo ) {
		toastEl.textContent = msg;
		toastEl.className = 'sgc-aviso-toast on' + ( tipo ? ' ' + tipo : '' );
		clearTimeout( toast.t );
		toast.t = setTimeout( function () { toastEl.classList.remove( 'on' ); }, 3200 );
	}

	function dizStatus( msg, sujo, curto ) {
		var lg = $( '.sgc-st-lg', status ), cr = $( '.sgc-st-cr', status );
		if ( lg && cr ) { lg.textContent = msg; cr.textContent = curto || msg; } else { status.textContent = msg; }
		status.classList.toggle( 'sujo', !! sujo );
	}

	/* Cor: aceita sem "#" e com 3 dígitos; devolve #rrggbb ou '' se incompleta. */
	function hexValido( t ) {
		t = ( t || '' ).trim().replace( /^#?/, '#' );
		if ( /^#[0-9a-fA-F]{3}$/.test( t ) ) { t = '#' + t[1] + t[1] + t[2] + t[2] + t[3] + t[3]; }
		return /^#[0-9a-fA-F]{6}$/.test( t ) ? t.toLowerCase() : '';
	}

	/* ---------------------------------------------------- leitura do form */
	function valorDe( el ) {
		if ( el.type === 'checkbox' ) { return el.checked ? 'sim' : 'nao'; }
		if ( el.type === 'number' ) {
			if ( el.value === '' ) { return ''; }
			return Math.round( parseFloat( el.value ) * ( parseFloat( el.getAttribute( 'data-esc' ) ) || 1 ) );
		}
		if ( el.type === 'color' ) {
			var hex = el.parentNode.querySelector( '.sgc-cor__hex' );
			if ( ! hex ) { return el.value; }
			// Vazio limpa a cor; texto incompleto mantém o último seletor válido.
			return hex.value.trim() === '' ? '' : ( hexValido( hex.value ) || el.value );
		}
		return el.value;
	}

	function coletar() {
		var dados = {};
		$$( '[data-k]', form ).forEach( function ( el ) {
			var k = el.getAttribute( 'data-k' );

			if ( el.hasAttribute( 'data-lista' ) ) {
				dados[ k ] = $$( '[data-item]', el ).map( function ( item ) {
					var o = {};
					$$( '[data-sub]', item ).forEach( function ( s ) { o[ s.getAttribute( 'data-sub' ) ] = valorDe( s ); } );
					return o;
				} );
			} else if ( el.hasAttribute( 'data-cats' ) ) {
				dados[ k ] = $$( 'input:checked', el ).map( function ( i ) { return parseInt( i.value, 10 ); } );
			} else if ( el.hasAttribute( 'data-ordem' ) ) {
				var ordem = [], ativo = {};
				$$( '[data-b]', el ).forEach( function ( li ) {
					var b = li.getAttribute( 'data-b' );
					ordem.push( b );
					ativo[ b ] = li.classList.contains( 'is-off' ) ? 'nao' : 'sim';
				} );
				dados[ k ] = { ordem: ordem, ativo: ativo };
			} else {
				dados[ k ] = valorDe( el );
			}
		} );
		return dados;
	}

	/* ------------------------------------------------------------ servidor */
	function enviar( acao, extra ) {
		var d = new FormData();
		d.append( 'action', acao );
		d.append( 'nonce', C.nonce );
		if ( extra ) { Object.keys( extra ).forEach( function ( k ) { d.append( k, extra[ k ] ); } ); }
		return fetch( C.ajax, { method: 'POST', body: d, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
	}

	var emAndamento = null;
	function salvarRascunho() {
		if ( salvando ) { refazer = true; return; }
		salvando = true;
		dizStatus( 'Salvando…', true, 'Salvando…' );
		emAndamento = enviar( 'sgc_rascunho', { dados: JSON.stringify( coletar() ) } );
		emAndamento.then( function ( r ) {
			salvando = false;
			if ( ! r.success ) { dizStatus( 'Não consegui salvar. Tente de novo.', true, 'Erro ao salvar' ); toast( 'Não consegui salvar.', 'erro' ); return; }
			var n = r.data.mudancas;
			dizStatus( n ? 'Alterações prontas na prévia. Clique em Publicar para colocar no ar.' : 'Tudo publicado.', n > 0, n ? 'Não publicado' : 'Publicado' );
			recarregarPrevia( true );
			if ( refazer ) { refazer = false; salvarRascunho(); }
		} ).catch( function () {
			salvando = false;
			dizStatus( 'Sem conexão. Tente de novo.', true, 'Sem conexão' );
		} );
	}

	function agendar() {
		if ( travado ) { return; }
		dizStatus( 'Alterando…', true, 'Não publicado' );
		clearTimeout( timer );
		timer = setTimeout( salvarRascunho, 650 );
	}

	/* ---------------------------------------------------------------- prévia */
	function ajustar() {
		var w = LARG[ disp ];
		var disponivel = palco.clientWidth - 32;
		var escala = Math.min( 1, disponivel / w );
		var alto = ( palco.clientHeight - 32 ) / escala;
		tela.style.width = w + 'px';
		tela.style.height = alto + 'px';
		tela.style.marginLeft = ( -w / 2 ) + 'px';
		tela.style.transform = 'scale(' + escala + ')';
		tela.setAttribute( 'data-disp', disp );
	}

	function abrirPrevia( url, ancora ) {
		ancoraPendente = ancora || '';
		scrollPendente = null;
		carregando.classList.add( 'on' );
		if ( url !== previaUrl ) {
			previaUrl = url;
			iframe.src = url;
		} else {
			recarregarPrevia( false );
		}
	}

	function recarregarPrevia( manterRolagem ) {
		var win = iframe.contentWindow;
		if ( ! win || ! iframe.src ) { return; }
		try { scrollPendente = manterRolagem ? win.scrollY : null; } catch ( e ) { scrollPendente = null; }
		carregando.classList.add( 'on' );
		try { win.location.reload(); } catch ( e ) { iframe.src = previaUrl; }
	}

	iframe.addEventListener( 'load', function () {
		carregando.classList.remove( 'on' );
		try {
			var win = iframe.contentWindow;
			if ( scrollPendente !== null ) {
				win.scrollTo( 0, scrollPendente );
				scrollPendente = null;
			} else if ( ancoraPendente ) {
				var alvo = win.document.querySelector( ancoraPendente );
				if ( alvo ) {
					win.document.documentElement.style.scrollBehavior = 'auto';
					alvo.scrollIntoView( { block: 'start' } );
					win.scrollBy( 0, -90 );
				}
				ancoraPendente = '';
			}
		} catch ( e ) { /* outro domínio: ignora */ }
	} );

	/* ------------------------------------------------------------- navegação */
	var nav = $( '[data-nav]' );
	var estadoNav = {};
	try { estadoNav = JSON.parse( localStorage.getItem( 'sgc_nav' ) || '{}' ) || {}; } catch ( e ) { estadoNav = {}; }

	function guardarNav() {
		try { localStorage.setItem( 'sgc_nav', JSON.stringify( estadoNav ) ); } catch ( e ) {}
	}

	// Abre ou fecha um grupo/item da navegação.
	function abrirNo( no, abrir, guardar ) {
		var bt = $( ':scope > [data-alterna]', no );
		var alvo = $( ':scope > .sgc-nav__lista, :scope > .sgc-nav__filhos', no );
		if ( ! bt || ! alvo ) { return; }
		bt.setAttribute( 'aria-expanded', abrir ? 'true' : 'false' );
		alvo.hidden = ! abrir;
		no.classList.toggle( 'aberto', abrir );
		if ( guardar ) { estadoNav[ no.getAttribute( 'data-no' ) ] = abrir ? 1 : 0; guardarNav(); }
	}

	// Estado inicial: grupos abertos por padrão, itens fechados, memória por cima.
	$$( '[data-no]', nav ).forEach( function ( no ) {
		var chave = no.getAttribute( 'data-no' );
		var padrao = chave.indexOf( 'g-' ) === 0;
		abrirNo( no, chave in estadoNav ? !! estadoNav[ chave ] : padrao, false );
	} );

	function ir( id, semPrevia ) {
		var sec = $( '[data-sec="' + id + '"]', form );
		if ( ! sec ) { return; }
		$$( '.sgc-sec', form ).forEach( function ( s ) { s.hidden = s !== sec; } );
		$$( '.sgc-nav__item[data-ir]' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-ir' ) === id;
			b.classList.toggle( 'on', on );
			if ( on ) { b.setAttribute( 'aria-current', 'page' ); } else { b.removeAttribute( 'aria-current' ); }
		} );
		// Abre o item e o grupo que contêm a seção atual.
		$$( '.sgc-nav__no, .sgc-nav__grupo', nav ).forEach( function ( no ) { no.classList.remove( 'tem-atual' ); } );
		var atual = $( '.sgc-nav__item.on', nav );
		var pai = atual ? atual.parentNode : null;
		while ( pai && pai !== nav ) {
			if ( pai.hasAttribute && pai.hasAttribute( 'data-no' ) ) {
				pai.classList.add( 'tem-atual' );
				if ( pai.classList.contains( 'aberto' ) === false ) { abrirNo( pai, true, true ); }
			}
			pai = pai.parentNode;
		}
		if ( atual && atual.scrollIntoView ) { atual.scrollIntoView( { block: 'nearest' } ); }
		form.scrollTop = 0;
		secaoAtual = id;
		try { history.replaceState( null, '', '#' + id ); localStorage.setItem( 'sgc_secao', id ); } catch ( e ) {}
		raiz.classList.remove( 'ver-previa' );
		if ( ! semPrevia ) { abrirPrevia( C.previas[ id ], sec.getAttribute( 'data-ancora' ) ); }
	}

	nav.addEventListener( 'click', function ( e ) {
		var bt = e.target.closest( '[data-alterna]' );
		if ( ! bt ) { return; }
		var no = bt.parentNode;
		abrirNo( no, bt.getAttribute( 'aria-expanded' ) !== 'true', true );
	} );

	// Teclado: setas movem entre itens visíveis; direita abre, esquerda fecha.
	nav.addEventListener( 'keydown', function ( e ) {
		var el = e.target.closest( '.sgc-nav__item, .sgc-nav__gt' );
		if ( ! el ) { return; }
		var lista = $$( '.sgc-nav__item, .sgc-nav__gt', nav ).filter( function ( b ) { return b.offsetParent !== null; } );
		var i = lista.indexOf( el );
		var k = e.key;
		if ( k === 'ArrowDown' ) { if ( lista[ i + 1 ] ) { lista[ i + 1 ].focus(); } }
		else if ( k === 'ArrowUp' ) { if ( lista[ i - 1 ] ) { lista[ i - 1 ].focus(); } }
		else if ( k === 'Home' ) { lista[0].focus(); }
		else if ( k === 'End' ) { lista[ lista.length - 1 ].focus(); }
		else if ( k === 'ArrowRight' && el.hasAttribute( 'data-alterna' ) && el.getAttribute( 'aria-expanded' ) === 'false' ) { abrirNo( el.parentNode, true, true ); }
		else if ( k === 'ArrowLeft' && el.hasAttribute( 'data-alterna' ) && el.getAttribute( 'aria-expanded' ) === 'true' ) { abrirNo( el.parentNode, false, true ); }
		else if ( k === 'ArrowLeft' && el.classList.contains( 'sgc-nav__sub' ) ) { var p = $( '[data-alterna]', el.closest( '.sgc-nav__no' ) ); if ( p ) { p.focus(); } }
		else { return; }
		e.preventDefault();
	} );

	/* ------------------------------------------------------------ Ir para… */
	var irBusca = $( '[data-ir-busca]' );
	var irLista = $( '[data-ir-lista]' );
	var irVazio = $( '[data-ir-vazio]' );
	var irOps = $$( '.sgc-ir__op', irLista );
	var irAtivo = -1;

	function limpar( t ) { return ( t || '' ).toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ); }
	function irVisiveis() { return irOps.filter( function ( o ) { return ! o.hidden; } ); }
	function irMarcar( n ) {
		var v = irVisiveis();
		irAtivo = v.length ? ( n + v.length ) % v.length : -1;
		irOps.forEach( function ( o ) { o.classList.remove( 'on' ); o.removeAttribute( 'aria-selected' ); } );
		if ( irAtivo > -1 ) {
			v[ irAtivo ].classList.add( 'on' );
			v[ irAtivo ].setAttribute( 'aria-selected', 'true' );
			v[ irAtivo ].scrollIntoView( { block: 'nearest' } );
		}
	}
	function irFiltrar() {
		var q = limpar( irBusca.value ).split( /\s+/ ).filter( Boolean );
		irOps.forEach( function ( o ) {
			var t = limpar( o.getAttribute( 'data-txt' ) );
			o.hidden = ! q.every( function ( p ) { return t.indexOf( p ) > -1; } );
		} );
		irVazio.hidden = irVisiveis().length > 0;
		irMarcar( 0 );
	}
	function irAbrir( abrir ) {
		irLista.hidden = ! abrir;
		irBusca.setAttribute( 'aria-expanded', abrir ? 'true' : 'false' );
	}
	function irEscolher( op ) {
		if ( ! op ) { return; }
		var url = op.getAttribute( 'data-url' );
		irAbrir( false );
		irBusca.value = '';
		if ( url ) { window.location.href = url; return; }
		ir( op.getAttribute( 'data-destino' ) );
		irBusca.blur();
	}
	irBusca.addEventListener( 'focus', function () { irFiltrar(); irAbrir( true ); } );
	irBusca.addEventListener( 'input', function () { irFiltrar(); irAbrir( true ); } );
	irBusca.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'ArrowDown' ) { irAbrir( true ); irMarcar( irAtivo + 1 ); }
		else if ( e.key === 'ArrowUp' ) { irAbrir( true ); irMarcar( irAtivo - 1 ); }
		else if ( e.key === 'Enter' ) { irEscolher( irVisiveis()[ irAtivo ] ); }
		else if ( e.key === 'Escape' ) { irAbrir( false ); irBusca.value = ''; }
		else { return; }
		e.preventDefault();
	} );
	irLista.addEventListener( 'mousedown', function ( e ) {
		var op = e.target.closest( '.sgc-ir__op' );
		if ( op ) { e.preventDefault(); irEscolher( op ); }
	} );
	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '[data-ir-caixa]' ) ) { irAbrir( false ); }
	} );
	irBusca.addEventListener( 'blur', function () { irAbrir( false ); } );

	/* ---------------------------------------------------------- dependências */
	function aplicarSe() {
		$$( '[data-se]', form ).forEach( function ( el ) {
			var cond = {};
			try { cond = JSON.parse( el.getAttribute( 'data-se' ) ); } catch ( e ) {}
			var ok = Object.keys( cond ).every( function ( k ) {
				var campo = document.getElementById( 'sgc-' + k );
				return campo && campo.value === cond[ k ];
			} );
			el.hidden = ! ok;
		} );
	}

	function rotulosChave() {
		$$( '.sgc-chave' ).forEach( function ( l ) {
			var i = $( 'input', l ), t = $( '.sgc-chave__txt', l );
			t.textContent = i.checked ? t.getAttribute( 'data-sim' ) : t.getAttribute( 'data-nao' );
		} );
	}

	/* ------------------------------------------------------- listas e ordem */
	var seqId = 0;
	function renumerar( lista ) {
		var nome = ( lista.getAttribute( 'data-nome' ) || 'item' ).toLowerCase();
		$$( '[data-item]', lista ).forEach( function ( it, i ) {
			var n = $( '[data-n]', it );
			if ( n ) { n.textContent = i + 1; }
			[ [ '[data-sobe]', 'Subir' ], [ '[data-desce]', 'Descer' ], [ '[data-remove]', 'Remover' ] ].forEach( function ( p ) {
				var b = $( p[0], it );
				if ( b ) { b.setAttribute( 'aria-label', p[1] + ' ' + nome + ' ' + ( i + 1 ) ); }
			} );
			// Ids únicos para ligar rótulo e campo (itens novos vêm do modelo).
			$$( '.sgc-campo', it ).forEach( function ( c ) {
				var r = $( '.sgc-rotulo', c ), ctl = $( '[data-sub]', c );
				if ( ! r || ! ctl ) { return; }
				if ( ! ctl.id || document.querySelectorAll( '[id="' + ctl.id + '"]' ).length > 1 ) { ctl.id = 'sgc-n' + ( ++seqId ); }
				r.setAttribute( 'for', ctl.id );
			} );
		} );
	}

	function mover( item, dir ) {
		var irmao = dir < 0 ? item.previousElementSibling : item.nextElementSibling;
		if ( ! irmao ) { return; }
		if ( dir < 0 ) { item.parentNode.insertBefore( item, irmao ); } else { item.parentNode.insertBefore( irmao, item ); }
		var lista = item.closest( '[data-lista]' );
		if ( lista ) { renumerar( lista ); }
	}

	var arrastando = null;
	document.addEventListener( 'dragstart', function ( e ) {
		var it = e.target.closest ? e.target.closest( '[data-item], [data-b]' ) : null;
		if ( ! it || it.getAttribute( 'draggable' ) !== 'true' ) { return; }
		arrastando = it;
		it.classList.add( 'arrastando' );
		e.dataTransfer.effectAllowed = 'move';
		try { e.dataTransfer.setData( 'text/plain', 'x' ); } catch ( x ) {}
	} );
	document.addEventListener( 'dragover', function ( e ) {
		if ( ! arrastando ) { return; }
		var alvo = e.target.closest( arrastando.hasAttribute( 'data-b' ) ? '[data-b]' : '[data-item]' );
		if ( ! alvo || alvo === arrastando || alvo.parentNode !== arrastando.parentNode ) { return; }
		e.preventDefault();
		var r = alvo.getBoundingClientRect();
		var depois = ( e.clientY - r.top ) > r.height / 2;
		alvo.parentNode.insertBefore( arrastando, depois ? alvo.nextSibling : alvo );
	} );
	document.addEventListener( 'dragend', function () {
		if ( ! arrastando ) { return; }
		arrastando.classList.remove( 'arrastando' );
		if ( arrastando.getAttribute( 'data-item' ) !== null ) {
			arrastando.setAttribute( 'draggable', 'false' );
			var l = arrastando.closest( '[data-lista]' );
			if ( l ) { renumerar( l ); }
		}
		arrastando = null;
		agendar();
	} );
	// Itens de lista só arrastam pela alça.
	document.addEventListener( 'mousedown', function ( e ) {
		var alca = e.target.closest ? e.target.closest( '[data-alca]' ) : null;
		var it = alca ? alca.closest( '[data-item]' ) : null;
		if ( it ) { it.setAttribute( 'draggable', 'true' ); }
	} );

	/* --------------------------------------------------------------- imagens */
	function escolherImagem( bloco ) {
		if ( ! window.wp || ! wp.media ) { toast( 'A biblioteca de imagens não carregou. Atualize a página.', 'erro' ); return; }
		var quadro = wp.media( { title: 'Escolher imagem', button: { text: 'Usar esta imagem' }, multiple: false, library: { type: 'image' } } );
		quadro.on( 'select', function () {
			var a = quadro.state().get( 'selection' ).first().toJSON();
			var campo = $( 'input[type=hidden]', bloco );
			campo.value = a.url;
			var prev = $( '.sgc-img__prev', bloco );
			var img = document.createElement( 'img' );
			img.src = a.url;
			img.alt = '';
			prev.textContent = '';
			prev.appendChild( img );
			campo.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );
		quadro.open();
	}

	/* ---------------------------------------------------------------- eventos */
	var seqHex = 0;
	function marcarHex( t, invalido ) {
		var msg = t.parentNode.nextElementSibling;
		if ( ! msg || ! msg.classList.contains( 'sgc-erro' ) ) {
			msg = document.createElement( 'p' );
			msg.className = 'sgc-erro';
			msg.id = 'sgc-hex-erro-' + ( ++seqHex );
			msg.hidden = true;
			msg.textContent = 'Use 6 letras/números, ex.: #1E40AF';
			t.parentNode.parentNode.insertBefore( msg, t.parentNode.nextSibling );
		}
		msg.hidden = ! invalido;
		if ( invalido ) { t.setAttribute( 'aria-invalid', 'true' ); t.setAttribute( 'aria-describedby', msg.id ); }
		else { t.removeAttribute( 'aria-invalid' ); t.removeAttribute( 'aria-describedby' ); }
	}
	// Ao sair do campo, completa o "#" e as cores curtas.
	form.addEventListener( 'focusout', function ( e ) {
		var t = e.target;
		if ( t.classList && t.classList.contains( 'sgc-cor__hex' ) ) {
			var ok = hexValido( t.value );
			if ( ok ) { t.value = ok; marcarHex( t, false ); }
		}
	} );

	form.addEventListener( 'input', function ( e ) {
		var t = e.target;
		if ( t.classList.contains( 'sgc-cor__pick' ) ) { $( '.sgc-cor__hex', t.parentNode ).value = t.value; }
		if ( t.classList.contains( 'sgc-cor__hex' ) ) {
			var ok = hexValido( t.value );
			var vazio = t.value.trim() === '';
			if ( ok ) { $( '.sgc-cor__pick', t.parentNode ).value = ok; }
			marcarHex( t, ! ok && ! vazio );
			if ( ! ok && ! vazio ) { return; } // só salva com cor completa
		}
		aplicarSe();
		agendar();
	} );
	form.addEventListener( 'change', function ( e ) {
		rotulosChave();
		aplicarSe();
		if ( e.target.type === 'checkbox' || e.target.tagName === 'SELECT' ) { agendar(); }
	} );

	form.addEventListener( 'keydown', function ( e ) {
		var a = e.target.closest ? e.target.closest( '.sgc-aba' ) : null;
		if ( ! a || ( e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' ) ) { return; }
		var abas = $$( '.sgc-aba', a.parentNode ), i = abas.indexOf( a ) + ( e.key === 'ArrowRight' ? 1 : -1 );
		var prox = abas[ ( i + abas.length ) % abas.length ];
		prox.focus();
		prox.click();
		e.preventDefault();
	} );

	form.addEventListener( 'click', function ( e ) {
		var t = e.target;
		var bt;

		if ( ( bt = t.closest( '[data-aba]' ) ) ) {
			var sec = bt.closest( '.sgc-sec' ), i = bt.getAttribute( 'data-aba' );
			$$( '.sgc-aba', sec ).forEach( function ( a ) {
				a.classList.toggle( 'on', a === bt );
				a.setAttribute( 'aria-selected', a === bt ? 'true' : 'false' );
				a.tabIndex = a === bt ? 0 : -1;
			} );
			$$( '[data-painel]', sec ).forEach( function ( p ) { p.hidden = p.getAttribute( 'data-painel' ) !== i; } );
			var anc = '#sg-vitrine' + ( parseInt( i, 10 ) + 1 );
			if ( sec.getAttribute( 'data-sec' ) === 'vitrines' ) { sec.setAttribute( 'data-ancora', anc ); abrirPrevia( C.previas.vitrines, anc ); }
			return;
		}
		if ( ( bt = t.closest( '[data-add]' ) ) ) {
			var lista = bt.closest( '[data-lista]' );
			var modelo = $( 'template', lista );
			$( '[data-itens]', lista ).appendChild( modelo.content.cloneNode( true ) );
			renumerar( lista );
			var novo = $$( '[data-item]', lista ).pop();
			if ( novo ) { novo.scrollIntoView( { block: 'nearest', behavior: 'smooth' } ); }
			agendar();
			return;
		}
		if ( ( bt = t.closest( '[data-remove]' ) ) ) {
			var it = bt.closest( '[data-item]' ), l = it.closest( '[data-lista]' );
			if ( window.confirm( 'Remover este item?' ) ) { it.remove(); renumerar( l ); agendar(); }
			return;
		}
		if ( ( bt = t.closest( '[data-sobe]' ) ) ) { mover( bt.closest( '[data-item], [data-b]' ), -1 ); agendar(); return; }
		if ( ( bt = t.closest( '[data-desce]' ) ) ) { mover( bt.closest( '[data-item], [data-b]' ), 1 ); agendar(); return; }
		if ( ( bt = t.closest( '[data-olho]' ) ) ) {
			var li = bt.closest( '[data-b]' );
			var off = li.classList.toggle( 'is-off' );
			bt.setAttribute( 'aria-pressed', off ? 'false' : 'true' );
			agendar();
			return;
		}
		if ( ( bt = t.closest( '[data-img-escolher]' ) ) ) { escolherImagem( bt.closest( '[data-img]' ) ); return; }
		if ( ( bt = t.closest( '[data-img-remover]' ) ) ) {
			var bloco = bt.closest( '[data-img]' );
			$( 'input[type=hidden]', bloco ).value = '';
			$( '.sgc-img__prev', bloco ).innerHTML = '<span>Nenhuma imagem</span>';
			$( 'input[type=hidden]', bloco ).dispatchEvent( new Event( 'input', { bubbles: true } ) );
			return;
		}
		if ( t.closest( '[data-cores-marca]' ) ) {
			Object.keys( C.marca ).forEach( function ( k ) {
				var p = document.getElementById( 'sgc-' + k );
				if ( p ) { p.value = C.marca[ k ]; $( '.sgc-cor__hex', p.parentNode ).value = C.marca[ k ]; }
			} );
			agendar();
			toast( 'Cores da marca aplicadas na prévia.' );
		}
	} );

	/* ---------------------------------------------------------- barra de cima */
	raiz.addEventListener( 'click', function ( e ) {
		var bt;
		if ( ( bt = e.target.closest( '[data-ir]' ) ) ) { ir( bt.getAttribute( 'data-ir' ) ); return; }

		if ( ( bt = e.target.closest( '[data-disp]' ) ) ) {
			disp = bt.getAttribute( 'data-disp' );
			$$( '.sgc-disp__bt' ).forEach( function ( b ) { b.classList.toggle( 'on', b === bt ); } );
			ajustar();
			return;
		}
		if ( ( bt = e.target.closest( '[data-ampliar]' ) ) ) {
			var larga = raiz.classList.toggle( 'previa-larga' );
			bt.setAttribute( 'aria-pressed', larga ? 'true' : 'false' );
			bt.title = larga ? 'Reduzir a prévia' : 'Ampliar a prévia';
			bt.setAttribute( 'aria-label', bt.title );
			setTimeout( ajustar, 50 );
			return;
		}
		if ( e.target.closest( '[data-recarrega]' ) ) { recarregarPrevia( true ); return; }
		if ( e.target.closest( '[data-previa-alterna]' ) ) { raiz.classList.toggle( 'ver-previa' ); setTimeout( ajustar, 50 ); return; }

		if ( ( bt = e.target.closest( '[data-publicar]' ) ) ) {
			clearTimeout( timer );
			refazer = false;
			bt.disabled = true;
			bt.textContent = 'Publicando…';
			enviar( 'sgc_publicar', { dados: JSON.stringify( coletar() ) } ).then( function ( r ) {
				bt.disabled = false;
				bt.textContent = 'Publicar';
				if ( r.success ) {
					dizStatus( 'Tudo publicado.', false, 'Publicado' );
					toast( 'Pronto! O site já está atualizado. 🎉' );
					recarregarPrevia( true );
				} else {
					toast( 'Não consegui publicar. Tente de novo.', 'erro' );
				}
			} ).catch( function () { bt.disabled = false; bt.textContent = 'Publicar'; toast( 'Sem conexão.', 'erro' ); } );
			return;
		}
		if ( e.target.closest( '[data-descartar]' ) ) {
			// Nenhum salvamento automático pode gravar de novo depois do descarte.
			clearTimeout( timer );
			refazer = false;
			if ( ! window.confirm( 'Descartar tudo o que ainda não foi publicado?' ) ) { return; }
			clearTimeout( timer );
			refazer = false;
			travado = true;
			var espera = salvando && emAndamento ? emAndamento.catch( function () {} ) : Promise.resolve();
			espera.then( function () { return enviar( 'sgc_descartar' ); } ).then( function () { window.location.reload(); } );
		}
	} );

	window.addEventListener( 'resize', ajustar );
	if ( window.ResizeObserver ) { new ResizeObserver( ajustar ).observe( palco ); }

	/* ------------------------------------------------------------------ início */
	rotulosChave();
	aplicarSe();
	ajustar();

	var inicial = ( location.hash || '' ).replace( '#', '' );
	if ( ! $( '[data-sec="' + inicial + '"]', form ) ) {
		try { inicial = localStorage.getItem( 'sgc_secao' ) || ''; } catch ( e ) { inicial = ''; }
	}
	if ( ! $( '[data-sec="' + inicial + '"]', form ) ) { inicial = raiz.getAttribute( 'data-primeira' ); }
	ir( inicial );

	// Links antigos (#id) continuam funcionando se o endereço mudar com a tela aberta.
	window.addEventListener( 'hashchange', function () {
		var h = ( location.hash || '' ).replace( '#', '' );
		if ( h && h !== secaoAtual ) { ir( h ); }
	} );
} )();
