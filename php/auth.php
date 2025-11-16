<?php
require __DIR__.'/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_HOME);
    exit;
}

$auth_error = $_SESSION['auth_error'] ?? '';
$auth_error_tab = $_SESSION['auth_error_tab'] ?? 'signup';
unset($_SESSION['auth_error'], $_SESSION['auth_error_tab']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Life Tracker — Authentification</title>
  <link rel="stylesheet" href="../css/auth.css?v=<?php echo @filemtime(__DIR__.'/../css/index.css') ?: time(); ?>" />
</head>
<body>
<section class="auth-screen">
  <div class="auth-card">
    <div class="auth-tabs">
      <button id="tab-signup" class="auth-tab <?php echo ($auth_error_tab==='signup'?'active':''); ?>" type="button">Créer un compte</button>
      <button id="tab-login" class="auth-tab <?php echo ($auth_error_tab==='login'?'active':''); ?>" type="button">Se connecter</button>
    </div>

    <!-- ========= CRÉER UN COMPTE ========= -->
    <form id="signupForm" class="auth-form <?php echo ($auth_error_tab==='login'?'hidden':''); ?>" method="post" action="register.php" autocomplete="on" novalidate>
      <label>
        <div class="label-row"><span>Nom d’utilisateur <b>*</b></span></div>
        <input type="text" name="username" id="username" maxlength="32" required />
        <small id="userHint" class="auth-error"></small>
      </label>

      <label>
        <span>Email <b>*</b></span>
        <input type="email" name="email" id="email" required />
        <small id="emailHint" class="auth-error"></small>
      </label>

      <label>
        <span>Numéro de téléphone</span>
        <div class="phone-field">
          <select name="dial_code" id="dial_code" class="phone-code" aria-label="Indicatif">
            <option value="+33">+33 (FR)</option><option value="+32">+32 (BE)</option>
            <option value="+41">+41 (CH)</option><option value="+352">+352 (LU)</option>
            <option value="+49">+49 (DE)</option><option value="+44">+44 (UK)</option>
            <option value="+34">+34 (ES)</option><option value="+39">+39 (IT)</option>
            <option value="+212">+212 (MA)</option><option value="+216">+216 (TN)</option>
            <option value="+213">+213 (DZ)</option><option value="+1">+1 (US/CA)</option>
          </select>
          <input type="tel" name="phone_local" id="phone" placeholder="06 12 34 56 78" />
        </div>
        <small id="phoneHint" class="auth-error"></small>
      </label>

      <label>
        <span>Date de naissance <b>*</b></span>
        <div class="date-field" id="dobField">
          <input type="text" class="date-input" id="birthdate_display" placeholder="JJ/MM/AAAA" />
          <svg class="calendar-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="1.5"></rect>
            <line x1="16" y1="2" x2="16" y2="6" stroke-width="1.5"></line>
            <line x1="8"  y1="2" x2="8"  y2="6" stroke-width="1.5"></line>
            <line x1="3"  y1="10" x2="21" y2="10" stroke-width="1.5"></line>
          </svg>
          <input type="date" name="birthdate" id="birthdate" class="hidden" required />
          <div class="datepicker" id="datepicker"></div>
        </div>
      </label>
      <small id="birthHint" class="auth-error" aria-live="polite"></small>

      <label>
        <span>Genre</span>
        <select name="gender" id="gender">
          <option value="">— Sélectionner —</option>
          <option value="Homme">Homme</option>
          <option value="Femme">Femme</option>
          <option value="Autre">Autre</option>
        </select>
      </label>

      <label class="password-label">
        <div class="label-row">
          <span>Mot de passe</span>
          <div class="field-actions">
            <button type="button" id="pwdToggle" class="icon-mini" title="Afficher / masquer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button type="button" id="pwdGenBtn" class="icon-mini" title="Générer un mot de passe">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3v3m0 12v3m9-9h-3M6 12H3m12.728-6.728l-2.121 2.121M8.393 15.607l-2.121 2.121M18.364 18.364l-2.121-2.121M8.393 8.393L6.272 6.272" stroke-width="1.6" stroke-linecap="round"/></svg>
            </button>
          </div>
        </div>
        <input class="trim-ellipsis" type="password" name="password" id="password" minlength="8" maxlength="32" required />
        <small id="pwdHint" class="auth-error"></small>

        <!-- Popover générateur -->
        <div id="pwdGen" class="popover" aria-hidden="true">
          <div class="options-row">
            <label for="genLength">Longueur</label>
            <input id="genLength" type="number" min="8" max="32" value="16" />
            <label><input type="checkbox" id="genLower" checked /> abc</label>
            <label><input type="checkbox" id="genUpper" checked /> ABC</label>
            <label><input type="checkbox" id="genDigits" checked /> 0-9</label>
            <label><input type="checkbox" id="genSymbols" checked /> Symboles</label>
          </div>
          <div class="gen-bar">
            <button type="button" class="btn-ghost" id="genMake">Générer</button>
            <input id="genPreview" class="trim-ellipsis" type="text" readonly
                   style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;">
            <button type="button" id="genCopy" class="icon-mini" title="Copier" aria-label="Copier">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="9" y="9" width="11" height="11" rx="2" ry="2"></rect>
                <path d="M6 15V6a2 2 0 0 1 2-2h9"></path>
              </svg>
            </button>
            <button type="button" class="btn-primary" id="genUse">Utiliser</button>
          </div>
        </div>
      </label>

      <label>
        <span>Confirmer le mot de passe</span>
        <input class="trim-ellipsis" type="password" name="password_confirm" id="password_confirm" minlength="8" maxlength="32" required />
      </label>

      <p class="soft-note">* obligatoire</p>
      <p class="legal-note">
        En vous inscrivant, vous acceptez nos <a href="#" target="_blank" rel="noopener">Conditions générales</a>.
        Découvrez comment nous collectons, utilisons et partageons vos données en lisant notre
        <a href="#" target="_blank" rel="noopener">Politique de confidentialité</a> et comment nous utilisons
        les cookies et autres technologies similaires en consultant notre
        <a href="#" target="_blank" rel="noopener">Politique d’utilisation des cookies</a>.
      </p>

      <button class="btn-primary" type="submit">Créer mon compte</button>

      <?php if ($auth_error && $auth_error_tab==='signup'): ?>
        <p class="auth-error"><?= htmlspecialchars($auth_error) ?></p>
      <?php else: ?>
        <p class="auth-error"></p>
      <?php endif; ?>
    </form>

    <!-- ========= SE CONNECTER ========= -->
    <form id="loginForm" class="auth-form <?php echo ($auth_error_tab==='login'?'':'hidden'); ?>" method="post" action="login.php" novalidate>
      <label><span>Identifiant (pseudo, email ou téléphone)</span><input type="text" name="identifier" required /></label>
      <label><span>Mot de passe</span><input type="password" name="password" required /></label>

      <button class="btn-primary" type="submit">Connexion</button>

      <div class="auth-inline-row">
        <span class="auth-error">
          <?php if ($auth_error && $auth_error_tab==='login') echo htmlspecialchars($auth_error); ?>
        </span>
        <a class="soft-note" href="reset_request.php">Mot de passe oublié ?</a>
      </div>
    </form>
  </div>
</section>

<script>
'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const tabSignup = document.getElementById('tab-signup');
  const tabLogin  = document.getElementById('tab-login');
  const signupForm= document.getElementById('signupForm');
  const loginForm = document.getElementById('loginForm');
  function showTab(name){
    if(name==='signup'){
      tabSignup.classList.add('active'); tabLogin.classList.remove('active');
      signupForm.classList.remove('hidden'); loginForm.classList.add('hidden');
    }else{
      tabLogin.classList.add('active'); tabSignup.classList.remove('active');
      loginForm.classList.remove('hidden'); signupForm.classList.add('hidden');
    }
  }
  if (tabSignup && tabLogin) {
    tabSignup.addEventListener('click',()=>showTab('signup'));
    tabLogin.addEventListener('click',()=>showTab('login'));
  }

  const $ = (sel) => document.querySelector(sel);
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

  const username = $('#username');
  const userHint = $('#userHint');
  const email = $('#email');
  const emailHint = $('#emailHint');
  const dial = $('#dial_code');
  const phone = $('#phone');
  const phoneHint = $('#phoneHint');
  const birth = $('#birthdate');
  const birthDisp = $('#birthdate_display');
  const birthHint = $('#birthHint');

  const pwd = $('#password');
  const pwdHint = $('#pwdHint');
  const pwdConfirm = $('#password_confirm');
  const submitBtn = $('#signupForm .btn-primary');
  const genderSel = $('#gender');

  if (!username || !email || !dial || !phone || !birth || !pwd || !pwdConfirm || !submitBtn) return;

  let birthOK=false, emailOK=true, phoneOK=true, userOK=false, pwdOK=false;
  const refreshSubmit=()=>{ submitBtn.disabled=!(birthOK&&emailOK&&phoneOK&&userOK&&pwdOK); };

  const parseYMD=(v)=>{ const [y,m,d]=v.split('-').map(Number); return new Date(y,(m||1)-1,(d||1)); };
  const fmtDMY=(date)=>`${String(date.getDate()).padStart(2,'0')}/${String(date.getMonth()+1).padStart(2,'0')}/${date.getFullYear()}`;
  const toYMD=(date)=>`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
  const parseDMY=(str)=>{ const m=(str||'').trim().match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/); if(!m)return null; const d=new Date(+m[3],+m[2]-1,+m[1]); if(d.getFullYear()!=+m[3]||d.getMonth()!=+m[2]-1||d.getDate()!=+m[1])return null; return d; };

  function validateBirth(){
    birthHint.textContent='';
    const v=birth.value;
    if(!v){ birthOK=false; return refreshSubmit(); }
    const d=parseYMD(v), today=new Date();
    if(isNaN(d.getTime())){ birthHint.textContent='Date invalide.'; birthOK=false; return refreshSubmit(); }
    if(d>today){ birthHint.textContent='La date ne peut pas être dans le futur.'; birthOK=false; return refreshSubmit(); }
    let age=today.getFullYear()-d.getFullYear(); const m=today.getMonth()-d.getMonth(); if(m<0||(m===0&&today.getDate()<d.getDate())) age--;
    if(age<13){ birthHint.textContent='Vous devez avoir au moins 13 ans pour vous inscrire.'; birthOK=false; }
    else { birthOK=true; if(age>150){ birthHint.textContent=`Cette date indique ${age} ans. Êtes-vous sûr·e ?`; } }
    refreshSubmit();
  }

  const isValidEmail=(v)=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);

  async function runChecks(){
    try{
      const u=(username.value||'').trim();
      const e=(email.value||'').trim();
      const p=(phone.value||'').trim();

      const params=new URLSearchParams();
      if(u) params.set('u', u);
      if(e) params.set('email', e);
      const digits=(dial.value+p).replace(/\D/g,'');
      if(p && digits.length>=8 && digits.length<=15){ params.set('dial', dial.value); params.set('phone', p); }

      if(!params.toString()) return;
      const res=await fetch('check.php?'+params.toString(), {cache:'no-store', credentials:'same-origin'});
      if(!res.ok) return;
      const data=await res.json();

      if('username_available' in data){
        if(data.username_available){ userHint.className='auth-success'; userHint.textContent='Nom d’utilisateur disponible.'; userOK=true; }
        else { userHint.className='auth-error'; userHint.textContent='Nom d’utilisateur indisponible.'; userOK=false; }
      }
      if(data.email_checked && data.email_used){ emailHint.textContent='Email déjà utilisé.'; emailOK=false; }
      if(data.phone_checked){
        if(data.phone_used){ phoneHint.className='auth-error'; phoneHint.textContent='Numéro déjà utilisé.'; phoneOK=false; }
        else { phoneHint.className='auth-success'; phoneHint.textContent='Numéro valide.'; phoneOK=true; }
      }
      refreshSubmit();
    }catch(e){}
  }
  const runChecksDebounced=debounce(runChecks, 400);

  function validateEmailPhone(){
    emailHint.textContent=''; emailOK=true;
    phoneHint.textContent=''; phoneHint.className='auth-error';

    const e=(email.value||'').trim();
    const p=(phone.value||'').trim();

    if(p!=='' && e===''){ emailHint.textContent='Email requis.'; emailOK=false; }
    else if(e!=='' && !isValidEmail(e)){ emailHint.textContent='Email invalide.'; emailOK=false; }

    if(p!==''){
      if(/[A-Za-z]/.test(p)){ phoneHint.textContent='Numéro invalide.'; phoneOK=false; refreshSubmit(); return; }
      const digits=(dial.value+p).replace(/\D/g,'');
      if(digits.length<8 || digits.length>15){ phoneHint.textContent='Numéro invalide.'; phoneOK=false; refreshSubmit(); return; }
      phoneOK=true;
    } else { phoneOK=true; }

    runChecksDebounced();
    refreshSubmit();
  }

  const checkUser = debounce(()=>{
    userHint.className='auth-error'; userHint.textContent=''; userOK=false;
    const v=(username.value||'').trim();
    if(v.length<3){ userHint.textContent='Minimum 3 caractères.'; refreshSubmit(); return; }
    if(!/^[A-Za-z0-9_.-]{3,32}$/.test(v)){ userHint.textContent='Caractères autorisés: lettres, chiffres, _ . -'; refreshSubmit(); return; }
    runChecksDebounced();
  },300);
  username.addEventListener('input', checkUser);

  function validatePwd(){
    const v=pwd.value;
    const re=/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]{8,32}$/;
    if(re.test(v)){ pwdHint.className='auth-success'; pwdHint.textContent='Mot de passe valide.'; pwdOK=true; }
    else { pwdHint.className='auth-error'; pwdHint.textContent='Entre 8 et 32 caractères, avec au moins 1 majuscule et 1 chiffre.'; pwdOK=false; }
    refreshSubmit();
  }
  pwd.addEventListener('input', validatePwd);

  document.getElementById('pwdToggle').addEventListener('click',()=>{ pwd.type = (pwd.type==='password')?'text':'password'; });

  // Générateur
  const pwdGenBtn=document.getElementById('pwdGenBtn');
  const pwdGen=document.getElementById('pwdGen');
  const genLen=document.getElementById('genLength');
  const genLower=document.getElementById('genLower');
  const genUpper=document.getElementById('genUpper');
  const genDigits=document.getElementById('genDigits');
  const genSymbols=document.getElementById('genSymbols');
  const genMake=document.getElementById('genMake');
  const genUse=document.getElementById('genUse');
  const genCopy=document.getElementById('genCopy');
  const genPreview=document.getElementById('genPreview');

  function makePassword(){
    const len=Math.max(8, Math.min(32, parseInt(genLen.value||16,10)));
    let pools=[];
    if(genLower.checked)pools.push('abcdefghijklmnopqrstuvwxyz');
    if(genUpper.checked)pools.push('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    if(genDigits.checked)pools.push('0123456789');
    if(genSymbols.checked)pools.push('!@#$%^&*()_+[]{};:,.<>/?\\|-=\'"');
    if(pools.length===0)pools=['abcdefghijklmnopqrstuvwxyz'];
    let out=''; pools.forEach(p=> out+=p[Math.floor(Math.random()*p.length)]);
    const all=pools.join(''); while(out.length<len){ out+=all[Math.floor(Math.random()*all.length)]; }
    return out.split('').sort(()=>Math.random()-0.5).join('');
  }
  function openGen(){ pwdGen.classList.add('open'); document.addEventListener('click', genDocClose, true); document.addEventListener('keydown', genKeyClose, true); }
  function closeGen(){ pwdGen.classList.remove('open'); document.removeEventListener('click', genDocClose, true); document.removeEventListener('keydown', genKeyClose, true); }
  function genDocClose(e){ if(!pwdGen.contains(e.target) && e.target!==pwdGenBtn) closeGen(); }
  function genKeyClose(e){ if(e.key==='Escape') closeGen(); }

  pwdGenBtn.addEventListener('click',()=>{ if(pwdGen.classList.contains('open')) closeGen(); else openGen(); });
  genMake.addEventListener('click',()=>{ genPreview.value=makePassword(); });

  // 🔧 COPIER : génère si vide, puis copie la valeur (mobile/desktop/tablette)
  genCopy.addEventListener('click', async ()=>{
    try{
      if(!genPreview.value) genPreview.value = makePassword();
      const pass = genPreview.value;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(pass);
      } else {
        // fallback
        const ta=document.createElement('textarea');
        ta.value=pass; ta.style.position='fixed'; ta.style.opacity='0'; document.body.appendChild(ta);
        ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
      }
    }catch(e){}
  });

  genUse.addEventListener('click',()=>{
    if(!genPreview.value) genPreview.value=makePassword();
    pwd.value=genPreview.value; document.getElementById('password_confirm').value=pwd.value; validatePwd(); closeGen();
  });

  // Datepicker sombre
  const dpWrap=document.getElementById('datepicker'); const field=document.getElementById('dobField'); let currentMonth;
  if (dpWrap && field) {
    function buildDP(){
      dpWrap.innerHTML='';
      const head=document.createElement('div'); head.className='dp-head';
      const title=document.createElement('div'); title.className='dp-title';
      const nav=document.createElement('div'); nav.className='dp-nav';
      const prev=document.createElement('button'); prev.type='button'; prev.className='dp-btn'; prev.textContent='‹';
      const next=document.createElement('button'); next.type='button'; next.className='dp-btn'; next.textContent='›';
      nav.append(prev,next); head.append(title,nav); dpWrap.append(head);
      const months=['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
      title.textContent=`${months[currentMonth.getMonth()]} ${currentMonth.getFullYear()}`;
      const grid=document.createElement('div'); grid.className='dp-grid';
      const days=['L','M','M','J','V','S','D']; days.forEach(d=>{ const el=document.createElement('div'); el.className='dp-cell muted'; el.textContent=d; grid.appendChild(el); });
      const firstDay=(new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1).getDay()+6)%7;
      const daysInMonth=new Date(currentMonth.getFullYear(), currentMonth.getMonth()+1, 0).getDate();
      const selected=birth.value?parseYMD(birth.value):null; if(selected)selected.setHours(0,0,0,0);
      for(let i=0;i<firstDay;i++){ const el=document.createElement('div'); el.className='dp-cell muted'; el.textContent=''; grid.appendChild(el); }
      for(let d=1; d<=daysInMonth; d++){
        const el=document.createElement('button'); el.type='button'; el.className='dp-cell'; el.textContent=String(d);
        const date=new Date(currentMonth.getFullYear(), currentMonth.getMonth(), d);
        if(selected && date.getTime()===selected.getTime()) el.classList.add('selected');
        el.addEventListener('click',()=>{ birth.value=toYMD(date); birthDisp.value=fmtDMY(date); validateBirth(); closeDP(); });
        grid.appendChild(el);
      }
      dpWrap.append(grid);
      prev.addEventListener('click',()=>{ currentMonth.setMonth(currentMonth.getMonth()-1); buildDP(); });
      next.addEventListener('click',()=>{ currentMonth.setMonth(currentMonth.getMonth()+1); buildDP(); });
    }
    function openDP(){ const base=birth.value?parseYMD(birth.value):new Date(); currentMonth=new Date(base.getFullYear(), base.getMonth(), 1); dpWrap.classList.add('open'); buildDP(); document.addEventListener('click', dpDocClose, true); document.addEventListener('keydown', dpKeyClose, true); }
    function closeDP(){ dpWrap.classList.remove('open'); document.removeEventListener('click', dpDocClose, true); document.removeEventListener('keydown', dpKeyClose, true); }
    function dpDocClose(e){ if(!field.contains(e.target)) closeDP(); }
    function dpKeyClose(e){ if(e.key==='Escape') closeDP(); }
    birthDisp.addEventListener('click', openDP);
    birthDisp.addEventListener('keydown', e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); openDP(); } });
    const syncFromText=()=>{ const d=parseDMY(birthDisp.value); if(!birthDisp.value.trim()){ birth.value=''; birthOK=false; birthHint.textContent=''; refreshSubmit(); return; } if(!d){ birth.value=''; birthOK=false; birthHint.textContent='Date invalide.'; refreshSubmit(); return; } birth.value=toYMD(d); birthDisp.value=fmtDMY(d); validateBirth(); };
    birthDisp.addEventListener('blur', syncFromText);
  }

  email.addEventListener('input', validateEmailPhone);
  phone.addEventListener('input', validateEmailPhone);
  dial.addEventListener('change', validateEmailPhone);
  validateEmailPhone(); refreshSubmit();
});
</script>
</body>
</html>
