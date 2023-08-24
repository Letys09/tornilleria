<hr>

<footer>
  <div class="logo-footer">
</footer>

<div>
<?php
?>
</div>
<script>

let sinDiacriticos = (function(){
let de = 'ÁÃÀÄÂÉËÈÊÍÏÌÎÓÖÒÔÚÜÙÛÑÇáãàäâéëèêíïìîóöòôúüùûñ´ç°ª–”“†ÿŸÇ',
     a = 'AAAAAEEEEIIIIOOOOUUUUNCaaaaaeeeeiiiioooouuuun c        C',
    re = new RegExp('['+de+']' , 'ug');

return texto =>
  texto.replace(
      re, 
      match => a.charAt(de.indexOf(match))
  );
})();

let setError = (function(elem,msg){
    console.log(elem,msg)
    $('#'+elem).tooltip('destroy');
    msg = '<span class="ddsicon-info-sign"></span> '+msg;
    $('#'+elem).tooltip({title: msg, trigger: 'manual', template: '<div class="tooltip tooltip-error"><div class="tooltip-arrow"></div class="tooltip-inner"></div></div>', html: true});
    $('#'+elem).tooltip('show');
    $('#'+elem).focus();
})
</script>
</body>
</html>