</div><!-- .main -->
</body>
<script>
const sb=document.getElementById('sidebar'),ov=document.getElementById('overlay'),hb=document.getElementById('hamburger');
if(hb){hb.addEventListener('click',()=>{sb.classList.add('open');ov.classList.add('show');});
ov.addEventListener('click',()=>{sb.classList.remove('open');ov.classList.remove('show');});}
</script>
</html>
