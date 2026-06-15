// Noms italiens des jours et des mois, partagés par tout le site.
var GIORNI = ['Domenica','Lunedi','Martedi','Mercoledi','Giovedi','Venerdi','Sabato'];
var MESI = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

// Formate une date ISO "YYYY-MM-DD" en "D Mese YYYY" (ex. "5 Giugno 2026").
// Renvoie '' si vide, ou la valeur telle quelle si le format est inattendu.
function formatDateIso(iso) {
  if (!iso) return '';
  var p = iso.split('-');
  if (p.length < 3) return iso;
  return parseInt(p[2], 10) + ' ' + MESI[parseInt(p[1], 10) - 1] + ' ' + p[0];
}

function WebDate() {
  let now = new Date();
  let year = now.getYear();
  if (year < 1000) {
    year += 1900;
  }
  document.write('<p>' + GIORNI[now.getDay()] + ' ' + now.getDate() + ' ' + MESI[now.getMonth()] + ' ' + year + '<\/p>');
}

function buildHeader() {
  var now = new Date();
  var year = now.getFullYear();
  var dateStr = GIORNI[now.getDay()] + ' ' + now.getDate() + ' ' + MESI[now.getMonth()] + ' ' + year;

  var pageMap = {
    'index.html':        'Home',
    'novita.html':       'Novità',
    'storia.html':       'Storia',
    'iniziative.html':   'Iniziative',
    'immagini.html':     'Immagini',
    'filmati.html':      'Filmati',
    'contatti.html':     'Contatti',
    'associazione.html': 'Organizzazione',
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

// Construit les liens e-mail à partir d'attributs data-* (anti-spam : l'adresse
// n'apparaît jamais en clair dans le HTML). Sur chaque élément portant
// data-email-user et data-email-domain, on injecte un <a href="mailto:...">.
// Attributs optionnels :
//   data-email-class : classe CSS appliquée au lien (ex. "Stile63")
//   data-email-text  : texte affiché à la place de l'adresse
function buildEmailLinks() {
  var nodes = document.querySelectorAll('[data-email-user][data-email-domain]');
  for (var i = 0; i < nodes.length; i++) {
    var el = nodes[i];
    var addr = el.getAttribute('data-email-user') + '@' + el.getAttribute('data-email-domain');
    var a = document.createElement('a');
    a.href = 'mailto:' + addr;
    a.textContent = el.getAttribute('data-email-text') || addr;
    var cls = el.getAttribute('data-email-class');
    if (cls) {
      a.className = cls;
    }
    el.appendChild(a);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', buildEmailLinks);
} else {
  buildEmailLinks();
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
