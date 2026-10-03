/* Atualizar produtos pelo catálogo — envio, simulação e aplicação em lotes. */
( function () {
	'use strict';
	var C = window.SGCCAT;
	var raiz = document.getElementById( 'sgcat' );
	if ( ! C || ! raiz ) { return; }

	function $( id ) { return document.getElementById( id ); }
	function el( tag, cls, txt ) {
		var e = document.createElement( tag );
		if ( cls ) { e.className = cls; }
		if ( txt !== undefined ) { e.textContent = txt; }
		return e;
	}
	function limpar( n ) { while ( n.firstChild ) { n.removeChild( n.firstChild ); } }
	// Os números dos passos acompanham só os cartões que estão à mostra (1, 2, 3...).
	function renumerar() {
		var n = 0;
		Array.prototype.forEach.call( raiz.querySelectorAll( '.sgcat__cartao' ), function ( c ) {
			var num = c.querySelector( '.sgcat__num' );
			if ( c.hidden || ! num ) { return; }
			n++;
			num.textContent = String( n );
		} );
	}
	function mostrar( n, sim ) { n.hidden = ! sim; renumerar(); }
	function msgErro( t ) { var e = $( 'sgcat-erro' ); e.textContent = t || ''; mostrar( e, !! t ); }

	function chamar( acao, dados ) {
		var corpo = new URLSearchParams();
		corpo.set( 'action', acao );
		corpo.set( 'nonce', C.nonce );
		Object.keys( dados || {} ).forEach( function ( k ) { corpo.set( k, dados[ k ] ); } );
		return fetch( C.ajax, { method: 'POST', credentials: 'same-origin', body: corpo } )
			.then( function ( r ) { return r.json().catch( function () { throw new Error( 'O servidor respondeu algo inesperado. Tente de novo.' ); } ); } )
			.then( function ( j ) {
				if ( ! j || ! j.success ) { throw new Error( ( j && j.data && j.data.mensagem ) || 'Não deu certo. Tente de novo.' ); }
				return j.data;
			} );
	}

	function opcoes() {
		return {
			sobrescrever: $( 'sgcat-o-sobrescrever' ).checked ? '1' : '0',
			precos: $( 'sgcat-o-precos' ).checked ? '1' : '0',
			medidas: $( 'sgcat-o-medidas' ).checked ? '1' : '0'
		};
	}

	var simulado = false;
	function invalidarSim( mudou ) {
		simulado = false;
		$( 'sgcat-aplicar' ).disabled = true;
		$( 'sgcat-aplicar-nota' ).textContent = mudou ? 'Você mudou as opções: rode a simulação de novo para liberar este botão.' : 'Rode a simulação para liberar este botão.';
	}

	/* ---------- passo 2: envio ---------- */
	function mostrarCarregado( c ) {
		var b = $( 'sgcat-carregado' );
		if ( ! c ) { mostrar( b, false ); mostrar( $( 'sgcat-passo3' ), false ); return; }
		b.textContent = 'Catálogo pronto: ' + c.total + ' produtos' + ( c.quando ? ' (enviado em ' + c.quando + ')' : '' ) + ( c.invalidos ? '. ' + c.invalidos + ' item(ns) sem SKU ou repetido(s) foram ignorados.' : '.' );
		mostrar( b, true );
		mostrar( $( 'sgcat-passo3' ), true );
	}

	$( 'sgcat-arquivo' ).addEventListener( 'change', function () {
		var f = this.files[ 0 ];
		$( 'sgcat-nome' ).textContent = f ? f.name + ' (' + Math.round( f.size / 1024 ) + ' KB)' : 'Nenhum arquivo escolhido';
	} );

	$( 'sgcat-enviar' ).addEventListener( 'click', function () {
		var bt = this;
		var arq = $( 'sgcat-arquivo' ).files[ 0 ];
		var colado = $( 'sgcat-texto' ).value;
		msgErro( '' );
		function enviar( texto ) {
			if ( texto.length > C.maxBytes ) { msgErro( 'O arquivo tem mais de 5 MB. Envie o catalogo.json original.' ); bt.disabled = false; return; }
			chamar( 'sgc_cat_enviar', { json: texto } ).then( function ( d ) {
				mostrarCarregado( d.carregado );
				limpar( $( 'sgcat-sim' ) );
				mostrar( $( 'sgcat-passo4' ), false );
				mostrar( $( 'sgcat-passo5' ), false );
				limpar( $( 'sgcat-rel' ) );
				mostrar( $( 'sgcat-linha-csv' ), false );
				mostrar( $( 'sgcat-linha-ultimo' ), false );
				C.final = null;
				invalidarSim();
			} ).catch( function ( e ) { msgErro( e.message ); } ).then( function () { bt.disabled = false; } );
		}
		bt.disabled = true;
		if ( arq ) {
			if ( arq.size > C.maxBytes ) { msgErro( 'O arquivo tem mais de 5 MB. Envie o catalogo.json original.' ); bt.disabled = false; return; }
			var leitor = new FileReader();
			leitor.onload = function () { enviar( String( leitor.result ) ); };
			leitor.onerror = function () { msgErro( 'Não consegui abrir o arquivo.' ); bt.disabled = false; };
			leitor.readAsText( arq, 'utf-8' );
		} else if ( colado.trim() ) {
			enviar( colado );
		} else {
			msgErro( 'Escolha o arquivo catalogo.json ou cole o conteúdo dele.' );
			bt.disabled = false;
		}
	} );

	/* ---------- passo 3 e 4: simulação ---------- */
	[ 'sgcat-o-sobrescrever', 'sgcat-o-precos', 'sgcat-o-medidas' ].forEach( function ( id ) {
		$( id ).addEventListener( 'change', function () { if ( simulado ) { invalidarSim( true ); } } );
	} );

	function cifra( n, txt, cls ) {
		var d = el( 'div', 'sgcat__cifra' + ( cls ? ' sgcat__cifra--' + cls : '' ) );
		d.appendChild( el( 'b', '', String( n ) ) );
		d.appendChild( el( 'span', '', txt ) );
		return d;
	}

	function desenharSim( s ) {
		var r = $( 'sgcat-sim' );
		limpar( r );
		var g = el( 'div', 'sgcat__cifras' );
		g.appendChild( cifra( s.achados, 'achados pelo SKU', 'bom' ) );
		g.appendChild( cifra( s.nao_achados.length, 'não achados', s.nao_achados.length ? 'mau' : '' ) );
		g.appendChild( cifra( s.a_mudar, 'serão alterados', 'bom' ) );
		g.appendChild( cifra( s.sem_mudanca, 'já estão iguais' ) );
		r.appendChild( g );
		if ( s.repetidos.length ) {
			r.appendChild( el( 'div', 'sgcat__aviso sgcat__aviso--aviso', 'Atenção: estes SKUs existem em mais de um produto da loja (só o mais antigo será atualizado): ' + s.repetidos.join( ', ' ) ) );
		}
		if ( s.campos.length ) {
			r.appendChild( el( 'h3', '', 'O que será preenchido' ) );
			var ul = el( 'ul', 'sgcat__chips' );
			s.campos.forEach( function ( c ) { ul.appendChild( el( 'li', '', c.campo + ': ' + c.qtd ) ); } );
			r.appendChild( ul );
		}
		if ( s.amostra.length ) {
			r.appendChild( el( 'h3', '', 'Exemplos (antes → depois)' ) );
			s.amostra.forEach( function ( a ) {
				var box = el( 'div', 'sgcat__amostra' );
				box.appendChild( el( 'b', '', a.nome + ' (SKU ' + a.sku + ')' ) );
				var t = el( 'table' );
				a.linhas.forEach( function ( l ) {
					var tr = el( 'tr' );
					tr.appendChild( el( 'th', '', l.campo ) );
					tr.appendChild( el( 'td', 'sgcat__antes', 'Antes: ' + ( l.antes || '(vazio)' ) ) );
					tr.appendChild( el( 'td', 'sgcat__depois', 'Depois: ' + l.depois ) );
					t.appendChild( tr );
				} );
				box.appendChild( t );
				r.appendChild( box );
			} );
		}
		if ( s.nao_achados.length ) {
			r.appendChild( el( 'h3', '', 'SKUs do catálogo que não existem na loja (' + s.nao_achados.length + ')' ) );
			var cx = el( 'div', 'sgcat__skus' );
			var ul2 = el( 'ul', 'sgcat__skus-lista' );
			s.nao_achados.forEach( function ( k ) { ul2.appendChild( el( 'li', '', k ) ); } );
			cx.appendChild( ul2 );
			var copiar = el( 'button', 'sgcat__bt sgcat__bt--mini', 'Copiar lista' );
			copiar.type = 'button';
			copiar.addEventListener( 'click', function () {
				var txt = s.nao_achados.join( '\n' );
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( txt ).then( function () { copiar.textContent = 'Lista copiada'; }, function () { copiar.textContent = 'Não consegui copiar'; } );
				} else { copiar.textContent = 'Não consegui copiar'; }
			} );
			r.appendChild( cx );
			r.appendChild( copiar );
		}
		$( 'sgcat-aplicar' ).disabled = s.a_mudar === 0;
		$( 'sgcat-aplicar-nota' ).textContent = s.a_mudar === 0 ? 'Não há nada para alterar com estas opções.' : 'Os produtos são atualizados de 25 em 25. Se a página fechar no meio, dá para continuar de onde parou.';
	}

	$( 'sgcat-simular' ).addEventListener( 'click', function () {
		var bt = this;
		bt.disabled = true;
		bt.textContent = 'Simulando…';
		msgErro( '' );
		chamar( 'sgc_cat_simular', opcoes() ).then( function ( s ) {
			simulado = true;
			desenharSim( s );
			mostrar( $( 'sgcat-passo4' ), true );
			$( 'sgcat-passo4' ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
		} ).catch( function ( e ) { msgErro( e.message ); } ).then( function () { bt.disabled = false; bt.textContent = 'Simular (não altera nada)'; } );
	} );

	/* ---------- passo 5: aplicar em lotes ---------- */
	var rodando = false;
	function barra( pos, total ) {
		var p = total ? Math.round( pos * 100 / total ) : 0;
		$( 'sgcat-barra-i' ).style.width = p + '%';
		$( 'sgcat-barra' ).setAttribute( 'aria-valuenow', String( p ) );
		$( 'sgcat-prog' ).textContent = pos + ' de ' + total + ' produtos conferidos (' + p + '%)';
	}

	function relatorio( r ) {
		var box = $( 'sgcat-rel' );
		limpar( box );
		var c = r.contagens;
		var g = el( 'div', 'sgcat__cifras' );
		g.appendChild( cifra( c.alterado, 'produtos atualizados', 'bom' ) );
		g.appendChild( cifra( c.sem_mudanca, 'já estavam iguais' ) );
		g.appendChild( cifra( c.nao_achado, 'SKUs não achados', c.nao_achado ? 'mau' : '' ) );
		g.appendChild( cifra( c.erro, 'erros', c.erro ? 'mau' : '' ) );
		box.appendChild( el( 'div', 'sgcat__aviso sgcat__aviso--ok', 'Pronto! A atualização terminou.' ) );
		box.appendChild( g );
		if ( r.problemas.length ) {
			box.appendChild( el( 'h3', '', 'Problemas encontrados (' + r.problemas_total + ')' ) );
			var q = el( 'div', 'sgcat__skus' );
			r.problemas.forEach( function ( p ) { q.appendChild( el( 'div', '', p.sku + ' — ' + p.nome + ': ' + p.texto ) ); } );
			box.appendChild( q );
		}
		$( 'sgcat-csv' ).href = C.csv;
		mostrar( $( 'sgcat-linha-csv' ), true );
		mostrar( $( 'sgcat-retomar' ), false );
	}

	function lote( inicio ) {
	rodando = true;
	$( 'sgcat-continuar' ).disabled = true;
		var dados = opcoes();
		if ( inicio ) { dados.inicio = '1'; } else if ( C.andamento ) { dados = { sobrescrever: C.andamento.opcoes.sobrescrever ? '1' : '0', precos: C.andamento.opcoes.precos ? '1' : '0', medidas: C.andamento.opcoes.medidas ? '1' : '0' }; }
		chamar( 'sgc_cat_aplicar', dados ).then( function ( d ) {
			barra( d.pos, d.total );
			C.andamento = d.status === 'andamento' ? d : null;
			if ( d.status === 'andamento' ) { return lote( false ); }
			rodando = false;
			$( 'sgcat-continuar' ).disabled = false;
			$( 'sgcat-aplicar' ).disabled = false;
			relatorio( d.relatorio );
		} ).catch( function ( e ) {
			rodando = false;
			$( 'sgcat-continuar' ).disabled = false;
			msgErro( e.message + ' Clique em "Continuar de onde parou" para retomar.' );
			mostrar( $( 'sgcat-retomar' ), true );
		} );
	}

	$( 'sgcat-aplicar' ).addEventListener( 'click', function () {
		if ( rodando || ! simulado ) { return; }
		if ( ! window.confirm( 'Aplicar a atualização nos produtos da loja agora?' ) ) { return; }
		this.disabled = true;
		msgErro( '' );
		limpar( $( 'sgcat-rel' ) );
		mostrar( $( 'sgcat-linha-csv' ), false );
		mostrar( $( 'sgcat-passo5' ), true );
		barra( 0, 1 );
		$( 'sgcat-passo5' ).scrollIntoView( { behavior: 'smooth', block: 'start' });
		lote( true );
	} );

	$( 'sgcat-continuar' ).addEventListener( 'click', function () {
		if ( rodando ) { return; }
		msgErro( '' );
		mostrar( $( 'sgcat-retomar' ), false );
		lote( false );
	} );

	$( 'sgcat-recomecar' ).addEventListener( 'click', function () {
		if ( rodando ) { return; }
		chamar( 'sgc_cat_zerar', {} ).then( function () {
			C.andamento = null;
			mostrar( $( 'sgcat-passo5' ), false );
			msgErro( '' );
		} ).catch( function ( e ) { msgErro( e.message ); } );
	} );

	/* ---------- ao abrir a página ---------- */
	mostrarCarregado( C.carregado );
	invalidarSim();
	if ( C.andamento ) {
		mostrar( $( 'sgcat-passo5' ), true );
		barra( C.andamento.pos, C.andamento.total );
		$( 'sgcat-prog' ).textContent += ' — a atualização ficou pela metade.';
		mostrar( $( 'sgcat-retomar' ), true );
	} else if ( C.final ) {
		// Relatório antigo não ocupa a tela: fica atrás de um botão.
		mostrar( $( 'sgcat-linha-ultimo' ), true );
	}
	$( 'sgcat-ver-ultimo' ).addEventListener( 'click', function () {
		if ( ! C.final ) { return; }
		mostrar( $( 'sgcat-passo5' ), true );
		barra( C.final.total, C.final.total );
		relatorio( C.final );
		mostrar( $( 'sgcat-linha-ultimo' ), false );
		$( 'sgcat-passo5' ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
	} );
	renumerar();
} )();
