<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Premium URL Shortener — Setup Tool</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px; max-width: 680px; width: 100%; }
    h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 6px; color: #f1f5f9; }
    .sub { color: #94a3b8; font-size: 0.9rem; margin-bottom: 30px; }
    .step { background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 20px; margin-bottom: 16px; }
    .step h2 { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 12px; }
    .status { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; margin-bottom: 8px; }
    .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .dot.ok { background: #22c55e; }
    .dot.warn { background: #f59e0b; }
    .dot.err { background: #ef4444; }
    .dot.info { background: #3b82f6; }
    label.upload-area { display: block; border: 2px dashed #334155; border-radius: 10px; padding: 24px; text-align: center; cursor: pointer; transition: border-color 0.2s; margin-bottom: 12px; }
    label.upload-area:hover { border-color: #6366f1; }
    label.upload-area input { display: none; }
    .upload-icon { font-size: 1.8rem; margin-bottom: 6px; }
    .upload-text { font-size: 0.9rem; color: #94a3b8; }
    .upload-text strong { color: #e2e8f0; }
    button.btn { border: none; border-radius: 8px; padding: 12px 24px; font-size: 0.95rem; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.2s; }
    button.btn-primary { background: #6366f1; color: white; }
    button.btn-primary:hover { background: #4f46e5; }
    button.btn-danger { background: #dc2626; color: white; }
    button.btn-danger:hover { background: #b91c1c; }
    button.btn:disabled { background: #334155; color: #64748b; cursor: not-allowed; }
    .log { background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 16px; font-family: monospace; font-size: 0.8rem; max-height: 250px; overflow-y: auto; margin-top: 16px; display: none; }
    .log-line { margin-bottom: 4px; }
    .log-line.ok { color: #22c55e; }
    .log-line.err { color: #ef4444; }
    .log-line.info { color: #94a3b8; }
    .log-line.warn { color: #f59e0b; }
    .progress { background: #0f172a; border-radius: 999px; height: 6px; margin-top: 12px; display: none; }
    .progress-bar { height: 6px; border-radius: 999px; width: 0%; transition: width 0.3s; }
    .progress-bar.purple { background: #6366f1; }
    .progress-bar.green { background: #22c55e; }
    .success-banner { background: #052e16; border: 1px solid #22c55e; border-radius: 10px; padding: 16px; text-align: center; display: none; margin-top: 16px; }
    .success-banner a { color: #4ade80; font-weight: 600; }
    .filename { background: #334155; border-radius: 6px; padding: 6px 12px; font-size: 0.85rem; color: #94a3b8; margin-bottom: 12px; display: none; }
    .warn-box { background: #431407; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; font-size: 0.85rem; color: #fcd34d; margin-bottom: 16px; }
    .info-box { background: #0c1a2e; border: 1px solid #3b82f6; border-radius: 8px; padding: 12px 16px; font-size: 0.85rem; color: #93c5fd; margin-bottom: 16px; }
    .divider { border: none; border-top: 1px solid #334155; margin: 20px 0; }
  </style>
</head>
<body>
<?php
$appDir   = '/app-data';
$dbHost   = getenv('DB_HOST')   ?: 'db';
$dbPort   = getenv('DB_PORT')   ?: '3306';
$dbName   = getenv('DB_NAME')   ?: 'shortener';
$dbUser   = getenv('DB_USER')   ?: 'shortener';
$dbPass   = getenv('DB_PASS')   ?: '';

// ── Helper ──────────────────────────────────────────────────────────────────
function checkStatus() {
    global $appDir, $dbHost, $dbPort, $dbUser, $dbPass, $dbName;
    return [
        'dir_writable'      => is_writable($appDir),
        'zip_installed'     => (shell_exec('which unzip') !== null),
        'mysql_installed'   => (shell_exec('which mysql') !== null),
        'already_installed' => file_exists($appDir . '/config.php'),
        'has_public'        => is_dir($appDir . '/public'),
        'db_reachable'      => @fsockopen($dbHost, (int)$dbPort, $e, $s, 2) !== false,
    ];
}

// ── POST: ZIP upload ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
    header('Content-Type: application/json');
    $log = [];
    $error = false;

    $file = $_FILES['zipfile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false,'log'=>[['type'=>'err','msg'=>'Upload error code: '.$file['error']]]]);
        exit;
    }
    if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
        echo json_encode(['success'=>false,'log'=>[['type'=>'err','msg'=>'File must be a .zip file']]]);
        exit;
    }

    $log[] = ['type'=>'info','msg'=>'Received: '.$file['name'].' ('.round($file['size']/1024/1024,1).' MB)'];

    $dest = $appDir.'/main.zip';
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'log'=>[['type'=>'err','msg'=>'Failed to move file to '.$dest]]]);
        exit;
    }
    $log[] = ['type'=>'ok','msg'=>'ZIP saved to '.$dest];

    $log[] = ['type'=>'info','msg'=>'Extracting ZIP...'];
    $out = shell_exec('cd '.escapeshellarg($appDir).' && unzip -o main.zip 2>&1');
    if ($out === null) {
        $log[] = ['type'=>'err','msg'=>'unzip command failed'];
        $error = true;
    } else {
        $log[] = ['type'=>'ok','msg'=>'Extraction complete'];
    }

    if (!$error) {
        shell_exec('chown -R www-data:www-data '.escapeshellarg($appDir).' 2>&1');
        shell_exec('chmod -R 755 '.escapeshellarg($appDir).' 2>&1');
        $log[] = ['type'=>'ok','msg'=>'Permissions set (755)'];

        if (is_dir($appDir.'/storage')) {
            shell_exec('chmod -R 777 '.escapeshellarg($appDir.'/storage').' 2>&1');
            $log[] = ['type'=>'ok','msg'=>'storage/ set to 777'];
        }
        @mkdir($appDir.'/storage/cache', 0777, true);
        shell_exec('chmod -R 777 '.escapeshellarg($appDir.'/storage/cache').' 2>&1');
        $log[] = ['type'=>'ok','msg'=>'storage/cache/ ready'];

        @mkdir($appDir.'/public/content', 0777, true);
        shell_exec('chmod -R 777 '.escapeshellarg($appDir.'/public/content').' 2>&1');
        $log[] = ['type'=>'ok','msg'=>'public/content/ ready'];

        if (file_exists($appDir.'/config.sample.php')) {
            shell_exec('chmod 666 '.escapeshellarg($appDir.'/config.sample.php').' 2>&1');
            $log[] = ['type'=>'ok','msg'=>'config.sample.php set to 666'];
        }

        @unlink($dest);
        $log[] = ['type'=>'info','msg'=>'Cleaned up main.zip'];
        $log[] = ['type'=>'ok','msg'=>'✅ Done! Visit the app port to run the installer.'];
    }

    echo json_encode(['success'=>!$error,'log'=>$log]);
    exit;
}

// ── POST: SQL restore ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlfile'])) {
    header('Content-Type: application/json');
    $log = [];

    $file = $_FILES['sqlfile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false,'log'=>[['type'=>'err','msg'=>'Upload error code: '.$file['error']]]]);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['sql', 'gz'])) {
        echo json_encode(['success'=>false,'log'=>[['type'=>'err','msg'=>'File must be a .sql or .sql.gz file']]]);
        exit;
    }

    $log[] = ['type'=>'info','msg'=>'Received: '.$file['name'].' ('.round($file['size']/1024/1024,1).' MB)'];

    $tmpFile = '/tmp/restore.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
        echo json_encode(['success'=>false,'log'=>[['type'=>'err','msg'=>'Failed to save uploaded file']]]);
        exit;
    }
    $log[] = ['type'=>'ok','msg'=>'File saved, starting restore...'];

    // Build mysql command
    $mysqlCmd = 'mysql -h '.escapeshellarg($dbHost)
              .' -P '.escapeshellarg($dbPort)
              .' -u '.escapeshellarg($dbUser)
              .' -p'.escapeshellarg($dbPass)
              .' '.escapeshellarg($dbName);

    if ($ext === 'gz') {
        $cmd = 'zcat '.escapeshellarg($tmpFile).' | '.$mysqlCmd.' 2>&1';
    } else {
        $cmd = $mysqlCmd.' < '.escapeshellarg($tmpFile).' 2>&1';
    }

    exec($cmd, $output, $returnCode);
    @unlink($tmpFile);

    if ($returnCode !== 0) {
        $log[] = ['type'=>'err','msg'=>'Restore failed (exit '.$returnCode.'): '.implode(' ', $output)];
        echo json_encode(['success'=>false,'log'=>$log]);
    } else {
        $log[] = ['type'=>'ok','msg'=>'✅ Database restored successfully!'];
        if (!empty($output)) {
            foreach ($output as $line) {
                if (trim($line)) $log[] = ['type'=>'info','msg'=>$line];
            }
        }
        echo json_encode(['success'=>true,'log'=>$log]);
    }
    exit;
}

$checks = checkStatus();
$host = $_SERVER['HTTP_HOST'] ?? 'umbrel.local';
?>

<div class="card">
  <h1>🔗 Premium URL Shortener</h1>
  <p class="sub">Setup Tool — Upload your app zip and/or restore your database</p>

  <div class="warn-box">
    ⚠️ This tool does not include the software. You must purchase a valid license from
    <a href="https://codecanyon.net/item/premium-url-shortener/3688135" target="_blank" style="color:#fcd34d">CodeCanyon</a>
    and download <strong>main.zip</strong> before continuing.
  </div>

  <!-- System Status -->
  <div class="step">
    <h2>System Status</h2>
    <div class="status">
      <div class="dot <?= $checks['dir_writable'] ? 'ok' : 'err' ?>"></div>
      <span>App directory <?= $checks['dir_writable'] ? 'writable ✓' : 'NOT writable — check Docker volume' ?></span>
    </div>
    <div class="status">
      <div class="dot <?= $checks['zip_installed'] ? 'ok' : 'err' ?>"></div>
      <span>unzip <?= $checks['zip_installed'] ? 'available ✓' : 'NOT found' ?></span>
    </div>
    <div class="status">
      <div class="dot <?= $checks['mysql_installed'] ? 'ok' : 'err' ?>"></div>
      <span>mysql client <?= $checks['mysql_installed'] ? 'available ✓' : 'NOT found (restore unavailable)' ?></span>
    </div>
    <div class="status">
      <div class="dot <?= $checks['db_reachable'] ? 'ok' : 'warn' ?>"></div>
      <span>Database <?= $checks['db_reachable'] ? 'reachable ✓' : 'not yet reachable (may still be starting)' ?></span>
    </div>
    <div class="status">
      <div class="dot <?= $checks['already_installed'] ? 'warn' : 'info' ?>"></div>
      <span><?= $checks['already_installed'] ? '⚠️ config.php exists — uploading will overwrite files' : 'No existing install detected — fresh install' ?></span>
    </div>
    <?php if ($checks['has_public']): ?>
    <div class="status">
      <div class="dot ok"></div>
      <span>App files already extracted ✓</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Step 1: Upload ZIP -->
  <div class="step">
    <h2>Step 1 — Upload main.zip</h2>
    <form id="zipForm">
      <label class="upload-area" id="zipDropzone">
        <input type="file" name="zipfile" id="zipfile" accept=".zip">
        <div class="upload-icon">📦</div>
        <div class="upload-text"><strong>Click to select main.zip</strong><br>or drag and drop here</div>
      </label>
      <div class="filename" id="zipFilename"></div>
      <button type="submit" class="btn btn-primary" id="zipBtn" disabled>Upload & Install</button>
    </form>
    <div class="progress" id="zipProgress"><div class="progress-bar purple" id="zipBar"></div></div>
    <div class="log" id="zipLog"></div>
    <div class="success-banner" id="zipSuccess">
      ✅ App files extracted!<br><br>
      Now visit the <strong>main app port</strong> in Umbrel to run the built-in database installer.
    </div>
  </div>

  <hr class="divider">

  <!-- Step 2: Restore DB -->
  <div class="step">
    <h2>Step 2 (optional) — Restore Database Backup</h2>
    <div class="info-box">
      Upload a <strong>.sql</strong> or <strong>.sql.gz</strong> backup from your previous Docker setup.
      This will import it into the MariaDB database (<code><?= htmlspecialchars($dbName) ?></code>).
      Do this <em>before</em> running the app installer if you want to keep your old data.
    </div>
    <?php if (!$checks['db_reachable']): ?>
    <div class="warn-box">⚠️ Database not yet reachable. Wait a moment and refresh before restoring.</div>
    <?php endif; ?>
    <form id="sqlForm">
      <label class="upload-area" id="sqlDropzone">
        <input type="file" name="sqlfile" id="sqlfile" accept=".sql,.gz">
        <div class="upload-icon">🗄️</div>
        <div class="upload-text"><strong>Click to select your .sql or .sql.gz backup</strong><br>or drag and drop here</div>
      </label>
      <div class="filename" id="sqlFilename"></div>
      <button type="submit" class="btn btn-danger" id="sqlBtn" disabled>Restore Database</button>
    </form>
    <div class="progress" id="sqlProgress"><div class="progress-bar green" id="sqlBar"></div></div>
    <div class="log" id="sqlLog"></div>
    <div class="success-banner" id="sqlSuccess">
      ✅ Database restored! Your old data is back.
    </div>
  </div>

  <!-- DB Info -->
  <div class="step">
    <h2>Database Settings for App Installer</h2>
    <div class="status"><div class="dot info"></div><span>Host: <strong><?= htmlspecialchars($dbHost) ?></strong></span></div>
    <div class="status"><div class="dot info"></div><span>Port: <strong><?= htmlspecialchars($dbPort) ?></strong></span></div>
    <div class="status"><div class="dot info"></div><span>Database: <strong><?= htmlspecialchars($dbName) ?></strong></span></div>
    <div class="status"><div class="dot info"></div><span>Username: <strong><?= htmlspecialchars($dbUser) ?></strong></span></div>
    <div class="status"><div class="dot info"></div><span>Password: <em>shown in Umbrel app settings</em></span></div>
    <div class="status"><div class="dot info"></div><span>Table Prefix: <strong>pus_</strong></span></div>
  </div>
</div>

<script>
// ── Generic file picker + drag drop setup ──────────────────────────────────
function setupUpload({ formId, fileInputId, dropzoneId, submitBtnId, filenameId, logId, progressId, progressBarId, successId, uploadFieldName }) {
  const form      = document.getElementById(formId);
  const fileInput = document.getElementById(fileInputId);
  const dropzone  = document.getElementById(dropzoneId);
  const submitBtn = document.getElementById(submitBtnId);
  const logEl     = document.getElementById(logId);
  const progressEl= document.getElementById(progressId);
  const progressBar=document.getElementById(progressBarId);
  const successEl = document.getElementById(successId);
  const filenameEl= document.getElementById(filenameId);

  function onFileChosen(files) {
    if (!files || !files.length) return;
    const f = files[0];
    submitBtn.disabled = false;
    filenameEl.textContent = '📄 ' + f.name + ' (' + (f.size/1024/1024).toFixed(1) + ' MB)';
    filenameEl.style.display = 'block';
  }

  fileInput.addEventListener('change', () => onFileChosen(fileInput.files));
  dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.style.borderColor='#6366f1'; });
  dropzone.addEventListener('dragleave', () => { dropzone.style.borderColor='#334155'; });
  dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.style.borderColor='#334155';
    onFileChosen(e.dataTransfer.files);
    // assign to input so FormData picks it up
    const dt = new DataTransfer();
    for (const f of e.dataTransfer.files) dt.items.add(f);
    fileInput.files = dt.files;
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    logEl.style.display = 'block';
    logEl.innerHTML = '';
    progressEl.style.display = 'block';
    progressBar.style.width = '10%';
    successEl.style.display = 'none';

    const formData = new FormData();
    formData.append(uploadFieldName, fileInput.files[0]);

    addLog(logEl, 'info', 'Uploading ' + fileInput.files[0].name + '...');

    try {
      progressBar.style.width = '30%';
      const res = await fetch(window.location.href, { method:'POST', body:formData });
      progressBar.style.width = '80%';
      const data = await res.json();
      progressBar.style.width = '100%';

      data.log.forEach(l => addLog(logEl, l.type, l.msg));

      if (data.success) {
        successEl.style.display = 'block';
        submitBtn.textContent = '✅ Done!';
      } else {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Retry';
      }
    } catch (err) {
      addLog(logEl, 'err', 'Request failed: ' + err.message);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Retry';
    }
  });
}

function addLog(el, type, msg) {
  const line = document.createElement('div');
  line.className = 'log-line ' + type;
  const icons = { ok:'✅', err:'❌', info:'ℹ️', warn:'⚠️' };
  line.textContent = (icons[type]||'') + ' ' + msg;
  el.appendChild(line);
  el.scrollTop = el.scrollHeight;
}

// ── Init both forms ──────────────────────────────────────────────────────────
setupUpload({
  formId:'zipForm', fileInputId:'zipfile', dropzoneId:'zipDropzone',
  submitBtnId:'zipBtn', filenameId:'zipFilename', logId:'zipLog',
  progressId:'zipProgress', progressBarId:'zipBar', successId:'zipSuccess',
  uploadFieldName:'zipfile'
});

setupUpload({
  formId:'sqlForm', fileInputId:'sqlfile', dropzoneId:'sqlDropzone',
  submitBtnId:'sqlBtn', filenameId:'sqlFilename', logId:'sqlLog',
  progressId:'sqlProgress', progressBarId:'sqlBar', successId:'sqlSuccess',
  uploadFieldName:'sqlfile'
});
</script>
</body>
</html>
