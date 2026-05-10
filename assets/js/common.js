function WebDate() {
  var now = new Date();
  var year = now.getYear();
  if (year < 1000) year += 1900;
  var nameDay = ['Domenica','Lunedi','Martedi','Mercoledi','Giovedi','Venerdi','Sabato'];
  var nameMth = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
  document.write('<p>' + nameDay[now.getDay()] + ' ' + now.getDate() + ' ' + nameMth[now.getMonth()] + ' ' + year + '<\/p>');
}
