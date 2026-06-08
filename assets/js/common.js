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
    'associazione.html': 'Associazione',
    'iscrizione.html':   'Iscrizione',
    'galerie.html':      'Galleria',
    'informazioni.html': 'Informazioni utili'
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
    '<a href="index.html"><img src="images/shared/logoverdepiccolo.png" alt="Oltr\'Alpe" width="250" height="133" /></a>' +
    "</div>" +
    '<div class="header-spacer"></div>' +
    '<div class="header-identity">' +
    '<span class="Stile1">Oltr\'Alpe</span>' +
    '<span class="Stile38">Organizzazione di volontariato</span>' +
    '<span class="Stile22">via Matteotti,1 - 40063 Monghidoro</span>' +
    "</div>" +
    "</div>" +
    '<div class="header-datebar">' +
    '<span class="header-breadcrumb">' +
    breadcrumb +
    "</span>" +
    '<span class="header-date">' +
    dateStr +
    "</span>" +
    "</div>" +
    "</div>";

  var container = document.getElementById('header-container');
  if (container) {
    container.innerHTML = html;
  }
}

function buildNav() {
  const links = [
    { href: "index.html", label: "Home" },
    { href: "novita.html", label: "Novità" },
    { href: "storia.html", label: "Storia" },
    { href: "iniziative.html", label: "Iniziative" },
    { href: "galerie.html", label: "Galleria foto" },
    { href: "filmati.html", label: "Filmati" },
    { href: "contatti.html", label: "Contatti" },
    { href: "iscrizione.html", label: "Iscrizione" },
    { href: "associazione.html", label: "Organizzazione" },
    { href: "informazioni.html", label: "Informazioni utili" },
  ];
  let html = '<nav id="navigation"><ul>';
  for ( let i = 0; i < links.length; i++) {
    html += '<li><a href="' + links[i].href + '">' + links[i].label + '</a></li>';
  }
  html += '</ul></nav>';
  var container = document.getElementById('nav-container');
  if (container) {
    container.innerHTML = html;
  }
}

// Enregistre la visite côté serveur (stats/track.php) et affiche un compteur
// discret « Visite: N » dans la barre verte de pied de page. Échec silencieux :
// le compteur ne doit jamais perturber l'affichage du site.
function trackVisit() {
  var path = window.location.pathname.split('/').pop() || 'index.html';
  fetch('stats/track.php?p=' + encodeURIComponent(path))
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data || data.total === null || data.total === undefined) {
        return;
      }
      var bars = document.querySelectorAll('td[bgcolor="#99CC66"]');
      var footer = bars.length ? bars[bars.length - 1] : null;
      if (!footer) {
        return;
      }
      var span = document.createElement('span');
      span.className = 'visite-counter';
      span.textContent = 'Visite: ' + data.total.toLocaleString('it-IT');
      footer.innerHTML = '';
      footer.appendChild(span);
    })
    .catch(function () { /* hors-ligne ou PHP indisponible : on ignore */ });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', trackVisit);
} else {
  trackVisit();
}
