document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.clickable-row')
          .forEach(row =>
            row.addEventListener('click', () =>
              window.location.href = row.dataset.href));
});


setTimeout(function() {
    $('#dispari').fadeOut('fast');
}, 2000); // <-- time in milliseconds
