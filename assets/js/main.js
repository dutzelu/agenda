document
  .getElementById('searchParohie')
  .addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const select = document.getElementById('parohieSelect');
    Array.from(select.options).forEach(opt => {
      // arată/ascunde în funcție de textul opțiunii
      opt.style.display = opt.text.toLowerCase().includes(filter)
        ? ''
        : 'none';
    });
  });
