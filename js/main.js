function openModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.add('show');
}
function closeModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.remove('show');
}

// zamkniecie klikiem w tlo
document.addEventListener('click', function (ev) {
  if (ev.target.classList.contains('modal-bg')) {
    ev.target.classList.remove('show');
  }
});

// na telefonie szerokie tabele maja sie przewijac w poziomie
document.addEventListener('DOMContentLoaded', function () {
  var tables = document.querySelectorAll('table');
  for (var i = 0; i < tables.length; i++) {
    var t = tables[i];
    if (t.parentElement && t.parentElement.classList.contains('table-scroll')) continue;
    var wrap = document.createElement('div');
    wrap.className = 'table-scroll';
    t.parentNode.insertBefore(wrap, t);
    wrap.appendChild(t);
  }
});
