<?php
require __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_HOME);
    exit;
}

$auth_error = $_SESSION['auth_error'] ?? '';
$auth_error_tab = $_SESSION['auth_error_tab'] ?? 'signup';
$auth_debug = $_SESSION['auth_debug'] ?? '';
unset($_SESSION['auth_error'], $_SESSION['auth_error_tab'], $_SESSION['auth_debug']);

$selectedTab = $auth_error_tab === 'login' ? 'login' : 'signup';

$dialingCodes = [
    ['value' => '+33', 'label' => '+33 (FR)'],
    ['value' => '+32', 'label' => '+32 (BE)'],
    ['value' => '+41', 'label' => '+41 (CH)'],
    ['value' => '+352', 'label' => '+352 (LU)'],
    ['value' => '+49', 'label' => '+49 (DE)'],
    ['value' => '+44', 'label' => '+44 (UK)'],
    ['value' => '+34', 'label' => '+34 (ES)'],
    ['value' => '+39', 'label' => '+39 (IT)'],
    ['value' => '+212', 'label' => '+212 (MA)'],
    ['value' => '+216', 'label' => '+216 (TN)'],
    ['value' => '+213', 'label' => '+213 (DZ)'],
    ['value' => '+1', 'label' => '+1 (US/CA)'],
];

$genderOptions = [
    ['value' => '', 'label' => '— Sélectionner —'],
    ['value' => 'Homme', 'label' => 'Homme'],
    ['value' => 'Femme', 'label' => 'Femme'],
    ['value' => 'Autre', 'label' => 'Autre'],
];

$tabs = [
    'signup' => 'Créer un compte',
    'login' => 'Se connecter',
];

function renderOptions(array $options): string
{
    return implode('', array_map(function (array $option) {
        $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');
        return "<option value=\"{$value}\">{$label}</option>";
    }, $options));
}
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
      <?php foreach ($tabs as $tabKey => $tabLabel): ?>
        <button
          class="auth-tab <?php echo ($selectedTab === $tabKey ? 'active' : ''); ?>"
          type="button"
          data-tab="<?php echo htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8'); ?>"
          aria-pressed="<?php echo ($selectedTab === $tabKey ? 'true' : 'false'); ?>"
        >
          <?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?>
        </button>
      <?php endforeach; ?>
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
            <?php echo renderOptions($dialingCodes); ?>
          </select>
          <input type="tel" name="phone_local" id="phone" placeholder="06 12 34 56 78" />
        </div>
        <small id="phoneHint" class="auth-error"></small>
      </label>

      <label>
        <span>Date de naissance <b>*</b></span>
        <div class="date-field" id="dobField">
          <input type="text" class="date-input" id="birthdate_display" name="birthdate_display" placeholder="JJ/MM/AAAA" />
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
          <?php echo renderOptions($genderOptions); ?>
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
        <?php if ($auth_debug): ?>
          <p class="auth-error" style="font-size: 0.85em; opacity: 0.85;">Détails : <?= htmlspecialchars($auth_debug) ?></p>
        <?php endif; ?>
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
          <?php if ($auth_error && $auth_error_tab==='login' && $auth_debug): ?>
            <br><small style="font-size:0.85em;opacity:0.85;">Détails : <?= htmlspecialchars($auth_debug) ?></small>
          <?php endif; ?>
        </span>
        <a class="soft-note" href="reset_request.php">Mot de passe oublié ?</a>
      </div>
    </form>
  </div>
</section>
<script>
  (function () {
    const tabButtons = document.querySelectorAll('[data-tab]');
    const forms = {
      signup: document.getElementById('signupForm'),
      login: document.getElementById('loginForm'),
    };

    function switchTab(tabKey) {
      Object.entries(forms).forEach(([key, form]) => {
        form.classList.toggle('hidden', key !== tabKey);
      });

      tabButtons.forEach((button) => {
        const isActive = button.dataset.tab === tabKey;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', isActive);
      });
    }

    tabButtons.forEach((button) => {
      button.addEventListener('click', () => switchTab(button.dataset.tab));
    });

    switchTab('<?php echo htmlspecialchars($selectedTab, ENT_QUOTES, 'UTF-8'); ?>');
  })();
</script>
</body>
</html>
