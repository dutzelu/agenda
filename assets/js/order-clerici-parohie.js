$(function(){
  /* drag-and-drop */
  $('#clerici-list').sortable({handle:'.handle', placeholder:'ui-state-highlight'});

  /* buton salvare */
  $('#save-order').on('click',function(){
    const ord=[];
    $('#clerici-list tr').each(function(i){ord.push({id:$(this).data('cp-id'),sort:i+1});});
    $.post('update-order.php',{order:ord},function(r){
      if(r.success){
        $('#order-feedback').html('<div class="alert alert-success">Ordinea a fost salvată!</div>');
        /* mesaj auto‑hide */
        setTimeout(()=>{$('#order-feedback .alert').fadeOut(500,function(){$(this).remove();});},2500);
      } else {
        $('#order-feedback').html('<div class="alert alert-danger">Eroare: '+r.error+'</div>');
        setTimeout(()=>{$('#order-feedback .alert').fadeOut(500,function(){$(this).remove();});},4000);
      }
    },'json');
  });

  /* auto-close pentru alerte cu id="dispari" */
  setTimeout(()=>{const el=document.getElementById('dispari');if(el)$(el).fadeOut(500);},2500);
});