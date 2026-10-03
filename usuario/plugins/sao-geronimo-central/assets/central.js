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

	var LARG = { desktop: 1366, tablet: 820, mobile: 390 };
	var disp = 'desktop';
	var previaUrl = '';
	var ancoraPendente = '';
	var scrollPendente = null;
	var secaoAtual = '';
	var timer = null;
	var salvando = false;
	var refazer = false;

	/* ------------------------------------------------------------ avisos */
	function toast( msg, tipo ) {
		toastEl.textContent = msg;
		toastEl.className = 'sgc-aviso-toast on' + ( tipo ? ' ' + tipo : '' );
		clearTimeout( toast.t );
		toast.t = setTimeout( function () { toastEl.classList.remove( 'on' ); }, 3200 );
	}

	function dizStatus( msg, sujo ) {
		status.textContent = msg;
		status.classList.toggle( 'sujo', !! sujo );
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
			return hex ? hex.value.trim() : el.value;
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

	function salvarRascunho() {
		if ( salvando ) { refazer = true; return; }
		salvando = true;
		dizStatus( 'Salvando…', true );
		enviar( 'sgc_rascunho', { dados: JSON.stringify( coletar() ) } ).then( function ( r ) {
			salvando = false;
			if ( ! r.success ) { dizStatus( 'Não consegui salvar. Tente de novo.', true ); toast( 'Não consegui salvar.', 'erro' ); return; }
			var n = r.data.mudancas;
			dizStatus( n ? 'Alterações prontas na prévia. Clique em Publicar para colocar no ar.' : 'Tudo publicado.', n > 0 );
			recarregarPrevia( true );
			if ( refazer ) { refazer = false; salvarRascunho(); }
		} ).catch( function () {
			salvando = false;
			dizStatus( 'Sem conexão. Tente de novo.', true );
		} );
	}

	function agendar() {
		dizStatus( 'Alterando…', true );
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
	function ir( id, semPrevia ) {
		var sec = $( '[data-sec="' + id + '"]', form );
		if ( ! sec ) { return; }
		$$( '.sgc-sec', form ).forEach( function ( s ) { s.hidden = s !== sec; } );
		$$( '.sgc-nav__item[data-ir]' ).forEach( function ( b ) { b.classList.toggle( 'on', b.getAttribute( 'data-ir' ) === id ); } );
		form.scrollTop = 0;
		secaoAtual = id;
		try { history.replaceState( null, '', '#' + id ); localStorage.setItem( 'sgc_secao', id ); } catch ( e ) {}
		raiz.classList.remove( 'ver-previa' );
		if ( ! semPrevia ) { abrirPrevia( C.previas[ id ], sec.getAttribute( 'data-ancora' ) ); }
	}

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
	function renumerar( lista ) {
		$$( '[data-item]', lista ).forEach( function ( it, i ) { var n = $( '[data-n]', it ); if ( n ) { n.textContent = i + 1; } } );
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
			$( '.sgc-img__prev', bloco ).innerHTML = '<img src="' + a.url.replace( /"/g, '' ) + '" alt="">';
			campo.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );
		quadro.open();
	}

	/* ---------------------------------------------------------------- eventos */
	form.addEventListener( 'input', function ( e ) {
		var t = e.target;
		if ( t.classList.contains( 'sgc-cor__pick' ) ) { $( '.sgc-cor__hex', t.parentNode ).value = t.value; }
		if ( t.classList.contains( 'sgc-cor__hex' ) && /^#[0-9a-fA-F]{6}$/.test( t.value ) ) { $( '.sgc-cor__pick', t.parentNode ).value = t.value; }
		aplicarSe();
		agendar();
	} );
	form.addEventListener( 'change', function ( e ) {
		rotulosChave();
		aplicarSe();
		if ( e.target.type === 'checkbox' || e.target.tagName === 'SELECT' ) { agendar(); }
	} );

	form.addEventListener( 'click', function ( e ) {
		var t = e.target;
		var bt;

		if ( ( bt = t.closest( '[data-aba]' ) ) ) {
			var sec = bt.closest( '.sgc-sec' ), i = bt.getAttribute( 'data-aba' );
			$$( '.sgc-aba', sec ).forEach( function ( a ) { a.classList.toggle( 'on', a === bt ); } );
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
		if ( e.target.closest( '[data-recarrega]' ) ) { recarregarPrevia( true ); return; }
		if ( e.target.closest( '[data-previa-alterna]' ) ) { raiz.classList.toggle( 'ver-previa' ); setTimeout( ajustar, 50 ); return; }

		if ( ( bt = e.target.closest( '[data-publicar]' ) ) ) {
			clearTimeout( timer );
			bt.disabled = true;
			bt.textContent = 'Publicando…';
			enviar( 'sgc_publicar', { dados: JSON.stringify( coletar() ) } ).then( function ( r ) {
				bt.disabled = false;
				bt.textContent = 'Publicar';
				if ( r.success ) {
					dizStatus( 'Tudo publicado.', false );
					toast( 'Pronto! O site já está atualizado. 🎉' );
					recarregarPrevia( true );
				} else {
					toast( 'Não consegui publicar. Tente de novo.', 'erro' );
				}
			} ).catch( function () { bt.disabled = false; bt.textContent = 'Publicar'; toast( 'Sem conexão.', 'erro' ); } );
			return;
		}
		if ( e.target.closest( '[data-descartar]' ) ) {
			if ( ! window.confirm( 'Descartar tudo o que ainda não foi publicado?' ) ) { return; }
			enviar( 'sgc_descartar' ).then( function () { window.location.reload(); } );
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
} )();
