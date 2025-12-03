<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$message = '';
$error = '';

function sanitize_project_name(string $name): string {
    // Allow letters, numbers, dash, underscore. Remove leading/trailing non-alphanum.
    $name = preg_replace('/[^A-Za-z0-9\-_]/', '', $name);
    $name = preg_replace('/^[\-_]+|[\-_]+$/', '', $name);
    return mb_substr($name, 0, 60);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $rawName = (string)($_POST['project_name'] ?? '');
    $name = sanitize_project_name($rawName);
    
    if ($name === '') {
        $error = 'Project name is required (letters, numbers, - and _ allowed).';
    } else {
        $targetDesired = __DIR__ . DIRECTORY_SEPARATOR . $name;

        if (file_exists($targetDesired)) {
            $error = "Folder <strong>" . htmlspecialchars($name) . "</strong> already exists.";
        } else {
            if (!@mkdir($targetDesired, 0755, true)) {
                $error = "Failed to create folder. Check filesystem permissions for " . htmlspecialchars(__DIR__);
            } else {
                // Always create minimal index.php
                file_put_contents($targetDesired . DIRECTORY_SEPARATOR . 'index.php', "<!doctype html>\n<html><head><title>$name</title></head><body><h1>Welcome to $name</h1></body></html>");

                $message = "Project <strong>" . htmlspecialchars($name) . "</strong> created.";

                // Path to open in browser (for JavaScript)
                $openProjectUrl = '/' . rawurlencode($name) . '/';

                //  Attempt to open in VS Code using cmd /C for better Windows/XAMPP reliability.
                $vsCodeCmd = 'cmd /C "code ' . escapeshellarg($targetDesired) . ' > NUL 2>&1"';
                @exec($vsCodeCmd);
            }
        }
    }
}


// Auto-list projects
$projects = array_filter(glob('*'), function($i){
    return is_dir($i) && $i !== "xampp" && $i !== "." && $i !== "..";
});

// Server status + system info
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$apache_running = (stripos($server_software, 'apache') !== false) || (stripos($server_software, 'httpd') !== false);

$mysql_running = false;
$mysql_error = '';
if (function_exists('mysqli_connect')) {
    @$conn = @mysqli_connect('127.0.0.1', 'root', '', '', 3306);
    if ($conn) {
        $mysql_running = true;
        mysqli_close($conn);
    } else {
        $mysql_running = false;
        $mysql_error = mysqli_connect_error();
    }
} else {
    $mysql_error = 'mysqli extension missing';
}

$php_version = phpversion();
$important_exts = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'curl', 'json', 'openssl', 'gd', 'exif'];
$ext_status = [];
$loaded = get_loaded_extensions();
foreach ($important_exts as $e) {
    $ext_status[$e] = in_array($e, $loaded);
}

// RAM info
$ram_total = null; $ram_free = null; $ram_used = null; $ram_percent = null;
if (is_readable('/proc/meminfo')) {
    $data = file_get_contents('/proc/meminfo');
    if (preg_match('/MemTotal:\s+(\d+)\skB/i', $data, $m)) $ram_total = (int)$m[1] * 1024;
    if (preg_match('/MemAvailable:\s+(\d+)\skB/i', $data, $m2)) $ram_free = (int)$m2[1] * 1024;
    elseif (preg_match('/MemFree:\s+(\d+)\skB/i', $data, $m2)) $ram_free = (int)$m2[1] * 1024;
    if ($ram_total && $ram_free) { $ram_used = $ram_total - $ram_free; $ram_percent = round($ram_used / $ram_total * 100, 1); }
} elseif (stripos(PHP_OS, 'WIN') !== false) {
    $output = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
    if ($output && preg_match('/TotalVisibleMemorySize=(\d+)/i', $output, $a) && preg_match('/FreePhysicalMemory=(\d+)/i', $output, $b)) {
        $totalKB = (int)$a[1]; $freeKB = (int)$b[1];
        $ram_total = $totalKB * 1024; $ram_free = $freeKB * 1024;
        $ram_used = $ram_total - $ram_free;
        $ram_percent = round($ram_used / $ram_total * 100, 1);
    }
}

function human_bytes($bytes) {
    if ($bytes === null) return 'N/A';
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Localhost — Tanjimul Islam Tareq</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    /* 🌙 Dark Mode Defaults */
    :root { 
        --glass-bg: rgba(255,255,255,0.06); 
        --glass-border: rgba(255,255,255,0.08); 
    }
    .glass { 
        background: var(--glass-bg); 
        backdrop-filter: blur(10px); 
        border: 1px solid var(--glass-border); 
        transition: background 0.3s, border 0.3s;
    }
    .glass:hover {
        background: rgba(255,255,255,0.1);
    }
    
    /* ☀️ Light Mode Overrides (when 'light' class is on <html>) */
    .light body { 
        /* Very light gray background */
        background-image: none;
        background-color: #f5f5f5; 
        /* Dark text for contrast */
        color: #1f2937;
    }
    .light .glass { 
        /* White, opaque background */
        background: #ffffff; 
        /* Subtle light gray border */
        border-color: #e5e7eb; 
        /* Light shadow to lift elements */
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    }
    .light .glass:hover {
        /* Slightly visible hover effect */
        background: #f9fafb;
    }

    /* General styles */
    .glow-border { position: relative; overflow: hidden; border-radius: 14px; }
    .glow-border::before { content:""; position:absolute; inset:0; padding:2px; border-radius:inherit; background: linear-gradient(90deg,#06b6d4,#7c3aed,#60a5fa); -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; opacity:0.45; pointer-events:none; }
    .project-item { min-height: 64px; display: flex; align-items:center; justify-content:center; }
</style>

<script>
function toggleTheme(){ 
    document.documentElement.classList.toggle('light'); 
}
function searchProjects(){ 
    let q = document.getElementById('searchInput').value.toLowerCase(); 
    document.querySelectorAll('.project-item').forEach(it=>{ 
        it.style.display = it.innerText.toLowerCase().includes(q)?'flex':'none'; 
    }); 
}
function openModal(){ document.getElementById('createModal').classList.remove('hidden'); document.getElementById('project_name').focus(); }
function closeModal(){ document.getElementById('createModal').classList.add('hidden'); }
</script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-black text-white">

<div class="fixed top-6 right-6 flex gap-3 z-40">
    <button onclick="toggleTheme()" class="glass px-3 py-2 rounded-full flex items-center gap-2 hover:scale-105 transition">
        <i data-lucide="moon"></i> Theme
    </button>
    <button onclick="openModal()" class="bg-gradient-to-r from-indigo-500 to-cyan-400 px-4 py-2 rounded-full shadow-lg hover:scale-105 transition flex items-center gap-2 font-medium">
        <i data-lucide="plus"></i> Create Project
    </button>
</div>

<div id="createModal" class="fixed inset-0 flex items-center justify-center bg-black/50 hidden z-50">
    <div class="w-full max-w-xl p-6 glass rounded-2xl relative">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-xl"><i data-lucide="x"></i></button>
        <h3 class="text-2xl font-semibold mb-6 flex items-center gap-2"><i data-lucide="folder-plus"></i> Create Project</h3>

        <form method="post" class="space-y-4">
            <input id="project_name" name="project_name" placeholder="Project name (letters, numbers, - _ )" class="w-full p-3 rounded-lg text-slate-800" required />

            <p class="text-sm opacity-70">A project will be created as an empty folder with a minimal `index.php` file.</p>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg bg-transparent border border-white/10 light:border-slate-300">Cancel</button>
                <button type="submit" name="create_project" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-cyan-400">Create</button>
            </div>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto px-20 pt-32 pb-14">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <div class="glass p-6 rounded-2xl shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2"><i data-lucide="server"></i><h3 class="text-lg font-semibold">Web Server</h3></div>
                    <div class="text-sm opacity-80">Software: <strong><?= htmlspecialchars($server_software) ?></strong></div>
                </div>
                <div class="text-right">
                    <?php if ($apache_running): ?>
                        <div class="text-green-400 font-semibold">Running</div>
                    <?php else: ?>
                        <div class="text-rose-400 font-semibold">Unknown/Stopped</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="glass p-6 rounded-2xl shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2"><i data-lucide="database"></i><h3 class="text-lg font-semibold">Database</h3></div>
                    <div class="text-sm opacity-80">Host: <strong>127.0.0.1</strong></div>
                    <?php if (!$mysql_running && $mysql_error): ?>
                        <div class="text-xs mt-1 opacity-70">Error: <?= htmlspecialchars($mysql_error) ?></div>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <?php if ($mysql_running): ?>
                        <div class="text-green-400 font-semibold">Running</div>
                    <?php else: ?>
                        <div class="text-rose-400 font-semibold">Stopped</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="glass p-6 rounded-2xl shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2"><i data-lucide="code"></i><h3 class="text-lg font-semibold">PHP</h3></div>
                    <div class="text-sm opacity-80">Version: <strong><?= htmlspecialchars($php_version) ?></strong></div>
                </div>
                <div class="text-right">
                    <a href="/phpinfo.php" target="_blank" class="text-sm underline opacity-80">phpinfo()</a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="glass p-6 rounded-2xl shadow col-span-2">
            <h4 class="text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="info"></i> System Info</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="text-sm opacity-70">Memory</div>
                    <div class="mt-1 font-medium"><?= human_bytes($ram_total) ?> total</div>
                    <div class="text-xs opacity-70"><?= human_bytes($ram_used) ?> used (<?= $ram_percent ?? 'N/A' ?>%)</div>
                </div>
                <div>
                    <div class="text-sm opacity-70">PHP</div>
                    <div class="mt-1 font-medium"><?= htmlspecialchars($php_version) ?></div>
                    <div class="text-xs opacity-70">SAPI: <?= htmlspecialchars(php_sapi_name()) ?></div>
                </div>
                <div>
                    <div class="text-sm opacity-70">Paths</div>
                    <div class="mt-1 font-medium">Document Root</div>
                    <div class="text-xs opacity-70"><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) ?></div>
                </div>
            </div>
        </div>

        <div class="glass p-6 rounded-2xl shadow">
            <h4 class="text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="puzzle"></i> Extensions</h4>
            <ul class="text-sm space-y-2">
                <?php foreach ($ext_status as $k => $v): ?>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?= $v ? 'bg-green-400' : 'bg-rose-400' ?>"></span>
                            <span class="opacity-85"><?= htmlspecialchars($k) ?></span>
                        </div>
                        <div class="opacity-75 text-xs"><?= $v ? 'Loaded' : 'Missing' ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="glass glow-border p-8 rounded-2xl shadow-xl mb-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="/phpmyadmin/" class="glass p-4 rounded-xl flex items-center gap-3 hover:scale-105 transition">
                <i data-lucide="database"></i> phpMyAdmin
            </a>
            <a href="/dashboard/" class="glass p-4 rounded-xl flex items-center gap-3 hover:scale-105 transition">
                <i data-lucide="layout-dashboard"></i> XAMPP Dashboard
            </a>
        </div>
    </div>

    <div class="glass p-8 rounded-2xl shadow-xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold flex items-center gap-3"><i data-lucide="folder"></i> All Projects</h3>
            <div class="text-sm opacity-80"><?= count($projects) ?> projects</div>
        </div>

        <div class="mb-6 relative">
            <i data-lucide="search" class="absolute left-4 top-3 text-slate-400"></i>
            <input id="searchInput" onkeyup="searchProjects()" placeholder="Search projects..." class="w-full p-4 pl-12 rounded-xl text-slate-800" />
        </div>

        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($projects as $p): ?>
                <a href="/<?= rawurlencode($p) ?>/" class="project-item glass p-5 rounded-xl hover:scale-105 transition text-center">
                    <?= htmlspecialchars(ucfirst($p)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="text-center mt-12">
        <div class="flex justify-center gap-6 text-2xl mb-4">
            <a href="https://www.facebook.com/TanjimulIslamTareq/" class="hover:opacity-75"><i data-lucide="facebook"></i></a>
            <a href="https://www.instagram.com/tanjimulislamtareq/" class="hover:opacity-75"><i data-lucide="instagram"></i></a>
            <a href="https://github.com/engineertareq/" class="hover:opacity-75"><i data-lucide="github"></i></a>
            <a href="https://www.engineertareq.com/" class="hover:opacity-75"><i data-lucide="globe"></i></a>
        </div>
        <p class="opacity-70 text-sm">Built with ❤️ by <strong>Tanjimul Islam Tareq</strong></p>
    </div>

    <?php if (!empty($message)): ?>
        <div id="toastMsg" class="fixed bottom-6 left-6 bg-green-600 text-white px-4 py-2 rounded shadow z-50">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div id="toastErr" class="fixed bottom-6 left-6 bg-rose-500 text-white px-4 py-2 rounded shadow z-50">
            <?= $error ?>
        </div>
    <?php endif; ?>

</div>

<script>
    lucide.createIcons();

    // If PHP created a project this request, open it in a new tab
    <?php if (!empty($openProjectUrl)): ?>
        (function(){
            try {
                window.open(<?= json_encode($openProjectUrl) ?>, '_blank');
            } catch(e) {
                console.warn('Could not open new tab automatically.');
            }
        })();
    <?php endif; ?>
</script>
</body>
</html>