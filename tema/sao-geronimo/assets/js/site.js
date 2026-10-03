/* São Gerônimo — comportamento do site. Sem dependências, sem jQuery.
   O carrinho é do WooCommerce; aqui só cuidamos da interface. */
(() => {
  "use strict";

  const W = window.SG_WP || {};
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  /* ------------------------------------------------------------ banner --- */
  const hero = $("[data-hero]");
  if (hero) {
    const trilho = $("[data-trilho]", hero);
    const slides = trilho ? [...trilho.children] : [];
    const pontos = $$("[data-ir]", hero);
    let i = 0, timer = null;

    const ir = (n) => {
      i = (n + slides.length) % slides.length;
      if (trilho) trilho.style.transform = `translateX(-${i * 100}%)`;
      pontos.forEach((p, k) => p.classList.toggle("on", k === i));
    };
    const roda = () => {
      clearInterval(timer);
      const t = parseInt(hero.dataset.t || "7000", 10);
      if (slides.length > 1 && !matchMedia("(prefers-reduced-motion: reduce)").matches) {
        timer = setInterval(() => ir(i + 1), t);
      }
    };

    $("[data-prox]", hero)?.addEventListener("click", () => { ir(i + 1); roda(); });
    $("[data-ant]", hero)?.addEventListener("click", () => { ir(i - 1); roda(); });
    pontos.forEach((p) => p.addEventListener("click", () => { ir(+p.dataset.ir); roda(); }));
    hero.addEventListener("mouseenter", () => clearInterval(timer));
    hero.addEventListener("mouseleave", roda);

    // arrastar no celular
    let x0 = null;
    hero.addEventListener("touchstart", (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener("touchend", (e) => {
      if (x0 === null) return;
      const d = e.changedTouches[0].clientX - x0;
      if (Math.abs(d) > 45) { ir(i + (d < 0 ? 1 : -1)); roda(); }
      x0 = null;
    }, { passive: true });

    ir(0); roda();
  }

  /* --------------------------------------------------------- carrosséis --- */
  $$(".carrossel").forEach((caixa) => {
    const pista = $("[data-pista]", caixa);
    if (!pista) return;
    const passo = () => Math.max(240, pista.clientWidth * 0.8);
    $("[data-pista-prox]", caixa)?.addEventListener("click", () => pista.scrollBy({ left: passo(), behavior: "smooth" }));
    $("[data-pista-ant]", caixa)?.addEventListener("click", () => pista.scrollBy({ left: -passo(), behavior: "smooth" }));
  });

  /* ----------------------------------------------------- popup história --- */
  const historia = $("[data-historia]");
  if (historia) {
    const abre = () => (historia.showModal ? historia.showModal() : historia.setAttribute("open", ""));
    const fecha = () => (historia.close ? historia.close() : historia.removeAttribute("open"));
    $$("[data-abrir-historia]").forEach((b) => b.addEventListener("click", abre));
    $$("[data-fechar-historia]").forEach((b) => b.addEventListener("click", fecha));
    historia.addEventListener("click", (e) => { if (e.target === historia) fecha(); });
  }

  /* ------------------------------------------- acessibilidade das gavetas --- */
  // Gaveta fechada fica "inert" (sem foco, fora da leitura de tela); ao fechar, o foco
  // volta para o botão que abriu.
  const fechada = (el, v) => {
    if (!el) return;
    if (v) { el.setAttribute("inert", ""); el.setAttribute("aria-hidden", "true"); }
    else { el.removeAttribute("inert"); el.removeAttribute("aria-hidden"); }
  };

  /* -------------------------------------------------------- menu móvel --- */
  const menu = $("[data-menu-mob]");
  const burger = $("[data-abrir-menu]");
  if (menu && burger) {
    fechada(menu, true);
    const abrir = (v) => {
      menu.classList.toggle("on", v);
      fechada(menu, !v);
      burger.setAttribute("aria-expanded", v ? "true" : "false");
      document.body.style.overflow = v ? "hidden" : "";
      if (v) {
        setTimeout(() => $(".menu-mob__x", menu)?.focus(), 30);
      } else if (menu.contains(document.activeElement) || document.activeElement === document.body) {
        burger.focus();
      }
    };
    burger.addEventListener("click", () => abrir(true));
    $$("[data-fechar-menu]").forEach((b) => b.addEventListener("click", () => abrir(false)));
    menu.addEventListener("click", (e) => { if (e.target.closest("a")) abrir(false); });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape" && menu.classList.contains("on")) abrir(false); });
  }

  /* --------------------------------------------------------- cabeçalho --- */
  const cab = $("[data-cab]");
  if (cab) {
    const rola = () => cab.classList.toggle("rolou", scrollY > 8);
    addEventListener("scroll", rola, { passive: true });
    rola();
  }

  /* ------------------------------------------- filtro por subcategoria --- */
  const pilulas = $$("button.pilula");
  if (pilulas.length) {
    pilulas.forEach((b) => b.addEventListener("click", () => {
      pilulas.forEach((x) => x.classList.remove("on"));
      b.classList.add("on");
      const alvo = b.dataset.sub || "";
      $$(".grade-prod > .prod").forEach((card) => {
        card.style.display = !alvo || card.dataset.sub === alvo ? "" : "none";
      });
    }));
  }

  /* ----------------------------------------------- galeria do produto --- */
  $$("[data-troca]").forEach((b) => b.addEventListener("click", () => {
    const img = $(".pdp__principal img");
    if (!img) return;
    img.removeAttribute("srcset");
    img.removeAttribute("sizes");
    img.src = b.dataset.troca;
    $$("[data-troca]").forEach((x) => x.classList.remove("on"));
    b.classList.add("on");
  }));

  /* ============================== BUSCA ================================= */
  const painel = $("[data-busca]");
  if (!painel) return;

  const campo = $("[data-busca-campo]", painel);
  const corpo = $("[data-busca-corpo]", painel);
  const esc = (s = "") => String(s).replace(/[&<>"]/g, (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[m]));

  const SUGESTOES = (W.sugestoes && W.sugestoes.length)
    ? W.sugestoes
    : ["Incenso", "São Jerônimo", "Oxum", "Vela", "Sineta", "Tarô", "Palo Santo", "Difusor"];

  const dica = () => `<div class="wrap"><div class="busca__dica"><p>Sugestões</p><div class="busca__tags">
      ${SUGESTOES.map((s) => `<button type="button" class="busca__tag" data-sug="${esc(s)}">${esc(s)}</button>`).join("")}
    </div></div></div>`;

  const carregando = () => `<div class="wrap"><div class="busca__carregando"><span></span><span></span><span></span></div></div>`;

  let sel = -1, pedido = 0, cache = new Map();

  function linha(it) {
    return `<a class="busca__item" href="${esc(it.url)}">
      <div class="busca__thumb">${it.img ? `<img src="${esc(it.img)}" alt="" loading="lazy">` : ""}</div>
      <div class="busca__txt">
        <div class="busca__nome">${it.nome_html || esc(it.nome)}</div>
        ${it.sku ? `<div class="busca__ref">Ref. ${esc(it.sku)}</div>` : ""}
      </div>
      <div class="busca__preco">${it.preco || ""}</div>
    </a>`;
  }

  function mostrar(q, dados) {
    sel = -1;
    if (!dados.itens.length) {
      corpo.innerHTML = `<div class="wrap"><div class="busca__nada">
        <b>Nada encontrado para “${esc(q)}”.</b>
        <span>Tente outra palavra${W.whatsapp ? `, ou <a href="https://wa.me/${esc(W.whatsapp)}?text=${encodeURIComponent('Olá! Procurei por "' + q + '" no site e não encontrei. Vocês têm?')}" target="_blank" rel="noopener">pergunte no WhatsApp</a>` : ""}.</span>
      </div></div>`;
      return;
    }
    const mais = dados.total > dados.itens.length
      ? `<a class="busca__todos" href="${esc(W.busca)}${encodeURIComponent(q)}&post_type=product">Ver os ${dados.total} resultados</a>`
      : "";
    corpo.innerHTML = `<div class="wrap"><div class="busca__lista">${dados.itens.map(linha).join("")}</div></div>${mais}`;
  }

  async function procurar() {
    const q = campo.value.trim();
    if (q.length < 2) { corpo.innerHTML = dica(); return; }

    if (cache.has(q)) { mostrar(q, cache.get(q)); return; }

    const meu = ++pedido;
    corpo.innerHTML = carregando();
    try {
      const r = await fetch(`${W.ajax}?action=sg_busca&q=${encodeURIComponent(q.slice(0, 60))}`, {
        credentials: "same-origin",
      });
      const dados = await r.json();
      if (meu !== pedido) return;            // chegou atrasado, descarta
      if (!dados || !dados.success) throw new Error("resposta inválida");
      cache.set(q, dados.data);
      if (cache.size > 40) cache.delete(cache.keys().next().value);
      mostrar(q, dados.data);
    } catch (e) {
      if (meu !== pedido) return;
      corpo.innerHTML = `<div class="wrap"><div class="busca__nada">
        <b>Não consegui buscar agora.</b><span>Tente de novo em instantes, ou aperte Enter para ver a página de resultados.</span></div></div>`;
    }
  }

  let t = null;
  campo.addEventListener("input", () => { clearTimeout(t); t = setTimeout(procurar, 160); });

  let origem = null; // quem abriu a busca, para devolver o foco
  fechada(painel, true);

  function abrir(v) {
    painel.classList.toggle("on", v);
    fechada(painel, !v);
    document.body.style.overflow = v ? "hidden" : "";
    if (v) { corpo.innerHTML = dica(); setTimeout(() => campo.focus(), 60); }
    else {
      campo.value = "";
      if (origem && document.contains(origem)) origem.focus();
      origem = null;
    }
  }

  document.addEventListener("click", (e) => {
    const gatilho = e.target.closest("[data-abrir-busca]");
    if (gatilho) { e.preventDefault(); origem = gatilho; return abrir(true); }
    if (e.target.closest("[data-fechar-busca]") || e.target.closest(".busca__fundo")) return abrir(false);
    const sug = e.target.closest("[data-sug]");
    if (sug) { campo.value = sug.dataset.sug; campo.focus(); procurar(); }
  });

  campo.addEventListener("keydown", (e) => {
    const itens = $$(".busca__item", corpo);
    if (e.key === "ArrowDown" || e.key === "ArrowUp") {
      if (!itens.length) return;
      e.preventDefault();
      sel += e.key === "ArrowDown" ? 1 : -1;
      if (sel >= itens.length) sel = 0;
      if (sel < 0) sel = itens.length - 1;
      itens.forEach((x, k) => x.classList.toggle("sel", k === sel));
      itens[sel].scrollIntoView({ block: "nearest" });
    } else if (e.key === "Enter" && sel >= 0 && itens[sel]) {
      e.preventDefault();
      location.href = itens[sel].href;
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && painel.classList.contains("on")) abrir(false);
    if (e.key === "/" && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)
        && !document.activeElement.isContentEditable && !painel.classList.contains("on")) {
      e.preventDefault(); origem = document.activeElement; abrir(true);
    }
  });
})();
