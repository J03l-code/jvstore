/* JVSTORE - Main JS v2.0 */

// ====== MOBILE NAV ======
function jvToggleNav(){
  const nav = document.getElementById('jvMobileNav');
  if(nav) nav.classList.toggle('open');
}

// ====== HERO SLIDER ======
let jvSlide = 0;
function jvGoSlide(n){
  const slides = document.querySelectorAll('.jv-hero-slide');
  const dots   = document.querySelectorAll('.jv-dot');
  if(!slides.length) return;
  slides[jvSlide]?.classList.remove('active');
  dots[jvSlide]?.classList.remove('active');
  jvSlide = n % slides.length;
  slides[jvSlide]?.classList.add('active');
  dots[jvSlide]?.classList.add('active');
}
setInterval(()=> jvGoSlide(jvSlide+1), 5000);

// ====== STICKY HEADER ======
window.addEventListener('scroll', ()=>{
  const h = document.querySelector('.jv-header');
  if(h) h.classList.toggle('scrolled', window.scrollY > 60);
});

// ====== ADD TO CART ======
function addToCart(id, qty=1){
  fetch('api/cart.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'add', id, qty})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      const badge = document.querySelector('.jv-cart-count');
      if(badge) badge.textContent = d.count;
      else {
        const btn = document.querySelector('.jv-cart-btn');
        if(btn){
          const b = document.createElement('span');
          b.className='jv-cart-count'; b.textContent=d.count;
          btn.appendChild(b);
        }
      }
      showToast('¡Producto agregado al carrito!','success');
    } else {
      showToast(d.message||'Error al agregar','danger');
    }
  }).catch(()=> showToast('Error de conexión','danger'));
}

// ====== TOAST NOTIFICATION ======
function showToast(msg, type='success'){
  const t = document.createElement('div');
  t.className = `jv-flash ${type}`;
  t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
  document.body.appendChild(t);
  setTimeout(()=> t.remove(), 3500);
}

// ====== CLOSE MOBILE NAV ON OUTSIDE CLICK ======
document.addEventListener('click', e=>{
  const nav = document.getElementById('jvMobileNav');
  if(nav?.classList.contains('open') && !nav.contains(e.target) && !e.target.closest('.jv-mobile-toggle')){
    nav.classList.remove('open');
  }
});
