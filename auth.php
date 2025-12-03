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
  (function () {
    const signupForm = document.getElementById('signupForm');
    const loginForm = document.getElementById('loginForm');
    const tabSignup = document.getElementById('tab-signup');
    const tabLogin = document.getElementById('tab-login');

    function showSignup() {
      signupForm.classList.remove('hidden');
      loginForm.classList.add('hidden');
      tabSignup.classList.add('active');
      tabLogin.classList.remove('active');
    }

    function showLogin() {
      signupForm.classList.add('hidden');
      loginForm.classList.remove('hidden');
      tabSignup.classList.remove('active');
      tabLogin.classList.add('active');
    }

    tabSignup.addEventListener('click', showSignup);
    tabLogin.addEventListener('click', showLogin);
  })();
</script>
</body>
</html>
