function WebDate() {
  var now = new Date();
  var year = now.getYear();
  if (year < 1000) year += 1900;
  var nameDay = ['Domenica','Lunedi','Martedi','Mercoledi','Giovedi','Venerdi','Sabato'];
  var nameMth = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
  document.write('<p>' + nameDay[now.getDay()] + ' ' + now.getDate() + ' ' + nameMth[now.getMonth()] + ' ' + year + '<\/p>');
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
  var html = '<table width="100%" border="1" align="left" cellpadding="1" cellspacing="0" bordercolor="#C0CFE0">';
  html += '<tbody>';
  html += '<tr><td width="247">&nbsp;<br />&nbsp;<br /></td></tr>';
  for (var i = 0; i < links.length; i++) {
    html += '<tr>';
    html += '<td width="247" height="47" style="text-align: left">';
    html += '<table width="222" border="0" cellspacing="0">';
    html += '<tr><td width="23">&nbsp;</td>';
    html += '<td width="192"><a class="Stile251" href="' + links[i].href + '">' + links[i].label + '</a></td>';
    html += '</tr></table>';
    html += '</td>';
    html += '</tr>';
  }
  html += '</tbody></table>';
  var container = document.getElementById('nav-container');
  if (container) {
    container.innerHTML = html;
  }
}
