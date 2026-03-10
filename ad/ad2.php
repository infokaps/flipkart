<?php
session_start();

/*
  merchant-admin.php
  Single-file PHP admin UI to edit ../js/merchant.json
  - Hardcoded credentials below
  - Does NOT include any default merchant.json content
  - If ../js/merchant.json exists it will be loaded server-side but nothing is shown until login
  - Individual field saves immediately write the file on the server (AJAX)
*/

// ========== CONFIG ==========
$JSON_PATH = __DIR__ . '/../js/merchant.json'; // adjust if needed
$ADMIN_USERNAME = '2';
$ADMIN_PASSWORD = '2'; // change before production
// ============================

function load_merchant($path) {
    if (file_exists($path)) {
        $raw = file_get_contents($path);
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;
    }
    return [
        "upiId" => "",
        "merchantName" => "",
        "merchantCode" => "",
        "transactionPrefix" => "",
        "qrMedium" => ""
    ];
}

$merchant = load_merchant($JSON_PATH);
$message = "";

// simple helper to persist merchant to disk and return boolean
function persist_merchant($path, $merchantArr) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) return false;
    }
    $jsonText = json_encode($merchantArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return @file_put_contents($path, $jsonText) !== false;
}

// AUTH: login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if ($u === $ADMIN_USERNAME && $p === $ADMIN_PASSWORD) {
        $_SESSION['auth'] = true;
        $message = "Welcome, admin!";
    } else {
        $message = "Invalid credentials.";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    unset($_SESSION['auth']);
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Update single field (AJAX or normal). This now immediately writes the file to disk when authenticated.
if (isset($_POST['action']) && $_POST['action'] === 'update_field' && !empty($_SESSION['auth'])) {
    $key = $_POST['field_key'] ?? '';
    $value = $_POST['field_value'] ?? '';
    $allowed = array_keys($merchant);
    if (in_array($key, $allowed, true)) {
        $merchant[$key] = $value;
        $ok = persist_merchant($JSON_PATH, $merchant);
        $message = htmlspecialchars($key) . ($ok ? " saved to file." : " updated locally but failed to write file. Check permissions.");
        // If AJAX request, return JSON and exit
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([ 'status' => $ok ? 'ok' : 'error', 'message' => $message, 'merchant' => $merchant ]);
            exit;
        }
    } else {
        $message = "Unknown field.";
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode([ 'status' => 'error', 'message' => $message ]);
            exit;
        }
    }
}

// Reset to blanks (does NOT delete file). Keep as manual action if authenticated.
if (isset($_POST['action']) && $_POST['action'] === 'reset' && !empty($_SESSION['auth'])) {
    foreach ($merchant as $k => $v) $merchant[$k] = "";
    $message = "Fields cleared (file not deleted). Save by editing a field to persist.";
}

// Download current merchant JSON (only after auth)
if (isset($_GET['download']) && !empty($_SESSION['auth'])) {
    $jsonText = json_encode($merchant, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="merchant.json"');
    echo $jsonText;
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Merchant Admin Panel</title>
<style>
  :root{ --blue:#0b6cf0; --blue-50:#eef6ff; --muted:#6b7280; --card:#ffffff; }
  html,body{height:100%;margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;}
  body{display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#f8fbff 0%, #ffffff 100%);padding:24px;}
  .panel{width:100%;max-width:1100px;background:var(--card);border-radius:14px;box-shadow:0 10px 30px rgba(11,108,240,0.08);overflow:hidden;display:flex;flex-wrap:wrap;animation:fadeIn 420ms ease;}
  .sidebar{flex:0 0 320px;padding:28px;background:linear-gradient(180deg,#ffffff 0%,var(--blue-50) 100%);border-right:1px solid rgba(11,108,240,0.05);} 
  .main{flex:1;padding:28px;background:linear-gradient(180deg,var(--blue-50) 0%,#fff 100%);} 
  h1{color:var(--blue);margin:0 0 8px;font-size:20px;} 
  p.sub{color:var(--muted);font-size:13px;margin-top:0} 
  .status{margin-top:16px;font-size:13px;} 
  .btn{display:inline-block;padding:10px 14px;border-radius:10px;font-weight:600;border:0;cursor:pointer} 
  .btn-primary{background:var(--blue);color:#fff;box-shadow:0 6px 18px rgba(11,108,240,0.16);transition:transform .14s ease} 
  .btn-primary:active{transform:translateY(1px)} 
  .btn-ghost{background:#fff;border:1px solid rgba(11,108,240,0.12);color:var(--blue)} 
  .field-card{background:#fff;border-radius:10px;padding:14px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 6px 14px rgba(16,24,40,0.03);margin-bottom:12px;transition:transform .25s ease,box-shadow .25s ease} 
  .field-card:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(16,24,40,0.06)} 
  .field-left{max-width:64%} 
  .field-key{font-size:11px;color:var(--muted);text-transform:uppercase} 
  .field-val{margin-top:6px;font-size:14px;color:#111;word-break:break-all} 
  .field-actions{display:flex;gap:8px} 
  .input{width:100%;padding:8px 10px;border-radius:8px;border:1px solid #e6e9ef;font-size:13px} 
  pre.json-box{background:#fff;padding:12px;border-radius:10px;border:1px solid rgba(11,108,240,0.04);font-size:12px;overflow:auto;max-height:220px} 
  .login-box{margin-top:18px;padding:12px;border-radius:10px;background:#ffffff;} 
  .small{font-size:12px;color:var(--muted)} 
  .msg{margin-top:12px;color:var(--blue);font-weight:600} 
  .top-actions{display:flex;gap:10px;margin-bottom:14px} 
  @keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}} 
  @media (max-width:880px){ .sidebar{flex-basis:100%;border-right:none;border-bottom:1px solid rgba(11,108,240,0.04)} .panel{flex-direction:column} }
</style>
</head>
<body>
<div class="panel" role="main" aria-live="polite">
  <div class="sidebar" aria-label="sidebar">
    <h1>Merchant Admin</h1>

    <div class="status">
      Status:
      <?php if (!empty($_SESSION['auth'])): ?>
        <span style="color:green;font-weight:700"> Logged in</span>
      <?php else: ?>
        <span style="color:#c026d3;font-weight:700"> Locked</span>
      <?php endif; ?>
    </div>

    <div style="margin-top:14px">
      <?php if (!empty($_SESSION['auth'])): ?>
        <button class="btn btn-primary" onclick="location.href='?download=1'">Download JSON</button>
        <button class="btn btn-ghost" onclick="copyJSON()">Copy JSON</button>
      <?php else: ?>
        <div class="small">Log in to access export and edit actions.</div>
      <?php endif; ?>
    </div>

    <div class="login-box">
      <?php if (empty($_SESSION['auth'])): ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="action" value="login">
          <label class="small">Username</label>
          <input name="username" class="input" style="margin-top:6px" required>
          <label class="small" style="margin-top:8px;display:block">Password</label>
          <input type="password" name="password" class="input" style="margin-top:6px" required>
          <div style="margin-top:10px;display:flex;gap:8px">
            <button class="btn btn-primary" type="submit">Login</button>
            <button class="btn btn-ghost" type="button" onclick="alert('Hint: use admin / Secret@123')">Hint</button>
          </div>
        </form>
      <?php else: ?>
        <div class="small">You are logged in as <strong>admin</strong>.</div>
        <div style="margin-top:10px">
          <a href="?logout=1" class="btn btn-ghost" style="text-decoration:none">Logout</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($message): ?>
      <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div style="margin-top:14px" class="small">Note: ensure the webserver user can write to the target path when you save.</div>
  </div>

  <div class="main" aria-label="editor">
    <div class="top-actions">
      <?php if (!empty($_SESSION['auth'])): ?>
        <!-- Save to File removed; individual field saves auto-persist -->
        <form method="post" style="display:inline;margin-left:6px">
          <input type="hidden" name="action" value="reset">
          <button type="submit" class="btn btn-ghost">Reset to Blank</button>
        </form>
      <?php endif; ?>

      <div style="margin-left:auto" class="small">Server-side file path is protected.</div>
    </div>

    <h2 style="margin:0 0 10px;color:var(--blue)">Fields</h2>

    <?php if (!empty($_SESSION['auth'])): ?>
      <!-- Editable form: input fields and per-field auto-save -->
      <form id="merchantForm" method="post">
        <input type="hidden" name="action" value="save">
        <?php foreach ($merchant as $key => $value): ?>
          <div class="field-card" id="card-<?php echo htmlspecialchars($key); ?>">
            <div class="field-left">
              <div class="field-key"><?php echo htmlspecialchars($key); ?></div>
              <div class="field-val" id="val-<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($value ?: ''); ?></div>

              <!-- Hidden input shown when editing -->
              <div style="margin-top:8px;display:none" id="editbox-<?php echo htmlspecialchars($key); ?>">
                <input class="input" name="merchant[<?php echo htmlspecialchars($key); ?>]" id="input-<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
              </div>
            </div>

            <div class="field-actions">
              <button type="button" class="btn btn-ghost" onclick="toggleEdit('<?php echo $key; ?>')" id="editbtn-<?php echo $key; ?>">Edit</button>

              <button type="button" class="btn btn-primary" id="savebtn-<?php echo $key; ?>" style="display:none" onclick="saveField('<?php echo $key; ?>')">Save</button>
            </div>
          </div>
        <?php endforeach; ?>

        <div style="margin-top:6px">
          <div class="small">Tip: click Edit on a field, change the value and press Save — it will be written to disk immediately.</div>
        </div>
      </form>

      <div style="margin-top:18px">
        <h3 class="small" style="margin:0 0 6px;color:var(--muted)">Raw JSON (preview)</h3>
        <pre id="rawjson" class="json-box"><?php echo htmlspecialchars(json_encode($merchant, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
      </div>

    <?php else: ?>
      <div class="small">Log in to view and edit merchant values.</div>
    <?php endif; ?>

  </div>
</div>

<script>
  function toggleEdit(key){
    var editbox = document.getElementById('editbox-' + key);
    var valdiv = document.getElementById('val-' + key);
    var editbtn = document.getElementById('editbtn-' + key);
    var savebtn = document.getElementById('savebtn-' + key);
    var input = document.getElementById('input-' + key);

    if(!editbox || !input) return;
    var showing = editbox.style.display !== 'none';

    if(!showing){
      input.value = valdiv.textContent.trim();
      editbox.style.display = 'block';
      valdiv.style.display = 'none';
      editbtn.textContent = 'Cancel';
      savebtn.style.display = 'inline-block';
      input.focus();
    } else {
      editbox.style.display = 'none';
      valdiv.style.display = 'block';
      editbtn.textContent = 'Edit';
      savebtn.style.display = 'none';
    }
  }

  async function saveField(key){
    var input = document.getElementById('input-' + key);
    var valdiv = document.getElementById('val-' + key);
    if(!input) return;
    var fd = new FormData();
    fd.append('action','update_field');
    fd.append('field_key', key);
    fd.append('field_value', input.value);

    // signal AJAX to server
    const res = await fetch(location.href, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    });

    if (res.ok) {
      const data = await res.json();
      if (data.status === 'ok') {
        // update UI
        valdiv.textContent = input.value;
        document.getElementById('editbox-' + key).style.display = 'none';
        document.getElementById('editbtn-' + key).textContent = 'Edit';
        document.getElementById('savebtn-' + key).style.display = 'none';
        // update raw JSON preview
        if (data.merchant) document.getElementById('rawjson').innerText = JSON.stringify(data.merchant, null, 2);
        alert(data.message);
      } else {
        alert(data.message || 'Save failed');
      }
    } else {
      alert('Network error while saving');
    }
  }

  function copyJSON(){
    var pre = document.getElementById('rawjson');
    var text = pre ? pre.innerText : '';
    if(!text) return alert('No JSON to copy');
    navigator.clipboard.writeText(text).then(function(){ alert('Copied JSON to clipboard'); });
  }

  // keep raw JSON updated when editing client-side values (visual only — server persists on Save button)
  document.querySelectorAll('[id^=input-]').forEach(function(inp){
    inp && inp.addEventListener('input', function(){
      var k = this.id.replace('input-','');
      var valdiv = document.getElementById('val-'+k);
      if(valdiv) valdiv.textContent = this.value;
      var obj = {};
      document.querySelectorAll('[id^=input-]').forEach(function(i){
        var key = i.id.replace('input-','');
        obj[key] = i.value;
      });
      var raw = document.getElementById('rawjson');
      if(raw) raw.innerText = JSON.stringify(obj, null, 2);
    });
  });

</script>
</body>
</html>