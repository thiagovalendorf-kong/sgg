/* Painel São Gerônimo — comportamento do administrativo. Sem dependências. */
(function () {
  "use strict";

  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  /* ------------------------------------------------------------- imagens */
  let quadro = null;
  document.addEventListener("click", (e) => {
    const abrir = e.target.closest("[data-img-escolher]");
    if (abrir) {
      e.preventDefault();
      const caixa = abrir.closest("[data-img]");
      if (!window.wp || !wp.media) return;

      quadro = wp.media({
        title: (window.SGP && SGP.escolher) || "Escolher imagem",
        button: { text: (window.SGP && SGP.usar) || "Usar esta imagem" },
        library: { type: "image" },
        multiple: false,
      });

      quadro.on("select", () => {
        const a = quadro.state().get("selection").first().toJSON();
        const url = (a.sizes && a.sizes.large ? a.sizes.large.url : a.url);
        caixa.querySelector("[data-img-valor]").value = url;
        const id = caixa.querySelector("[data-img-id]");
        if (id) id.value = a.id;
        caixa.querySelector(".sgp-img__visor").innerHTML =
          '<img src="' + url + '" alt="">';
        caixa.classList.add("tem");
      });

      quadro.open();
      return;
    }

    const tirar = e.target.closest("[data-img-tirar]");
    if (tirar) {
      e.preventDefault();
      const caixa = tirar.closest("[data-img]");
      caixa.querySelector("[data-img-valor]").value = "";
      const id = caixa.querySelector("[data-img-id]");
      if (id) id.value = "";
      caixa.querySelector(".sgp-img__visor").innerHTML = "<span>—</span>";
      caixa.classList.remove("tem");
    }
  });

  /* ---------------------------------------------------------------- cores */
  $$("[data-espelho]").forEach((pick) => {
    const txt = document.getElementById(pick.dataset.espelho);
    if (!txt) return;
    pick.addEventListener("input", () => { txt.value = pick.value.toUpperCase(); });
    txt.addEventListener("input", () => {
      if (/^#[0-9a-f]{6}$/i.test(txt.value)) pick.value = txt.value;
    });
  });

  /* ------------------------------------------------------------ repetidor */
  document.addEventListener("click", (e) => {
    const add = e.target.closest("[data-rep-add]");
    if (add) {
      e.preventDefault();
      const rep = add.closest("[data-rep]");
      const modelo = rep.querySelector("[data-rep-modelo]");
      const lista = rep.querySelector("[data-rep-lista]");
      const i = lista.children.length + "_" + Date.now().toString(36);
      const div = document.createElement("div");
      div.innerHTML = modelo.innerHTML.replace(/__i__/g, i).trim();
      lista.appendChild(div.firstElementChild);
      lista.lastElementChild.querySelector("input, textarea")?.focus();
      return;
    }

    const tirar = e.target.closest("[data-rep-tirar]");
    if (tirar) {
      e.preventDefault();
      const item = tirar.closest("[data-rep-item]");
      const campos = [...item.querySelectorAll("input, textarea")]
        .filter((c) => c.type !== "hidden" && c.value.trim() !== "");
      if (campos.length && !confirm("Remover este item?")) return;
      item.remove();
    }
  });

  /* --------------------------------------------------- arrastar para ordenar */
  function arrastavel(lista, seletor) {
    let pego = null;

    lista.addEventListener("dragstart", (e) => {
      pego = e.target.closest(seletor);
      if (!pego) return;
      pego.classList.add("arrastando");
      e.dataTransfer.effectAllowed = "move";
      try { e.dataTransfer.setData("text/plain", ""); } catch (x) { /* IE */ }
    });

    lista.addEventListener("dragend", () => {
      pego?.classList.remove("arrastando");
      $$(seletor, lista).forEach((x) => x.classList.remove("alvo"));
      pego = null;
    });

    lista.addEventListener("dragover", (e) => {
      if (!pego) return;
      e.preventDefault();
      const alvo = e.target.closest(seletor);
      if (!alvo || alvo === pego) return;
      $$(seletor, lista).forEach((x) => x.classList.remove("alvo"));
      alvo.classList.add("alvo");
      const r = alvo.getBoundingClientRect();
      const depois = (e.clientY - r.top) > r.height / 2;
      alvo.parentNode.insertBefore(pego, depois ? alvo.nextSibling : alvo);
    });
  }

  $$("[data-ordem]").forEach((l) => arrastavel(l, "li"));
  $$("[data-rep-lista]").forEach((l) => {
    arrastavel(l, "[data-rep-item]");
    // a alça é que arrasta, não o item inteiro (senão não dá para selecionar texto)
    l.addEventListener("mousedown", (e) => {
      const item = e.target.closest("[data-rep-item]");
      if (!item) return;
      item.draggable = !!e.target.closest(".sgp-pega");
    });
  });

  /* ================================= CAIXA DE SEO ======================== */
  const caixaSeo = document.querySelector(".sgp-seo-caixa");
  if (!caixaSeo) return;

  // abas
  $$("[data-seo-aba]", caixaSeo).forEach((b) => {
    b.addEventListener("click", () => {
      $$("[data-seo-aba]", caixaSeo).forEach((x) => x.classList.toggle("on", x === b));
      $$("[data-seo-painel]", caixaSeo).forEach((p) =>
        p.classList.toggle("on", p.dataset.seoPainel === b.dataset.seoAba));
    });
  });

  // prévia do Google + contadores
  const campoTit = caixaSeo.querySelector('[data-previa-fonte="tit"]');
  const campoDesc = caixaSeo.querySelector('[data-previa-fonte="desc"]');
  const previaTit = caixaSeo.querySelector("[data-previa-tit]");
  const previaDesc = caixaSeo.querySelector("[data-previa-desc]");
  const contaTit = caixaSeo.querySelector('[data-conta="titulo"]');
  const contaDesc = caixaSeo.querySelector('[data-conta="descricao"]');
  const campoChave = caixaSeo.querySelector("[data-chave]");
  const painelAnalise = caixaSeo.querySelector("[data-analise]");

  const tituloDoPost = () =>
    document.getElementById("title")?.value ||
    document.querySelector(".editor-post-title__input")?.textContent ||
    document.querySelector('[name="post_title"]')?.value || "";

  function conta(el, campo, texto) {
    if (!el) return;
    const ideal = +el.dataset.ideal;
    const n = texto.length;
    el.textContent = n + "/" + ideal;
    el.className = n === 0 ? "" : (n > ideal ? "longo" : (n > ideal * 0.6 ? "bom" : ""));
  }

  function semAcento(s) {
    return s.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();
  }

  function corpoDoPost() {
    const ta = document.getElementById("content");
    if (ta && ta.value) return ta.value;
    const tiny = window.tinymce && tinymce.get("content");
    if (tiny) return tiny.getContent({ format: "text" });
    const blocos = document.querySelector(".block-editor-writing-flow");
    return blocos ? blocos.textContent : "";
  }

  function analisa() {
    if (!painelAnalise) return;
    const chave = (campoChave?.value || "").trim();
    if (!chave) {
      painelAnalise.innerHTML = '<p class="sgp-dica">Preencha a palavra-chave para ver a análise.</p>';
      return;
    }

    const k = semAcento(chave);
    const tit = semAcento(campoTit?.value || tituloDoPost());
    const desc = semAcento(campoDesc?.value || "");
    const corpo = semAcento(corpoDoPost());
    const palavras = corpo.split(/\s+/).filter(Boolean).length;
    const vezes = k ? (corpo.split(k).length - 1) : 0;
    const dens = palavras ? (vezes * k.split(" ").length / palavras * 100) : 0;

    const itens = [];
    const diz = (nivel, txt) => itens.push({ nivel, txt });

    diz(tit.includes(k) ? "bom" : "ruim",
      tit.includes(k)
        ? "A palavra-chave aparece no título. Bom."
        : "A palavra-chave não está no título. Esse é o sinal mais forte para o Google.");

    const nTit = (campoTit?.value || tituloDoPost()).length;
    diz(nTit === 0 ? "ruim" : (nTit > 60 ? "meio" : "bom"),
      nTit === 0 ? "Sem título de SEO — usaremos o título da página."
        : (nTit > 60 ? "O título tem " + nTit + " caracteres e vai ser cortado no Google. Encurte para até 60."
          : "Título com bom tamanho (" + nTit + " caracteres)."));

    const nDesc = (campoDesc?.value || "").length;
    diz(nDesc === 0 ? "ruim" : (nDesc > 155 ? "meio" : (nDesc < 70 ? "meio" : "bom")),
      nDesc === 0 ? "Falta a descrição. Sem ela, o Google inventa um trecho da página."
        : (nDesc > 155 ? "Descrição com " + nDesc + " caracteres: vai ser cortada. Encurte para até 155."
          : (nDesc < 70 ? "Descrição curta (" + nDesc + "). Aproveite até 155 para convencer."
            : "Descrição com bom tamanho (" + nDesc + ").")));

    diz(desc.includes(k) ? "bom" : "meio",
      desc.includes(k) ? "A palavra-chave está na descrição."
        : "Ponha a palavra-chave na descrição — ela aparece em negrito no Google.");

    if (palavras > 30) {
      diz(vezes === 0 ? "ruim" : (dens > 3 ? "meio" : "bom"),
        vezes === 0 ? "A palavra-chave não aparece no texto da página."
          : (dens > 3 ? "A palavra-chave aparece demais (" + vezes + "x). Soa forçado; varie com sinônimos."
            : "A palavra-chave aparece " + vezes + "x no texto. Natural."));

      diz(palavras < 150 ? "meio" : "bom",
        palavras < 150 ? "Texto curto (" + palavras + " palavras). Páginas com mais contexto rendem melhor."
          : "Texto com " + palavras + " palavras.");
    }

    const temImg = !!document.querySelector("#set-post-thumbnail img, .editor-post-featured-image img");
    diz(temImg ? "bom" : "meio",
      temImg ? "Tem imagem destacada." : "Sem imagem destacada — ela é usada no Google e no WhatsApp.");

    painelAnalise.innerHTML = itens.map((i) =>
      '<div class="item ' + i.nivel + '"><span class="bolha"></span><span>' + i.txt + "</span></div>"
    ).join("");
  }

  function atualiza() {
    const t = (campoTit?.value || "").trim() || tituloDoPost() || "(sem título)";
    const d = (campoDesc?.value || "").trim();
    if (previaTit) previaTit.textContent = t;
    if (previaDesc) previaDesc.textContent = d || "O Google vai escolher um trecho da página quando este campo estiver vazio.";
    conta(contaTit, "titulo", campoTit?.value || "");
    conta(contaDesc, "descricao", campoDesc?.value || "");
    analisa();
  }

  [campoTit, campoDesc, campoChave].forEach((c) => c && c.addEventListener("input", atualiza));
  atualiza();
  setTimeout(atualiza, 1200);   // espera o editor carregar
})();
