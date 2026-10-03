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

	// No computador, marcar uma caixinha já filtra. No celular, só no botão.
	var timer;
	form.addEventListener( 'change', function ( e ) {
		if ( window.matchMedia( '(max-width: 900px)' ).matches ) { return; }
		if ( e.target.type !== 'checkbox' ) { return; }
		clearTimeout( timer );
		timer = setTimeout( function () { form.requestSubmit(); }, 350 );
	} );
} )();
