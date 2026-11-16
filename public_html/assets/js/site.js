/* ASAS · comportamento do site
   1. Aviso de erro do formulário de candidatura (participar.html)
   2. Filtro por região dos quadros de encontros (index.html) */
(function () {
  'use strict';

  /* ---------- 1. AVISO DE ERRO DO FORMULÁRIO ----------
     O enviar.php devolve a visitante ao formulário com ?erro=campos ou ?erro=envio no endereço. */
  var parametros = new URLSearchParams(window.location.search);
  var aviso = document.querySelector('[data-aviso-erro]');
  var erro = parametros.get('erro');
  if (aviso && erro) {
    aviso.textContent = erro === 'campos'
      ? 'Faltou preencher algum campo obrigatório. Confira os dados e envie de novo.'
      : 'Não foi possível enviar agora. Tente de novo em alguns minutos.';
    aviso.hidden = false;
    aviso.focus();
  }

  /* ---------- 2. FILTRO DOS ENCONTROS ----------
     Os quadros ficam escritos no index.html. Cada um tem data-regiao.
     Ao clicar em um botão de filtro, ficam visíveis só os quadros daquela região. */
  var agenda = document.getElementById('agenda');
  if (!agenda) { return; }

  var quadros = agenda.querySelectorAll('.encontro');
  var vazia = document.getElementById('agenda-vazia');
  var filtros = document.querySelectorAll('#filtros .filtro');

  function filtrar(regiao) {
    var visiveis = 0;
    for (var i = 0; i < quadros.length; i++) {
      var mostrar = regiao === 'Todas' || quadros[i].getAttribute('data-regiao') === regiao;
      quadros[i].hidden = !mostrar;
      if (mostrar) { visiveis++; }
    }
    // Sem quadro para mostrar, entra o aviso "Ainda não há encontro marcado nesta região."
    agenda.hidden = visiveis === 0;
    vazia.hidden = visiveis !== 0;
  }

  for (var f = 0; f < filtros.length; f++) {
    filtros[f].addEventListener('click', function () {
      for (var j = 0; j < filtros.length; j++) {
        filtros[j].setAttribute('aria-pressed', filtros[j] === this ? 'true' : 'false');
      }
      filtrar(this.getAttribute('data-regiao'));
    });
  }
})();
