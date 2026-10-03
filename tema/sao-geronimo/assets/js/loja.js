/* Loja: gaveta de filtros no celular e envio automático ao marcar. */
( function () {
	var corpo = document.body;
	var form = document.querySelector( '[data-filtros-form]' );

	function abrir() { corpo.classList.add( 'filtros-aberto' ); }
	function fechar() { corpo.classList.remove( 'filtros-aberto' ); }

	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-filtros-abrir]' ) ) { abrir(); }
		if ( e.target.closest( '[data-filtros-fechar]' ) ) { fechar(); }
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) { fechar(); }
	} );

	if ( ! form ) { return; }

	// Tira do endereço os campos vazios, para o link ficar limpo.
	form.addEventListener( 'submit', function () {
		Array.prototype.forEach.call( form.querySelectorAll( 'input[type=number]' ), function ( i ) {
			if ( i.value === '' ) { i.disabled = true; }
		} );
	} );

	// Celular: abre só a primeira seção ao entrar.
	if ( window.matchMedia( '(max-width: 900px)' ).matches ) {
		var abertas = form.querySelectorAll( 'details.filtro[open]' );
		for ( var n = 1; n < abertas.length; n++ ) { abertas[ n ].open = false; }
	}

	// Faixa pronta e campos digitados não valem juntos.
	form.addEventListener( 'change', function ( e ) {
		if ( e.target.name === 'sg_faixa' ) {
			Array.prototype.forEach.call( form.querySelectorAll( 'input[type=number]' ), function ( i ) { i.value = ''; } );
		}
	} );
	form.addEventListener( 'input', function ( e ) {
		if ( e.target.type === 'number' ) {
			Array.prototype.forEach.call( form.querySelectorAll( 'input[name=sg_faixa]' ), function ( r ) { r.checked = false; } );
		}
	} );

	var celular = function () { return window.matchMedia( '(max-width: 900px)' ).matches; };

	// No celular, só uma seção fica aberta por vez (a folha nunca precisa rolar muito).
	form.addEventListener( 'toggle', function ( e ) {
		if ( ! celular() || ! e.target.open ) { return; }
		Array.prototype.forEach.call( form.querySelectorAll( 'details.filtro' ), function ( d ) { if ( d !== e.target ) { d.open = false; } } );
	}, true );

	// No computador, marcar uma opção já filtra. No celular, só no botão "Ver produtos".
	var timer;
	form.addEventListener( 'change', function ( e ) {
		if ( celular() ) { return; }
		if ( e.target.type !== 'checkbox' && e.target.type !== 'radio' ) { return; }
		clearTimeout( timer );
		timer = setTimeout( function () { form.requestSubmit(); }, 300 );
	} );
} )();
