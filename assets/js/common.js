function WebDate() {
  let now = new Date();
  let year = now.getYear();
  if (year < 1000) {
    year += 1900;
  }
  const nameDay = ["Domenica", "Lunedi", "Martedi", "Mercoledi", "Giovedi", "Venerdi", "Sabato"];
  const nameMth = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
  document.write('<p>' + nameDay[now.getDay()] + ' ' + now.getDate() + ' ' + nameMth[now.getMonth()] + ' ' + year + '<\/p>');
}

function buildHeader() {
  var now = new Date();
  var year = now.getFullYear();
  var nameDay = ['Domenica','Lunedi','Martedi','Mercoledi','Giovedi','Venerdi','Sabato'];
  var nameMth = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
  var dateStr = nameDay[now.getDay()] + ' ' + now.getDate() + ' ' + nameMth[now.getMonth()] + ' ' + year;

  var pageMap = {
    'index.html':        'Home',
    'novita.html':       'Novità',
    'storia.html':       'Storia',
    'iniziative.html':   'Iniziative',
    'immagini.html':     'Immagini',
    'filmati.html':      'Filmati',
    'contatti.html':     'Contatti',
    'associazione.html': 'Associazione'
  };
  var path = window.location.pathname.split('/').pop() || 'index.html';
  var pageLabel = pageMap[path];
  var breadcrumb = pageLabel && pageLabel !== 'Home'
    ? '<a href="index.html">Home</a> &rsaquo; ' + pageLabel
    : 'Home';

  var html =
    '<div id="site-header">' +
      '<div class="header-top">' +
        '<div class="header-logo">' +
          '<img src="images/shared/logoverdepiccolo.png" alt="Oltr\'Alpe" width="250" height="133" />' +
        '</div>' +
        '<div class="header-spacer"></div>' +
        '<div class="header-identity">' +
          '<span class="Stile1">Oltr\'Alpe</span>' +
          '<span class="Stile38">Associazione di volontariato</span>' +
          '<span class="Stile22">via Matteotti,1 - 40063 Monghidoro</span>' +
        '</div>' +
      '</div>' +
      '<div class="header-datebar">' +
        '<span class="header-breadcrumb">' + breadcrumb + '</span>' +
        '<span class="header-date">' + dateStr + '</span>' +
      '</div>' +
    '</div>';

  var container = document.getElementById('header-container');
  if (container) {
    container.innerHTML = html;
  }
}

function buildNav() {
  var links = [
    { href: 'index.html',        label: 'Home' },
    { href: 'novita.html',       label: 'Novità' },
    { href: 'storia.html',       label: 'Storia' },
    { href: 'iniziative.html',   label: 'Iniziative' },
    { href: 'immagini.html',     label: 'Immagini' },
    { href: 'filmati.html',      label: 'Filmati' },
    { href: 'contatti.html',     label: 'Contatti' },
    { href: 'associazione.html', label: 'Associazione' }
  ];
  var html = '<nav id="navigation"><ul>';
  for (var i = 0; i < links.length; i++) {
    html += '<li><a href="' + links[i].href + '">' + links[i].label + '</a></li>';
  }
  html += '</ul></nav>';
  var container = document.getElementById('nav-container');
  if (container) {
    container.innerHTML = html;
  }
}
