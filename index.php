<?php
/**
 * KPSwamy TecZ — Fixed Version
 * SETUP: XAMPP → Start Apache → Save as C:\xampp\htdocs\index.php
 * Create folder: C:\xampp\htdocs\data\ (Full Control)
 * Open: http://localhost
 * Login: admin / kpswamy@2024
 */
session_start();

 $C = [
    'name'    => 'KPSwamy TecZ',
    'address' => 'Tiruvannamalai, TamilNadu, 6060704',
    'phone'   => '9566021167',
    'email'   => 'sbalamuruga96@yahoo.com',
    'gst'     => '33QDCPS4172N1ZS',
    'bank'    => 'STATE BANK OF INDIA',
    'acname'  => 'BALAMURUGAN S',
    'acno'    => '44020607707',
    'ifsc'    => 'SBIN0000993',
];

 $OFFICER_USER = 'admin';
 $OFFICER_PASS = 'kpswamy@2024';
 $DD = __DIR__ . '/data';
if (!is_dir($DD)) {
    mkdir($DD, 0777, true);
}

function loadJson($file, $dir) {
    $path = $dir . '/' . $file . '.json';
    if (!file_exists($path)) return array();
    $content = file_get_contents($path);
    if ($content === false) return array();
    $data = json_decode($content, true);
    return is_array($data) ? $data : array();
}

function saveJson($file, $data, $dir) {
    file_put_contents($dir . '/' . $file . '.json', json_encode($data, JSON_PRETTY_PRINT));
}

function nextInvId($arr) {
    $n = str_pad(count($arr) + 1, 3, '0', STR_PAD_LEFT);
    return 'KPS/INV/' . date('Y') . '-' . date('m') . '/' . $n;
}

function nextCertId($arr) {
    $n = str_pad(count($arr) + 1, 3, '0', STR_PAD_LEFT);
    return 'KPS/CERT/' . date('Y') . '-' . date('m') . '/' . $n;
}

function numToWords($n) {
    $ones = array('','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen');
    $tens = array('','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety');
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[floor($n/10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
    if ($n < 1000) return $ones[floor($n/100)] . ' Hundred' . ($n % 100 ? ' and ' . numToWords($n % 100) : '');
    if ($n < 100000) return numToWords(floor($n/1000)) . ' Thousand' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    if ($n < 10000000) return numToWords(floor($n/100000)) . ' Lakh' . ($n % 100000 ? ' ' . numToWords($n % 100000) : '');
    return numToWords(floor($n/10000000)) . ' Crore' . ($n % 10000000 ? ' ' . numToWords($n % 10000000) : '');
}

function esc($s) {
    return htmlspecialchars($s);
}

function isLoggedIn() {
    return !empty($_SESSION['officer']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ?page=login');
        exit;
    }
}

// ─── Routing ───
 $page   = isset($_GET['page']) ? $_GET['page'] : 'home';
 $viewId = isset($_GET['id']) ? $_GET['id'] : '';
 $action = isset($_POST['action']) ? $_POST['action'] : '';
 $msg    = '';
 $msgType = '';

 $invoices     = loadJson('invoices', $DD);
 $certificates = loadJson('certs', $DD);

// ─── Login Handler ───
if ($action === 'login') {
    $uname = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $upass = isset($_POST['password']) ? $_POST['password'] : '';
    $ucap  = isset($_POST['captcha']) ? $_POST['captcha'] : '';
    $scap  = isset($_SESSION['cap_ans']) ? $_SESSION['cap_ans'] : '';

    if ($uname === $OFFICER_USER && $upass === $OFFICER_PASS && strval($ucap) === strval($scap)) {
        $_SESSION['officer'] = $uname;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header('Location: ?page=dashboard');
        exit;
    } else {
        $msg = 'Invalid username, password, or captcha.';
        $msgType = 'error';
        $page = 'login';
    }
}

// ─── Logout ───
if ($page === 'logout') {
    session_destroy();
    header('Location: ?page=home');
    exit;
}

// ─── Protected Pages ───
 $protected = array('dashboard', 'inv-new', 'inv-list', 'inv-view', 'cert-new', 'cert-list', 'cert-view');
if (in_array($page, $protected)) {
    requireLogin();
}

// ─── Save Invoice ───
if ($action === 'save_inv' && isLoggedIn()) {
    $inv = array(
        'id'        => nextInvId($invoices),
        'date'      => isset($_POST['inv_date']) ? $_POST['inv_date'] : date('Y-m-d'),
        'bill_name' => esc(isset($_POST['bill_name']) ? $_POST['bill_name'] : ''),
        'bill_addr' => esc(isset($_POST['bill_addr']) ? $_POST['bill_addr'] : ''),
        'bill_gst'  => esc(isset($_POST['bill_gst']) ? $_POST['bill_gst'] : ''),
        'bill_state'=> esc(isset($_POST['bill_state']) ? $_POST['bill_state'] : ''),
        'wo_ref'    => esc(isset($_POST['wo_ref']) ? $_POST['wo_ref'] : ''),
        'sac'       => esc(isset($_POST['sac']) ? $_POST['sac'] : '9954'),
        'project'   => esc(isset($_POST['project']) ? $_POST['project'] : ''),
        'items'     => array(),
        'taxable'   => floatval(isset($_POST['taxable']) ? $_POST['taxable'] : 0),
        'igst_rate' => floatval(isset($_POST['igst_rate']) ? $_POST['igst_rate'] : 18),
        'words'     => esc(isset($_POST['words']) ? $_POST['words'] : ''),
        'by'        => $_SESSION['officer'],
        'ts'        => date('Y-m-d H:i:s'),
    );

    $descs = isset($_POST['desc']) ? $_POST['desc'] : array();
    $qtys  = isset($_POST['qty']) ? $_POST['qty'] : array();

    for ($i = 0; $i < count($descs); $i++) {
        if (trim($descs[$i]) !== '') {
            $inv['items'][] = array(
                'desc' => esc($descs[$i]),
                'qty'  => esc(isset($qtys[$i]) ? $qtys[$i] : ''),
            );
        }
    }

    $invoices[] = $inv;
    saveJson('invoices', $invoices, $DD);
    $msg = 'Invoice ' . $inv['id'] . ' created!';
    $msgType = 'success';
    $page = 'inv-list';
}

// ─── Delete Invoice ───
if ($action === 'del_inv' && $viewId !== '' && isLoggedIn()) {
    $newInvs = array();
    foreach ($invoices as $inv) {
        if ($inv['id'] !== $viewId) {
            $newInvs[] = $inv;
        }
    }
    $invoices = $newInvs;
    saveJson('invoices', $invoices, $DD);
    $msg = 'Invoice deleted.';
    $msgType = 'error';
    $page = 'inv-list';
    $viewId = '';
}

// ─── Save Certificate ───
if ($action === 'save_cert' && isLoggedIn()) {
    $crt = array(
        'id'      => nextCertId($certificates),
        'date'    => isset($_POST['cd']) ? $_POST['cd'] : date('Y-m-d'),
        'val'     => esc(isset($_POST['cv']) ? $_POST['cv'] : ''),
        'client'  => esc(isset($_POST['cc']) ? $_POST['cc'] : ''),
        'addr'    => esc(isset($_POST['cadr']) ? $_POST['cadr'] : ''),
        'equip'   => esc(isset($_POST['ce']) ? $_POST['ce'] : ''),
        'serial'  => esc(isset($_POST['cs']) ? $_POST['cs'] : ''),
        'loc'     => esc(isset($_POST['cl']) ? $_POST['cl'] : ''),
        'scope'   => esc(isset($_POST['csc']) ? $_POST['csc'] : ''),
        'std'     => esc(isset($_POST['cst']) ? $_POST['cst'] : ''),
        'result'  => esc(isset($_POST['cr']) ? $_POST['cr'] : ''),
        'remarks' => esc(isset($_POST['crm']) ? $_POST['crm'] : ''),
        'by'      => $_SESSION['officer'],
        'ts'      => date('Y-m-d H:i:s'),
    );
    $certificates[] = $crt;
    saveJson('certs', $certificates, $DD);
    $msg = 'Certificate ' . $crt['id'] . ' generated!';
    $msgType = 'success';
    $page = 'cert-list';
}

// ─── Delete Certificate ───
if ($action === 'del_cert' && $viewId !== '' && isLoggedIn()) {
    $newCerts = array();
    foreach ($certificates as $crt) {
        if ($crt['id'] !== $viewId) {
            $newCerts[] = $crt;
        }
    }
    $certificates = $newCerts;
    saveJson('certs', $certificates, $DD);
    $msg = 'Certificate deleted.';
    $msgType = 'error';
    $page = 'cert-list';
    $viewId = '';
}

// ─── Find Current Record ───
 $currentInv  = null;
 $currentCert = null;

if ($page === 'inv-view' && $viewId !== '') {
    foreach ($invoices as $inv) {
        if ($inv['id'] === $viewId) {
            $currentInv = $inv;
            break;
        }
    }
}
if ($page === 'cert-view' && $viewId !== '') {
    foreach ($certificates as $crt) {
        if ($crt['id'] === $viewId) {
            $currentCert = $crt;
            break;
        }
    }
}

// ─── Captcha ───
 $n1 = rand(1, 20);
 $n2 = rand(1, 20);
 $ops = array('+', '-');
 $op = $ops[array_rand($ops)];
if ($op === '-' && $n1 < $n2) {
    $tmp = $n1; $n1 = $n2; $n2 = $tmp;
}
 $capAns = ($op === '+') ? ($n1 + $n2) : ($n1 - $n2);
 $_SESSION['cap_ans'] = $capAns;
 $capQuestion = $n1 . ' ' . $op . ' ' . $n2 . ' = ?';

// ─── Services Data ───
 $services = array(
    array('i'=>'zap','t'=>'Electrical Installation','d'=>'Complete wiring, installation and commissioning.','f'=>array('Building Wiring','Conduit','Cable Tray','Earthing')),
    array('i'=>'plug-zap','t'=>'Panel Building','d'=>'HT/LT panels, APFC panels, MCC, DBs as per IS standards.','f'=>array('HT/LT Panels','APFC Panels','MCC Panels','DBs')),
    array('i'=>'cpu','t'=>'PLC & SCADA','d'=>'PLC programming, SCADA, HMI for industrial automation.','f'=>array('PLC Programming','SCADA','HMI Design','Process Control')),
    array('i'=>'cable','t'=>'Cable & Wire Supply','d'=>'Polycab, Havells, Finolex — HT/LT cables and wires.','f'=>array('HT Cables','LT Cables','Flexible Wires','Accessories')),
    array('i'=>'lightbulb','t'=>'Lighting Solutions','d'=>'Industrial LED, street lighting, emergency lighting.','f'=>array('Industrial LED','Street Lighting','Emergency Lighting','Smart Controls')),
    array('i'=>'shield-check','t'=>'Testing & Commissioning','d'=>'Earth, insulation, load testing, relay coordination.','f'=>array('Earth Testing','Insulation Testing','Load Testing','Relay Coordination')),
    array('i'=>'rotate-ccw','t'=>'Motor Rewinding','d'=>'AC/DC motor rewinding, transformer repair, generator overhaul.','f'=>array('AC Motor Rewinding','DC Motor Repair','Transformer Repair','Generator Overhaul')),
    array('i'=>'sun','t'=>'Solar Power Systems','d'=>'On-grid, off-grid, hybrid solar PV systems.','f'=>array('On-Grid Solar','Off-Grid Solar','Hybrid Systems','Net Metering')),
);

// ─── Letterpad Theme Colors ───
 $LP = array(
    'navy'   => '#0a2540',
    'gold'   => '#b8962e',
    'goldL'  => '#c9a84c',
    'goldBG' => '#fdf8ee',
    'blueBG' => '#edf2fa',
    'blueBdr'=> '#c5d3e8',
    'dark'   => '#1a1a2e',
    'body'   => '#333344',
    'muted'  => '#666680',
);

 $year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KPSwamy TecZ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
tailwind.config={theme:{extend:{fontFamily:{geist:['Geist','sans-serif'],serif:['Playfair Display','Georgia','serif']},colors:{bl:'#D9FF50',bd:'#0a0a0a',bc:'#171717'}}}}
</script>
<style>
*{box-sizing:border-box}
body{font-family:'Geist',sans-serif;background:#0a0a0a;color:#fff;margin:0}
::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:#0a0a0a}::-webkit-scrollbar-thumb{background:#333;border-radius:3px}
.bg-g{background-image:linear-gradient(to right,rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(to bottom,rgba(255,255,255,.03) 1px,transparent 1px);background-size:80px 80px}
.gl{background:rgba(255,255,255,.05);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px)}
.nl{color:#a3a3a3;transition:color .3s;font-size:14px;font-weight:500;text-decoration:none}
.nl:hover,.nl.on{color:#D9FF50}
.sl{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.15em;color:#D9FF50}
.bp{background:#D9FF50;color:#0a0a0a;font-weight:600;padding:12px 28px;border-radius:12px;transition:all .3s;display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:none;cursor:pointer;font-size:15px;font-family:inherit}
.bp:hover{background:#c8ee44;transform:translateY(-2px);box-shadow:0 10px 30px rgba(217,255,80,.3)}
.bs{background:transparent;color:#fff;font-weight:500;padding:12px 28px;border-radius:12px;border:1px solid rgba(255,255,255,.2);transition:all .3s;display:inline-flex;align-items:center;gap:8px;text-decoration:none;cursor:pointer;font-size:15px;font-family:inherit}
.bs:hover{border-color:rgba(255,255,255,.5);background:rgba(255,255,255,.05)}
.sm{padding:8px 16px;font-size:13px;border-radius:8px}
.fi{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:12px 16px;color:#fff;font-size:14px;font-family:inherit;transition:border-color .3s;width:100%;outline:none}
.fi:focus{border-color:#D9FF50;box-shadow:0 0 0 3px rgba(217,255,80,.1)}
.fi::placeholder{color:#737373}
.cd{background:#171717;border:1px solid rgba(255,255,255,.08);border-radius:20px;transition:all .4s cubic-bezier(.25,1,.5,1)}
.cd:hover{border-color:rgba(217,255,80,.25);transform:translateY(-3px);box-shadow:0 20px 40px -12px rgba(0,0,0,.5)}
.hg{position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(217,255,80,.1) 0%,transparent 70%);border-radius:50%;filter:blur(80px);pointer-events:none}
@keyframes fiu{0%{opacity:0;transform:translateY(25px)}100%{opacity:1;transform:translateY(0)}}
@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@keyframes mq{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
@keyframes pg{0%,100%{box-shadow:0 0 20px rgba(217,255,80,.15)}50%{box-shadow:0 0 40px rgba(217,255,80,.35)}}
.au{animation:fiu .7s ease-out forwards;opacity:0}
.d1{animation-delay:.1s}.d2{animation-delay:.2s}.d3{animation-delay:.3s}.d4{animation-delay:.4s}
.rv{opacity:0;transform:translateY(25px);transition:all .7s ease-out}.rv.vi{opacity:1;transform:translateY(0)}
.mm{transform:translateX(100%);transition:transform .4s cubic-bezier(.25,1,.5,1)}.mm.op{transform:translateX(0)}
.tst{position:fixed;top:90px;right:24px;z-index:9999;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:500;transform:translateX(120%);opacity:0;transition:all .4s cubic-bezier(.25,1,.5,1);max-width:420px}
.tst.sh{transform:translateX(0);opacity:1}
.tst.ok{background:#166534;color:#bbf7d0;border:1px solid #22c55e33}
.tst.er{background:#7f1d1d;color:#fecaca;border:1px solid #ef444433}
html{scroll-behavior:smooth}
.captcha-box{background:rgba(217,255,80,.08);border:2px dashed rgba(217,255,80,.3);border-radius:12px;padding:16px 20px}

@media print{
    body{background:#fff!important;color:#000!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    nav,.np,footer,.tst{display:none!important}
    .pa{display:block!important;box-shadow:none!important;border:none!important;margin:0!important;border-radius:0!important}
    .pa *{color:#1a1a2e!important}
    .lp-head{background:#0a2540!important}
    .lp-head *{color:#fff!important}
    .lp-head .lp-logo-bg{background:#c9a84c!important}
    .lp-head .lp-logo-bg *{color:#0a2540!important}
    .lp-head .lp-gold{color:#c9a84c!important}
    .lp-accent{color:#0a2540!important}
    .lp-gold-text{color:#b8962e!important}
    .lp-table th{background:#edf2fa!important;color:#0a2540!important;border-color:#c5d3e8!important}
    .lp-table td{border-color:#ddd!important}
    .lp-table .lp-bth{border-bottom:2px solid #0a2540!important}
    .lp-total-row{background:#fdf8ee!important}
    .lp-total-row *{color:#b8962e!important;font-weight:700!important}
    .lp-footer{background:#0a2540!important}
    .lp-footer *{color:rgba(201,168,76,.7)!important}
    .lp-stamp{border-color:#b8962e!important}
    .lp-cert-title{color:#0a2540!important}
    .lp-box-blue{background:#edf2fa!important;border:1px solid #c5d3e8!important}
    .lp-box-blue .lp-accent{color:#0a2540!important}
    .lp-box-gold{background:#fdf8ee!important;border:1px solid #e0d5b5!important}
    .lp-box-gold .lp-label{color:#8a7020!important}
    .lp-scope-bar{border-left:3px solid #c9a84c!important}
    @page{margin:8mm;size:A4}
}
</style>
</head>
<body class="font-geist antialiased">

<?php if ($msg !== ''): ?>
<div id="tst" class="tst <?= $msgType === 'success' ? 'ok' : 'er' ?>">
    <div class="flex items-center gap-3">
        <i data-lucide="<?= $msgType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
        <span><?= $msg ?></span>
    </div>
</div>
<?php endif; ?>

<!-- NAV -->
<nav class="np fixed top-0 left-0 right-0 z-50 gl" style="border-bottom:1px solid rgba(255,255,255,.06)">
<div class="max-w-7xl mx-auto px-6 lg:px-8">
<div class="flex items-center justify-between h-16 lg:h-[72px]">
    <a href="?page=home" class="flex items-center gap-3 no-underline">
        <div class="w-10 h-10 rounded-xl bg-bl flex items-center justify-center"><span class="text-black font-bold text-lg">KP</span></div>
        <div class="leading-tight"><span class="text-white font-semibold text-base">KPSwamy</span><span class="text-bl font-semibold text-base"> TecZ</span></div>
    </a>
    <div class="hidden lg:flex items-center gap-7">
        <a href="?page=home" class="nl <?= $page === 'home' ? 'on' : '' ?>">Home</a>
        <a href="?page=home#services" class="nl">Services</a>
        <a href="?page=home#contact" class="nl">Contact</a>
        <?php if (isLoggedIn()): ?>
        <a href="?page=dashboard" class="nl <?= in_array($page, $protected) ? 'on' : '' ?>">Dashboard</a>
        <a href="?page=logout" class="text-red-400 hover:text-red-300 text-sm font-medium no-underline">Logout</a>
        <?php else: ?>
        <a href="?page=login" class="bp sm">Officer Login</a>
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-3">
        <?php if (isLoggedIn()): ?>
        <a href="?page=inv-new" class="hidden sm:inline-flex bp sm"><i data-lucide="plus" class="w-4 h-4"></i> Invoice</a>
        <?php else: ?>
        <a href="?page=login" class="hidden sm:inline-flex bp sm"><i data-lucide="lock" class="w-4 h-4"></i> Login</a>
        <?php endif; ?>
        <button id="mobToggle" class="lg:hidden text-white p-2"><i data-lucide="menu" class="w-6 h-6"></i></button>
    </div>
</div>
</div>
</nav>

<!-- Mobile Menu -->
<div id="mobMenu" class="np mm fixed inset-0 z-[60] bg-bd/98 backdrop-blur-xl flex flex-col">
<div class="flex items-center justify-between px-6 h-16">
    <span class="text-white font-semibold">KPSwamy <span class="text-bl">TecZ</span></span>
    <button id="mobClose" class="text-white p-2"><i data-lucide="x" class="w-6 h-6"></i></button>
</div>
<div class="flex flex-col items-center justify-center flex-1 gap-7">
    <a href="?page=home" class="mlink text-2xl font-medium text-white/70 hover:text-white no-underline">Home</a>
    <a href="?page=home#services" class="mlink text-2xl font-medium text-white/70 hover:text-white no-underline">Services</a>
    <a href="?page=home#contact" class="mlink text-2xl font-medium text-white/70 hover:text-white no-underline">Contact</a>
    <?php if (isLoggedIn()): ?>
    <div class="w-16 h-px bg-white/10 my-2"></div>
    <a href="?page=dashboard" class="mlink text-2xl font-medium text-bl hover:text-white no-underline">Dashboard</a>
    <a href="?page=inv-new" class="mlink text-2xl font-medium text-bl hover:text-white no-underline">New Invoice</a>
    <a href="?page=cert-new" class="mlink text-2xl font-medium text-bl hover:text-white no-underline">New Certificate</a>
    <a href="?page=logout" class="mlink text-2xl font-medium text-red-400 no-underline">Logout</a>
    <?php else: ?>
    <div class="w-16 h-px bg-white/10 my-2"></div>
    <a href="?page=login" class="mlink text-2xl font-medium text-bl hover:text-white no-underline">Officer Login</a>
    <?php endif; ?>
</div>
</div>


<?php
// ═══════════════════════════════════════════════════
// PAGE: HOME
// ═══════════════════════════════════════════════════
if ($page === 'home'):
?>
<section class="relative min-h-screen flex items-center bg-g overflow-hidden">
<div class="hg top-1/4 -left-40"></div>
<div class="max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-20 w-full">
<div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
<div>
    <span class="sl inline-flex items-center gap-2 mb-6 au"><span class="w-2 h-2 rounded-full bg-bl animate-pulse"></span>Electrical & Electronics Engineering</span>
    <h1 class="text-5xl md:text-6xl lg:text-[76px] font-medium tracking-tight leading-[1.05] mb-6 au d1">Engineering<br><span class="text-bl">Excellence</span><br>in Every Wire</h1>
    <p class="text-lg text-neutral-400 leading-relaxed max-w-lg mb-10 au d2">From electrical installation to industrial automation and product supplies — KPSwamy TecZ delivers comprehensive solutions across India.</p>
    <div class="flex flex-wrap gap-4 mb-12 au d3">
        <a href="?page=home#contact" class="bp">Contact Us <i data-lucide="arrow-right" class="w-5 h-5"></i></a>
    </div>
    <div class="flex gap-8 au d4">
        <div><div class="text-3xl font-semibold">30+</div><div class="text-xs text-neutral-500 mt-1">Years Experience</div></div>
        <div class="w-px bg-white/10"></div>
        <div><div class="text-3xl font-semibold">500+</div><div class="text-xs text-neutral-500 mt-1">Projects Done</div></div>
        <div class="w-px bg-white/10"></div>
        <div><div class="text-3xl font-semibold">200+</div><div class="text-xs text-neutral-500 mt-1">Happy Clients</div></div>
    </div>
</div>
<div class="relative au d3 hidden lg:block">
    <div class="rounded-3xl overflow-hidden border border-white/10 shadow-2xl">
        <img src="https://picsum.photos/seed/kps-eng-hero/700/800.jpg" alt="Engineering" class="w-full h-[500px] object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-bd via-transparent to-transparent"></div>
    </div>
    <div class="absolute -left-8 top-1/4 gl rounded-2xl p-4 border border-white/10" style="animation:fl 6s ease-in-out infinite">
        <div class="flex items-center gap-3"><div class="w-11 h-11 rounded-xl bg-bl/20 flex items-center justify-center"><i data-lucide="zap" class="w-5 h-5 text-bl"></i></div><div><div class="text-sm font-semibold">Electrical</div><div class="text-xs text-neutral-400">HT/LT Systems</div></div></div>
    </div>
</div>
</div>
</div>
</section>

<section class="np py-5 border-y border-white/5 overflow-hidden"><div class="flex" style="animation:mq 25s linear infinite;white-space:nowrap">
<?php $mi=array('Electrical Installation','Panel Building','PLC Automation','Cable Supply','Solar Systems','Motor Rewinding','Testing','LED Lighting','VFD Drives','Transformers');
for($m=0;$m<2;$m++) foreach($mi as $x): ?><span class="mx-5 text-sm font-medium text-neutral-500 uppercase tracking-wider"><?= $x ?></span><span class="mx-2 text-bl/30">✦</span><?php endforeach; ?>
</div></section>

<section id="services" class="py-24 lg:py-32 bg-g relative">
<div class="hg top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-20"></div>
<div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
<div class="text-center max-w-2xl mx-auto mb-16 rv">
    <span class="sl">Our Services</span>
    <h2 class="text-3xl md:text-5xl font-medium tracking-tight mt-4 mb-6">Electrical & Electronics<br><span class="text-bl">Engineering Services</span></h2>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
<?php foreach($services as $i => $s): ?>
<div class="cd p-7 rv" style="transition-delay:<?= $i * 0.08 ?>s">
    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center mb-5"><i data-lucide="<?= $s['i'] ?>" class="w-6 h-6 text-bl"></i></div>
    <h3 class="text-lg font-semibold tracking-tight mb-2"><?= $s['t'] ?></h3>
    <p class="text-neutral-400 text-sm leading-relaxed mb-4"><?= $s['d'] ?></p>
    <ul class="space-y-1.5"><?php foreach($s['f'] as $ft): ?><li class="flex items-center gap-2 text-xs text-neutral-300"><i data-lucide="check" class="w-3 h-3 text-bl flex-shrink-0"></i><?= $ft ?></li><?php endforeach; ?></ul>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<section id="contact" class="py-24 lg:py-32">
<div class="max-w-7xl mx-auto px-6 lg:px-8">
<div class="text-center max-w-2xl mx-auto mb-16 rv"><span class="sl">Contact Us</span><h2 class="text-3xl md:text-5xl font-medium tracking-tight mt-4">Get In <span class="text-bl">Touch</span></h2></div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 rv">
    <div class="cd p-6 text-center"><div class="w-12 h-12 rounded-xl bg-bl/10 flex items-center justify-center mx-auto mb-4"><i data-lucide="map-pin" class="w-6 h-6 text-bl"></i></div><h4 class="font-semibold mb-2">Address</h4><p class="text-neutral-400 text-sm"><?= $C['address'] ?></p></div>
    <div class="cd p-6 text-center"><div class="w-12 h-12 rounded-xl bg-bl/10 flex items-center justify-center mx-auto mb-4"><i data-lucide="phone" class="w-6 h-6 text-bl"></i></div><h4 class="font-semibold mb-2">Phone</h4><p class="text-neutral-400 text-sm"><?= $C['phone'] ?></p></div>
    <div class="cd p-6 text-center"><div class="w-12 h-12 rounded-xl bg-bl/10 flex items-center justify-center mx-auto mb-4"><i data-lucide="mail" class="w-6 h-6 text-bl"></i></div><h4 class="font-semibold mb-2">Email</h4><p class="text-neutral-400 text-sm"><?= $C['email'] ?></p></div>
</div>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: LOGIN
// ═══════════════════════════════════════════════════
elseif ($page === 'login'):
    if (isLoggedIn()) { header('Location: ?page=dashboard'); exit; }
?>
<section class="min-h-screen flex items-center justify-center bg-g px-6 pt-20 pb-12">
<div class="w-full max-w-md">
<div class="text-center mb-10">
    <div class="w-16 h-16 rounded-2xl bg-bl flex items-center justify-center mx-auto mb-4"><span class="text-black font-bold text-2xl">KP</span></div>
    <h1 class="text-3xl font-medium tracking-tight">Officer <span class="text-bl">Login</span></h1>
    <p class="text-neutral-400 text-sm mt-2">Access invoice & certificate tools</p>
</div>
<form method="POST" action="?page=login" class="cd p-8">
    <input type="hidden" name="action" value="login">
    <div class="mb-5">
        <label class="block text-sm font-medium text-neutral-300 mb-2">Username</label>
        <div class="relative"><div class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-500"><i data-lucide="user" class="w-4 h-4"></i></div>
        <input type="text" name="username" class="fi" style="padding-left:2.5rem" placeholder="Enter username" required></div>
    </div>
    <div class="mb-5">
        <label class="block text-sm font-medium text-neutral-300 mb-2">Password</label>
        <div class="relative"><div class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-500"><i data-lucide="lock" class="w-4 h-4"></i></div>
        <input type="password" name="password" class="fi" style="padding-left:2.5rem" placeholder="Enter password" required></div>
    </div>
    <div class="mb-6">
        <label class="block text-sm font-medium text-neutral-300 mb-2">Security Captcha</label>
        <div class="captcha-box">
            <div class="flex items-center gap-3 mb-3">
                <i data-lucide="shield" class="w-4 h-4 text-bl"></i>
                <span class="text-sm font-semibold text-bl">Solve:</span>
                <span class="text-2xl font-bold tracking-wider text-white"><?= $capQuestion ?></span>
            </div>
            <input type="number" name="captcha" class="fi" style="text-align:center;font-size:1.1rem;font-weight:600" placeholder="Your answer" required>
        </div>
    </div>
    <button type="submit" class="bp w-full justify-center" style="animation:pg 3s ease-in-out infinite">
        <i data-lucide="log-in" class="w-5 h-5"></i> Sign In
    </button>
</form>
<p class="text-center text-xs text-neutral-600 mt-6">Default: admin / kpswamy@2024</p>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: DASHBOARD
// ═══════════════════════════════════════════════════
elseif ($page === 'dashboard'):
    $totalInv = count($invoices);
    $totalCert = count($certificates);
    $totalAmt = 0;
    foreach ($invoices as $iv) {
        $igst = $iv['taxable'] * $iv['igst_rate'] / 100;
        $totalAmt = $totalAmt + $iv['taxable'] + $igst;
    }
?>
<section class="pt-28 pb-24">
<div class="max-w-6xl mx-auto px-6 lg:px-8">
<div class="mb-10">
    <span class="sl">Officer Dashboard</span>
    <h2 class="text-3xl md:text-4xl font-medium tracking-tight mt-4">Welcome, <span class="text-bl"><?= esc($_SESSION['officer']) ?></span></h2>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
    <a href="?page=inv-new" class="cd p-6 no-underline group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-bl/10 flex items-center justify-center group-hover:bg-bl transition-colors"><i data-lucide="file-plus" class="w-6 h-6 text-bl group-hover:text-black transition-colors"></i></div>
            <div><div class="text-2xl font-bold text-white"><?= $totalInv ?></div><div class="text-xs text-neutral-500">Invoices</div></div>
        </div>
    </a>
    <a href="?page=cert-new" class="cd p-6 no-underline group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-bl/10 flex items-center justify-center group-hover:bg-bl transition-colors"><i data-lucide="award" class="w-6 h-6 text-bl group-hover:text-black transition-colors"></i></div>
            <div><div class="text-2xl font-bold text-white"><?= $totalCert ?></div><div class="text-xs text-neutral-500">Certificates</div></div>
        </div>
    </a>
    <div class="cd p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center"><i data-lucide="indian-rupee" class="w-6 h-6 text-green-400"></i></div>
            <div><div class="text-2xl font-bold text-white">&#8377; <?= number_format($totalAmt, 0) ?></div><div class="text-xs text-neutral-500">Total Value</div></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-10">
    <a href="?page=inv-new" class="cd p-8 no-underline group">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-bl/10 flex items-center justify-center flex-shrink-0 group-hover:bg-bl transition-colors"><i data-lucide="file-text" class="w-8 h-8 text-bl group-hover:text-black transition-colors"></i></div>
            <div><h3 class="text-lg font-semibold text-white mb-1">Create New Invoice</h3><p class="text-neutral-400 text-sm">Generate professional tax invoice</p></div>
        </div>
    </a>
    <a href="?page=cert-new" class="cd p-8 no-underline group">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-bl/10 flex items-center justify-center flex-shrink-0 group-hover:bg-bl transition-colors"><i data-lucide="award" class="w-8 h-8 text-bl group-hover:text-black transition-colors"></i></div>
            <div><h3 class="text-lg font-semibold text-white mb-1">Generate Certificate</h3><p class="text-neutral-400 text-sm">Create service certificates</p></div>
        </div>
    </a>
</div>

<?php if (count($invoices) > 0): ?>
<h3 class="text-lg font-semibold mb-5">Recent Invoices</h3>
<div class="cd overflow-hidden">
<table class="w-full text-sm">
<thead><tr class="text-left text-neutral-400 text-xs uppercase tracking-wider border-b border-white/10">
<th class="p-4">Invoice #</th><th class="p-4">Date</th><th class="p-4">Client</th><th class="p-4 text-right">Amount</th><th class="p-4 text-center">Action</th>
</tr></thead><tbody>
<?php
 $recent = array_reverse($invoices);
 $shown = 0;
foreach ($recent as $iv) {
    if ($shown >= 5) break;
    $igst = $iv['taxable'] * $iv['igst_rate'] / 100;
    $grand = $iv['taxable'] + $igst;
    $shown++;
?>
<tr class="border-b border-white/5 hover:bg-white/3">
    <td class="p-4 font-mono text-bl font-semibold"><?= $iv['id'] ?></td>
    <td class="p-4 text-neutral-300"><?= $iv['date'] ?></td>
    <td class="p-4 text-neutral-300" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $iv['bill_name'] ?></td>
    <td class="p-4 text-right font-semibold">&#8377; <?= number_format($grand, 2) ?></td>
    <td class="p-4 text-center"><a href="?page=inv-view&id=<?= $iv['id'] ?>" class="p-2 rounded-lg bg-white/5 hover:bg-bl/20 hover:text-bl inline-block"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
</tr>
<?php } ?>
</tbody></table>
</div>
<?php endif; ?>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: INVOICE NEW
// ═══════════════════════════════════════════════════
elseif ($page === 'inv-new'):
?>
<section class="np pt-28 pb-24">
<div class="max-w-5xl mx-auto px-6 lg:px-8">
<div class="flex items-center gap-3 mb-10">
    <a href="?page=dashboard" class="bs sm" style="padding:8px 16px"><i data-lucide="arrow-left" class="w-4 h-4"></i></a>
    <div><span class="sl">Invoice Generator</span><h2 class="text-3xl font-medium tracking-tight mt-1">Create New <span class="text-bl">Invoice</span></h2></div>
</div>

<form method="POST" action="?page=inv-new">
<input type="hidden" name="action" value="save_inv">

<!-- Invoice Date -->
<div class="cd p-8 mb-6">
    <h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="calendar" class="w-5 h-5 text-bl"></i> Invoice Details</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-neutral-300 mb-2">Invoice Date *</label>
            <input type="date" name="inv_date" class="fi" value="<?= date('Y-m-d') ?>" required>
        </div>
    </div>
</div>

<!-- Bill To -->
<div class="cd p-8 mb-6">
    <h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-bl"></i> BILL TO</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Name *</label><input type="text" name="bill_name" class="fi" placeholder="e.g. Mercantile Marine Department (MMD)" required></div>
        <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Address *</label><textarea name="bill_addr" rows="2" class="fi" style="resize:none" placeholder="Full address" required></textarea></div>
        <div><label class="block text-sm font-medium text-neutral-300 mb-2">GSTIN</label><input type="text" name="bill_gst" class="fi" placeholder="19CALC01217G1DC"></div>
        <div><label class="block text-sm font-medium text-neutral-300 mb-2">State</label><input type="text" name="bill_state" class="fi" placeholder="West Bengal"></div>
    </div>
</div>

<!-- Project Details -->
<div class="cd p-8 mb-6">
    <h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="folder-open" class="w-5 h-5 text-bl"></i> PROJECT DETAILS</h3>
    <div class="grid grid-cols-1 gap-5">
        <div><label class="block text-sm font-medium text-neutral-300 mb-2">W.O. Ref</label><input type="text" name="wo_ref" class="fi" placeholder="e.g. Work Order dated 11-May-2026"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div><label class="block text-sm font-medium text-neutral-300 mb-2">SAC Code</label><input type="text" name="sac" class="fi" value="9954"></div>
            <div><label class="block text-sm font-medium text-neutral-300 mb-2">SAC Description</label><input type="text" class="fi" value="Electrical Installation" disabled style="opacity:0.6"></div>
        </div>
        <div><label class="block text-sm font-medium text-neutral-300 mb-2">Project Name *</label><textarea name="project" rows="2" class="fi" style="resize:none" placeholder="e.g. Supply & Installation of Electrical Points at Examination Halls, MMD Kolkata" required></textarea></div>
    </div>
</div>

<!-- Items -->
<div class="cd p-8 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold flex items-center gap-2"><i data-lucide="list" class="w-5 h-5 text-bl"></i> DESCRIPTION OF SERVICES</h3>
        <button type="button" onclick="addRow()" class="bp sm"><i data-lucide="plus" class="w-4 h-4"></i> Add Row</button>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
    <thead><tr class="text-left text-neutral-400 text-xs uppercase tracking-wider border-b border-white/10">
        <th class="pb-3 pr-3" style="width:50px">SL</th>
        <th class="pb-3 pr-3">DESCRIPTION OF SERVICES</th>
        <th class="pb-3 pr-3" style="width:120px">QTY</th>
        <th class="pb-3" style="width:50px"></th>
    </tr></thead>
    <tbody id="rows">
    <tr class="ir border-b border-white/5">
        <td class="py-3 pr-3 text-center text-neutral-500 sl-no">01</td>
        <td class="py-3 pr-3"><textarea name="desc[]" rows="3" class="fi" style="padding:8px 16px;font-size:13px;resize:none" placeholder="Detailed description of service..." required></textarea></td>
        <td class="py-3 pr-3"><input type="text" name="qty[]" class="fi" style="padding:8px 16px;font-size:13px" value="190 Nos" placeholder="190 Nos"></td>
        <td class="py-3 text-center"><button type="button" onclick="delRow(this)" class="text-neutral-500 hover:text-red-400"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td>
    </tr>
    </tbody>
    </table>
    </div>
</div>

<!-- Tax -->
<div class="cd p-8 mb-6">
    <h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="calculator" class="w-5 h-5 text-bl"></i> TAX DETAILS</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label class="block text-sm font-medium text-neutral-300 mb-2">Taxable Value (&#8377;) *</label>
            <input type="number" name="taxable" class="fi" style="text-align:right;font-size:1.1rem;font-weight:600" value="0" min="0" step="0.01" id="taxableInput" required oninput="calcTotals()">
        </div>
        <div>
            <label class="block text-sm font-medium text-neutral-300 mb-2">IGST Rate (%)</label>
            <input type="number" name="igst_rate" class="fi" style="text-align:right" value="18" min="0" max="100" step="0.5" oninput="calcTotals()">
        </div>
        <div>
            <label class="block text-sm font-medium text-neutral-300 mb-2">Grand Total (&#8377;)</label>
            <div class="fi" style="background:rgba(217,255,80,.1);border-color:rgba(217,255,80,.3);text-align:right;font-size:1.1rem;font-weight:700;color:#D9FF50" id="grandTotalDisplay">&#8377; 0.00</div>
        </div>
    </div>
    <div class="mt-5">
        <label class="block text-sm font-medium text-neutral-300 mb-2">Amount in Words *</label>
        <input type="text" name="words" class="fi" id="wordsInput" placeholder="e.g. INR Three Lakh Thirty-Two Thousand Five Hundred Only" required>
    </div>
</div>

<div class="flex gap-4 justify-end">
    <a href="?page=dashboard" class="bs">Cancel</a>
    <button type="submit" class="bp"><i data-lucide="save" class="w-5 h-5"></i> Save Invoice</button>
</div>
</form>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: INVOICE LIST
// ═══════════════════════════════════════════════════
elseif ($page === 'inv-list'):
?>
<section class="np pt-28 pb-24">
<div class="max-w-6xl mx-auto px-6 lg:px-8">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-10 gap-4">
    <div class="flex items-center gap-3">
        <a href="?page=dashboard" class="bs sm" style="padding:8px 16px"><i data-lucide="arrow-left" class="w-4 h-4"></i></a>
        <div><span class="sl">Invoice Records</span><h2 class="text-3xl font-medium tracking-tight mt-1">All <span class="text-bl">Invoices</span></h2></div>
    </div>
    <a href="?page=inv-new" class="bp sm"><i data-lucide="plus" class="w-4 h-4"></i> New Invoice</a>
</div>

<?php if (count($invoices) === 0): ?>
<div class="cd p-16 text-center">
    <i data-lucide="file-text" class="w-16 h-16 text-neutral-600 mx-auto mb-4"></i>
    <h3 class="text-xl font-semibold text-neutral-400 mb-2">No Invoices Yet</h3>
    <a href="?page=inv-new" class="bp sm">Create Invoice</a>
</div>
<?php else: ?>
<div class="cd overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
<thead><tr class="text-left text-neutral-400 text-xs uppercase tracking-wider border-b border-white/10">
<th class="p-4">Invoice #</th><th class="p-4">Date</th><th class="p-4">Client</th><th class="p-4">Items</th><th class="p-4 text-right">Grand Total</th><th class="p-4 text-center">Actions</th>
</tr></thead><tbody>
<?php
 $revInvs = array_reverse($invoices);
foreach ($revInvs as $iv):
    $igst = $iv['taxable'] * $iv['igst_rate'] / 100;
    $grand = $iv['taxable'] + $igst;
?>
<tr class="border-b border-white/5 hover:bg-white/3">
    <td class="p-4 font-mono text-bl font-semibold"><?= $iv['id'] ?></td>
    <td class="p-4 text-neutral-300"><?= $iv['date'] ?></td>
    <td class="p-4 text-neutral-300" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $iv['bill_name'] ?></td>
    <td class="p-4 text-neutral-400"><?= count($iv['items']) ?></td>
    <td class="p-4 text-right font-semibold">&#8377; <?= number_format($grand, 2) ?></td>
    <td class="p-4 text-center">
        <div class="flex items-center justify-center gap-2">
            <a href="?page=inv-view&id=<?= $iv['id'] ?>" class="p-2 rounded-lg bg-white/5 hover:bg-bl/20 hover:text-bl inline-block"><i data-lucide="eye" class="w-4 h-4"></i></a>
            <form method="POST" action="?page=inv-list" onsubmit="return confirm('Delete this invoice?')">
                <input type="hidden" name="action" value="del_inv">
                <input type="hidden" name="id" value="<?= $iv['id'] ?>">
                <button type="submit" class="p-2 rounded-lg bg-white/5 hover:bg-red-500/20 hover:text-red-400"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div>
<?php endif; ?>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: INVOICE VIEW — EXACT PDF MODEL
// ═══════════════════════════════════════════════════
elseif ($page === 'inv-view' && $currentInv !== null):
    $iv = $currentInv;
    $igst = $iv['taxable'] * $iv['igst_rate'] / 100;
    $grand = $iv['taxable'] + $igst;
?>
<div class="np fixed top-20 left-6 z-40"><a href="?page=inv-list" class="bs sm" style="padding:8px 16px"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a></div>
<div class="np fixed top-20 right-6 z-40"><button onclick="window.print()" class="bp sm" style="padding:8px 16px"><i data-lucide="printer" class="w-4 h-4"></i> Print</button></div>

<div class="pa max-w-[210mm] mx-auto my-8 lg:my-12" style="background:#fff;border-radius:4px;box-shadow:0 25px 60px rgba(0,0,0,.5)">

    <!-- HEADER -->
    <div class="lp-head px-10 py-6" style="background:<?= $LP['navy'] ?>">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="lp-logo-bg w-14 h-14 rounded-xl flex items-center justify-center" style="background:<?= $LP['goldL'] ?>">
                    <span style="color:<?= $LP['navy'] ?>" class="font-bold text-xl font-serif">KP</span>
                </div>
                <div>
                    <div class="lp-gold text-xl font-bold tracking-tight font-serif" style="color:<?= $LP['goldL'] ?>"><?= $C['name'] ?></div>
                </div>
            </div>
            <div class="text-right text-xs" style="color:rgba(255,255,255,.55)">
                <div><?= $C['address'] ?></div>
                <div style="margin-top:4px">Phone: <?= $C['phone'] ?> | Email: <?= $C['email'] ?></div>
                <div style="margin-top:4px">GSTIN: <?= $C['gst'] ?></div>
            </div>
        </div>
    </div>

    <!-- INVOICE TITLE -->
    <div class="px-10 py-5 flex justify-between items-end" style="border-bottom:2px solid <?= $LP['navy'] ?>">
        <div class="lp-cert-title text-3xl font-bold font-serif" style="color:<?= $LP['navy'] ?>">INVOICE</div>
        <div class="text-right text-sm" style="color:<?= $LP['body'] ?>">
            <div><strong>Invoice No:</strong> <span class="font-mono font-semibold lp-accent" style="color:<?= $LP['navy'] ?>"><?= $iv['id'] ?></span></div>
            <div style="margin-top:4px"><strong>Date:</strong> <?= $iv['date'] ?></div>
        </div>
    </div>

    <!-- BILL TO + PROJECT -->
    <div class="px-10 py-5" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;border-bottom:1px solid #e0e0e0">
        <div>
            <div class="lp-accent text-xs uppercase font-bold mb-3" style="color:<?= $LP['navy'] ?>;letter-spacing:.1em">BILL TO</div>
            <table class="text-sm" style="color:<?= $LP['body'] ?>">
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>;width:65px;vertical-align:top">Name:</td><td style="padding:2px 0"><?= $iv['bill_name'] ?></td></tr>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>;vertical-align:top">Address:</td><td style="padding:2px 0;white-space:pre-line"><?= $iv['bill_addr'] ?></td></tr>
                <?php if ($iv['bill_gst'] !== ''): ?>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>">GSTIN:</td><td style="padding:2px 0"><?= $iv['bill_gst'] ?></td></tr>
                <?php endif; ?>
                <?php if ($iv['bill_state'] !== ''): ?>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>">State:</td><td style="padding:2px 0"><?= $iv['bill_state'] ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <div>
            <div class="lp-accent text-xs uppercase font-bold mb-3" style="color:<?= $LP['navy'] ?>;letter-spacing:.1em">PROJECT DETAILS</div>
            <table class="text-sm" style="color:<?= $LP['body'] ?>">
                <?php if ($iv['wo_ref'] !== ''): ?>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>;width:75px;vertical-align:top">W.O. Ref:</td><td style="padding:2px 0"><?= $iv['wo_ref'] ?></td></tr>
                <?php endif; ?>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>">SAC Code:</td><td style="padding:2px 0"><?= $iv['sac'] ?> (Electrical Installation)</td></tr>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>;vertical-align:top">Project:</td><td style="padding:2px 0"><?= $iv['project'] ?></td></tr>
            </table>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="px-10 py-4">
        <table class="lp-table w-full text-sm" style="border-collapse:collapse">
        <thead><tr>
            <th class="lp-bth text-left p-3 text-xs uppercase font-bold" style="background:<?= $LP['blueBG'] ?>;color:<?= $LP['navy'] ?>;border:1px solid <?= $LP['blueBdr'] ?>;border-bottom:2px solid <?= $LP['navy'] ?>;width:50px">SL</th>
            <th class="lp-bth text-left p-3 text-xs uppercase font-bold" style="background:<?= $LP['blueBG'] ?>;color:<?= $LP['navy'] ?>;border:1px solid <?= $LP['blueBdr'] ?>;border-bottom:2px solid <?= $LP['navy'] ?>">DESCRIPTION OF SERVICES</th>
            <th class="lp-bth text-left p-3 text-xs uppercase font-bold" style="background:<?= $LP['blueBG'] ?>;color:<?= $LP['navy'] ?>;border:1px solid <?= $LP['blueBdr'] ?>;border-bottom:2px solid <?= $LP['navy'] ?>;width:100px">QTY</th>
        </tr></thead>
        <tbody>
        <?php foreach ($iv['items'] as $idx => $item): ?>
        <tr>
            <td class="p-3 text-center font-semibold" style="border:1px solid #e0e0e0;color:<?= $LP['navy'] ?>"><?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?></td>
            <td class="p-3" style="border:1px solid #e0e0e0;color:<?= $LP['body'] ?>;line-height:1.6"><?= nl2br($item['desc']) ?></td>
            <td class="p-3 font-medium" style="border:1px solid #e0e0e0;color:<?= $LP['body'] ?>"><?= $item['qty'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>

    <!-- TOTALS -->
    <div class="px-10 pb-4">
        <table style="border-collapse:collapse;margin-left:auto;min-width:320px">
        <tr style="border-bottom:1px solid #e0e0e0"><td style="padding:10px;color:<?= $LP['muted'] ?>">Taxable Value</td><td style="padding:10px;text-align:right;font-weight:500;color:<?= $LP['body'] ?>">&#8377; <?= number_format($iv['taxable'], 2) ?></td></tr>
        <tr style="border-bottom:1px solid #e0e0e0"><td style="padding:10px;color:<?= $LP['muted'] ?>">IGST @ <?= $iv['igst_rate'] ?>%</td><td style="padding:10px;text-align:right;color:<?= $LP['body'] ?>">&#8377; <?= number_format($igst, 2) ?></td></tr>
        <tr class="lp-total-row"><td style="padding:12px;font-size:15px;font-weight:700;background:<?= $LP['goldBG'] ?>;color:<?= $LP['gold'] ?>;border-radius:4px 0 0 4px">GRAND TOTAL</td><td style="padding:12px;text-align:right;font-size:15px;font-weight:700;background:<?= $LP['goldBG'] ?>;color:<?= $LP['gold'] ?>;border-radius:0 4px 4px 0">&#8377; <?= number_format($grand, 2) ?></td></tr>
        </table>
    </div>

    <!-- WORDS -->
    <div class="px-10 pb-6">
        <div class="text-sm" style="color:<?= $LP['body'] ?>">
            <strong>Amount in Words:</strong> <span class="lp-gold-text" style="color:<?= $LP['gold'] ?>"><?= $iv['words'] ?></span>
        </div>
    </div>

    <!-- BANK + SIGNATORY -->
    <div class="px-10 py-5" style="display:grid;grid-template-columns:1fr 1fr;gap:32px;border-top:1px solid #e0e0e0">
        <div>
            <div class="lp-accent text-xs uppercase font-bold mb-3" style="color:<?= $LP['navy'] ?>;letter-spacing:.1em">BANK DETAILS (NEFT/RTGS)</div>
            <table class="text-sm" style="color:<?= $LP['body'] ?>">
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>;width:80px">Bank:</td><td style="padding:2px 0"><?= $C['bank'] ?></td></tr>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>">A/c Name:</td><td style="padding:2px 0"><?= $C['acname'] ?></td></tr>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>">A/c No:</td><td style="padding:2px 0"><?= $C['acno'] ?></td></tr>
                <tr><td style="padding:2px 0;font-weight:600;color:<?= $LP['muted'] ?>">IFSC:</td><td style="padding:2px 0"><?= $C['ifsc'] ?></td></tr>
            </table>
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;justify-content:flex-end">
            <div class="lp-accent text-sm font-bold uppercase" style="color:<?= $LP['navy'] ?>;letter-spacing:.08em;margin-bottom:8px">KPSWAMY TECZ</div>
            <div class="lp-stamp" style="width:176px;height:80px;border:1px dashed <?= $LP['gold'] ?>;border-radius:8px;margin-bottom:4px"></div>
            <div style="font-size:14px;font-weight:600;color:<?= $LP['dark'] ?>">For <?= $C['name'] ?></div>
            <div style="font-size:14px;font-weight:500;color:<?= $LP['body'] ?>">Authorized Signatory</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="lp-footer px-10 py-3 text-center text-xs" style="background:<?= $LP['navy'] ?>;color:rgba(201,168,76,.6)"><?= $C['name'] ?> | <?= $C['address'] ?> | Phone: <?= $C['phone'] ?> | Email: <?= $C['email'] ?> | GSTIN: <?= $C['gst'] ?></div>
</div>


<?php
// ═══════════════════════════════════════════════════
// PAGE: CERT NEW
// ═══════════════════════════════════════════════════
elseif ($page === 'cert-new'):
?>
<section class="np pt-28 pb-24">
<div class="max-w-4xl mx-auto px-6 lg:px-8">
<div class="flex items-center gap-3 mb-10">
    <a href="?page=dashboard" class="bs sm" style="padding:8px 16px"><i data-lucide="arrow-left" class="w-4 h-4"></i></a>
    <div><span class="sl">Certificate Generator</span><h2 class="text-3xl font-medium tracking-tight mt-1">Generate <span class="text-bl">Certificate</span></h2></div>
</div>
<form method="POST" action="?page=cert-new">
<input type="hidden" name="action" value="save_cert">
<div class="cd p-8 mb-6"><h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="file-badge" class="w-5 h-5 text-bl"></i> Certificate Details</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div><label class="block text-sm font-medium text-neutral-300 mb-2">Date *</label><input type="date" name="cd" class="fi" value="<?= date('Y-m-d') ?>" required></div>
    <div><label class="block text-sm font-medium text-neutral-300 mb-2">Validity</label><input type="text" name="cv" class="fi" placeholder="e.g. 1 Year"></div>
</div></div>
<div class="cd p-8 mb-6"><h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-bl"></i> Client</h3>
<div class="grid grid-cols-1 gap-5">
    <div><label class="block text-sm font-medium text-neutral-300 mb-2">Client Name *</label><input type="text" name="cc" class="fi" required></div>
    <div><label class="block text-sm font-medium text-neutral-300 mb-2">Address</label><textarea name="cadr" rows="2" class="fi" style="resize:none"></textarea></div>
</div></div>
<div class="cd p-8 mb-6"><h3 class="text-lg font-semibold mb-5 flex items-center gap-2"><i data-lucide="wrench" class="w-5 h-5 text-bl"></i> Equipment / Service</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Equipment *</label><input type="text" name="ce" class="fi" required></div>
    <div><label class="block text-sm font-medium text-neutral-300 mb-2">Serial / Model</label><input type="text" name="cs" class="fi"></div>
    <div><label class="block text-sm font-medium text-neutral-300 mb-2">Location</label><input type="text" name="cl" class="fi"></div>
    <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Scope of Work *</label><textarea name="csc" rows="3" class="fi" style="resize:none" required></textarea></div>
    <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Standards</label><input type="text" name="cst" class="fi" placeholder="e.g. IS 2026"></div>
    <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Test Results *</label><textarea name="cr" rows="3" class="fi" style="resize:none" required></textarea></div>
    <div class="sm:col-span-2"><label class="block text-sm font-medium text-neutral-300 mb-2">Remarks</label><textarea name="crm" rows="2" class="fi" style="resize:none"></textarea></div>
</div></div>
<div class="flex gap-4 justify-end"><a href="?page=dashboard" class="bs">Cancel</a><button type="submit" class="bp"><i data-lucide="award" class="w-5 h-5"></i> Generate</button></div>
</form>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: CERT LIST
// ═══════════════════════════════════════════════════
elseif ($page === 'cert-list'):
?>
<section class="np pt-28 pb-24">
<div class="max-w-6xl mx-auto px-6 lg:px-8">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-10 gap-4">
    <div class="flex items-center gap-3"><a href="?page=dashboard" class="bs sm" style="padding:8px 16px"><i data-lucide="arrow-left" class="w-4 h-4"></i></a><div><span class="sl">Certificates</span><h2 class="text-3xl font-medium tracking-tight mt-1">All <span class="text-bl">Certificates</span></h2></div></div>
    <a href="?page=cert-new" class="bp sm"><i data-lucide="plus" class="w-4 h-4"></i> New Certificate</a>
</div>
<?php if (count($certificates) === 0): ?>
<div class="cd p-16 text-center"><i data-lucide="award" class="w-16 h-16 text-neutral-600 mx-auto mb-4"></i><h3 class="text-xl font-semibold text-neutral-400 mb-2">No Certificates Yet</h3><a href="?page=cert-new" class="bp sm">Create Certificate</a></div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
<?php foreach (array_reverse($certificates) as $ct): ?>
<div class="cd p-6">
    <div class="flex items-start justify-between mb-2"><span class="font-mono text-bl text-xs font-semibold"><?= $ct['id'] ?></span><span class="text-xs text-neutral-500"><?= $ct['date'] ?></span></div>
    <h3 class="font-semibold text-sm truncate"><?= $ct['equip'] ?></h3>
    <p class="text-neutral-500 text-xs mb-3"><?= $ct['client'] ?></p>
    <div class="flex gap-2">
        <a href="?page=cert-view&id=<?= $ct['id'] ?>" class="bp sm" style="padding:6px 16px;font-size:12px;flex:1;justify-content:center">View</a>
        <form method="POST" action="?page=cert-list" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="del_cert"><input type="hidden" name="id" value="<?= $ct['id'] ?>"><button type="submit" class="bs sm" style="padding:6px 12px;font-size:12px;background:#7f1d1d;color:#fecaca;border:1px solid #ef444433"><i data-lucide="trash-2" class="w-3 h-3"></i></button></form>
    </div>
</div>
<?php endforeach; ?>
</div><?php endif; ?>
</div>
</section>


<?php
// ═══════════════════════════════════════════════════
// PAGE: CERT VIEW
// ═══════════════════════════════════════════════════
elseif ($page === 'cert-view' && $currentCert !== null):
    $ct = $currentCert;
?>
<div class="np fixed top-20 left-6 z-40"><a href="?page=cert-list" class="bs sm" style="padding:8px 16px"><i data-lucide="arrow-left" class="w-4 h-4"></i></a></div>
<div class="np fixed top-20 right-6 z-40"><button onclick="window.print()" class="bp sm" style="padding:8px 16px"><i data-lucide="printer" class="w-4 h-4"></i> Print</button></div>

<div class="pa max-w-[210mm] mx-auto my-8 lg:my-12" style="background:#fff;border-radius:4px;box-shadow:0 25px 60px rgba(0,0,0,.5)">
<div class="lp-head px-10 py-6" style="background:<?= $LP['navy'] ?>">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4"><div class="lp-logo-bg w-14 h-14 rounded-xl flex items-center justify-center" style="background:<?= $LP['goldL'] ?>"><span style="color:<?= $LP['navy'] ?>" class="font-bold text-xl font-serif">KP</span></div><div><div class="lp-gold text-xl font-bold font-serif" style="color:<?= $LP['goldL'] ?>"><?= $C['name'] ?></div></div></div>
        <div class="text-right text-xs" style="color:rgba(255,255,255,.55)"><div><?= $C['address'] ?></div><div style="margin-top:4px">Phone: <?= $C['phone'] ?></div><div>GSTIN: <?= $C['gst'] ?></div></div>
    </div>
</div>
<div class="lp-bar px-10 py-3 flex justify-between text-xs" style="background:<?= $LP['blueBG'] ?>;border-bottom:1px solid <?= $LP['blueBdr'] ?>"><div><?= $C['address'] ?> | Ph: <?= $C['phone'] ?></div><div class="text-right"><?= $C['email'] ?></div></div>
<div class="px-10 pt-10 pb-2 text-center" style="border-bottom:2px solid <?= $LP['navy'] ?>"><div class="lp-cert-title text-3xl font-bold font-serif" style="color:<?= $LP['navy'] ?>;letter-spacing:.05em">SERVICE CERTIFICATE</div><div class="text-sm mt-2" style="color:#888">No: <span class="font-mono font-semibold lp-accent" style="color:<?= $LP['navy'] ?>"><?= $ct['id'] ?></span> | Date: <?= $ct['date'] ?></div></div>
<div class="px-10 py-8 text-sm leading-relaxed" style="color:<?= $LP['body'] ?>">
    <p style="margin-bottom:24px">This is to certify that the following service / work has been successfully carried out by <strong class="lp-accent" style="color:<?= $LP['navy'] ?>"><?= $C['name'] ?></strong> at the premises of:</p>
    <div class="lp-box-blue rounded-lg" style="padding:20px;background:<?= $LP['blueBG'] ?>;border:1px solid <?= $LP['blueBdr'] ?>;margin-bottom:24px"><table class="text-sm" style="color:<?= $LP['body'] ?>"><tr><td style="padding:4px 0;font-weight:600;color:<?= $LP['navy'] ?>;width:140px">Client:</td><td style="padding:4px 0"><?= $ct['client'] ?></td></tr><?php if ($ct['addr'] !== ''): ?><tr><td style="padding:4px 0;font-weight:600;color:<?= $LP['navy'] ?>">Address:</td><td style="padding:4px 0"><?= nl2br($ct['addr']) ?></td></tr><?php endif; ?><tr><td style="padding:4px 0;font-weight:600;color:<?= $LP['navy'] ?>">Location:</td><td style="padding:4px 0"><?= $ct['loc'] !== '' ? $ct['loc'] : '—' ?></td></tr></table></div>
    <div class="lp-box-gold rounded-lg" style="padding:20px;background:<?= $LP['goldBG'] ?>;border:1px solid <?= $LP['goldBdr'] ?>;margin-bottom:24px"><div class="lp-label text-xs uppercase font-semibold" style="color:#8a7020;margin-bottom:12px;letter-spacing:.1em">Equipment / Service Details</div><table class="text-sm" style="color:<?= $LP['body'] ?>"><tr><td style="padding:4px 0;font-weight:600;width:160px">Description:</td><td style="padding:4px 0"><?= $ct['equip'] ?></td></tr><?php if ($ct['serial'] !== ''): ?><tr><td style="padding:4px 0;font-weight:600">Serial / Model:</td><td style="padding:4px 0"><?= $ct['serial'] ?></td></tr><?php endif; ?></table></div>
    <div style="margin-bottom:24px"><div class="lp-accent text-xs uppercase font-semibold" style="color:<?= $LP['navy'] ?>;margin-bottom:8px;letter-spacing:.1em">Scope of Work:</div><div class="lp-scope-bar" style="padding-left:16px;border-left:3px solid <?= $LP['goldL'] ?>"><?= nl2br($ct['scope']) ?></div></div>
    <?php if ($ct['std'] !== ''): ?><div style="margin-bottom:24px"><div class="lp-accent text-xs uppercase font-semibold" style="color:<?= $LP['navy'] ?>;margin-bottom:8px">Standards:</div><p><?= $ct['std'] ?></p></div><?php endif; ?>
    <div class="lp-box-blue rounded-lg" style="padding:20px;background:<?= $LP['blueBG'] ?>;border:1px solid <?= $LP['blueBdr'] ?>;margin-bottom:24px"><div class="lp-accent text-xs uppercase font-semibold" style="color:<?= $LP['navy'] ?>;margin-bottom:8px">Test Results / Outcome:</div><div><?= nl2br($ct['result']) ?></div></div>
    <?php if ($ct['remarks'] !== ''): ?><div style="margin-bottom:24px"><div class="lp-accent text-xs uppercase font-semibold" style="color:<?= $LP['navy'] ?>;margin-bottom:8px">Remarks:</div><p><?= nl2br($ct['remarks']) ?></p></div><?php endif; ?>
    <p style="color:#666;font-style:italic">This certificate is issued based on the work carried out and observations made during the service.</p>
</div>
<div class="px-10 py-6 flex justify-between items-end" style="border-top:1px solid #e0e0e0">
<div class="text-xs" style="color:<?= $LP['muted'] ?>">Validity: <?= $ct['val'] !== '' ? $ct['val'] : 'As per agreement' ?></div>
<div style="text-align:center;min-width:200px"><div class="lp-stamp" style="width:176px;height:80px;border:1px dashed <?= $LP['gold'] ?>;border-radius:8px;margin-bottom:4px"></div><div style="font-size:13px;font-weight:600;color:<?= $LP['dark'] ?>">Authorized Signatory</div><div style="font-size:12px;color:<?= $LP['muted'] ?>"><?= $C['name'] ?></div></div>
</div>
<div class="lp-footer px-10 py-3 text-center text-xs" style="background:<?= $LP['navy'] ?>;color:rgba(201,168,76,.6)"><?= $C['name'] ?> | <?= $C['address'] ?> | Phone: <?= $C['phone'] ?> | Computer generated certificate.</div>
</div>

<?php else: ?>
<section style="padding:128px 0 96px;text-align:center"><div style="max-width:400px;margin:0 auto;padding:0 24px"><div style="font-size:80px;font-weight:700;color:#D9FF50;margin-bottom:16px">404</div><h2 style="font-size:24px;font-weight:600;margin-bottom:16px">Page Not Found</h2><a href="?page=home" class="bp">Go Home</a></div></section>
<?php endif; ?>


<?php if (!in_array($page, array('inv-view', 'cert-view'))): ?>
<footer class="np border-t border-white/5" style="padding:56px 0 32px">
<div style="max-width:1280px;margin:0 auto;padding:0 24px">
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:40px;margin-bottom:40px">
    <div><div style="display:flex;align-items:center;gap:12px;margin-bottom:16px"><div style="width:36px;height:36px;border-radius:8px;background:#D9FF50;display:flex;align-items:center;justify-content:center"><span style="color:#0a0a0a;font-weight:700;font-size:14px">KP</span></div><span style="color:#fff;font-weight:600">KPSwamy <span style="color:#D9FF50">TecZ</span></span></div><p style="color:#737373;font-size:14px">Electrical & Electronics Engineering Solutions.</p></div>
    <div><h4 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:#fff;margin-bottom:16px">Quick Links</h4><div style="display:flex;flex-direction:column;gap:8px"><a href="?page=home" style="font-size:14px;color:#737373;text-decoration:none">Home</a><a href="?page=home#services" style="font-size:14px;color:#737373;text-decoration:none">Services</a><a href="?page=home#contact" style="font-size:14px;color:#737373;text-decoration:none">Contact</a></div></div>
    <div><h4 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:#fff;margin-bottom:16px">Contact</h4><div style="font-size:14px;color:#737373;line-height:1.6"><div><?= $C['address'] ?></div><div style="margin-top:8px"><?= $C['phone'] ?></div><div><?= $C['email'] ?></div></div></div>
</div>
<div style="border-top:1px solid rgba(255,255,255,.05);padding-top:24px;text-align:center"><p style="font-size:12px;color:#525252">&copy; <?= $year ?> <?= $C['name'] ?>. All rights reserved. | GSTIN: <?= $C['gst'] ?></p></div>
</div>
</footer>
<?php endif; ?>


<script>
lucide.createIcons();

// Mobile menu
var mobToggle = document.getElementById('mobToggle');
var mobClose = document.getElementById('mobClose');
var mobMenu = document.getElementById('mobMenu');
if (mobToggle && mobClose && mobMenu) {
    mobToggle.addEventListener('click', function() { mobMenu.classList.add('op'); });
    mobClose.addEventListener('click', function() { mobMenu.classList.remove('op'); });
    var mlinks = document.querySelectorAll('.mlink');
    for (var i = 0; i < mlinks.length; i++) {
        mlinks[i].addEventListener('click', function() { mobMenu.classList.remove('op'); });
    }
}

// Scroll reveal
var rvEls = document.querySelectorAll('.rv');
var rvObs = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            entry.target.classList.add('vi');
            rvObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
for (var r = 0; r < rvEls.length; r++) { rvObs.observe(rvEls[r]); }

// Nav scroll
var navEl = document.querySelector('nav');
window.addEventListener('scroll', function() {
    if (navEl) {
        navEl.style.background = window.scrollY > 50 ? 'rgba(10,10,10,0.92)' : 'rgba(255,255,255,0.05)';
    }
});

// Toast
var toastEl = document.getElementById('tst');
if (toastEl) {
    setTimeout(function() { toastEl.classList.add('sh'); }, 400);
    setTimeout(function() { toastEl.classList.remove('sh'); }, 5000);
}

// Auto-calculate totals
function calcTotals() {
    var tv = parseFloat(document.getElementById('taxableInput').value) || 0;
    var rate = parseFloat(document.querySelector('[name="igst_rate"]').value) || 0;
    var gt = tv + (tv * rate / 100);
    document.getElementById('grandTotalDisplay').textContent = '\u20B9 ' + gt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Auto-fill words
    var wordsInput = document.getElementById('wordsInput');
    if (tv > 0 && wordsInput && !wordsInput.value) {
        wordsInput.value = 'INR ' + numberToWords(Math.round(gt)) + ' Only';
    }
}

function numberToWords(n) {
    var o = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    var t = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if (n < 20) return o[n];
    if (n < 100) return t[Math.floor(n/10)] + (n%10 ? ' ' + o[n%10] : '');
    if (n < 1000) return o[Math.floor(n/100)] + ' Hundred' + (n%100 ? ' and ' + numberToWords(n%100) : '');
    if (n < 100000) return numberToWords(Math.floor(n/1000)) + ' Thousand' + (n%1000 ? ' ' + numberToWords(n%1000) : '');
    if (n < 10000000) return numberToWords(Math.floor(n/100000)) + ' Lakh' + (n%100000 ? ' ' + numberToWords(n%100000) : '');
    return numberToWords(Math.floor(n/10000000)) + ' Crore' + (n%10000000 ? ' ' + numberToWords(n%10000000) : '');
}

// Invoice rows
var slCount = 1;
function addRow() {
    slCount++;
    var num = String(slCount).padStart(2, '0');
    var tbody = document.getElementById('rows');
    var tr = document.createElement('tr');
    tr.className = 'ir border-b border-white/5';
    tr.innerHTML = '<td class="py-3 pr-3 text-center text-neutral-500 sl-no">' + num + '</td>' +
        '<td class="py-3 pr-3"><textarea name="desc[]" rows="3" class="fi" style="padding:8px 16px;font-size:13px;resize:none" placeholder="Description..." required></textarea></td>' +
        '<td class="py-3 pr-3"><input type="text" name="qty[]" class="fi" style="padding:8px 16px;font-size:13px" placeholder="190 Nos"></td>' +
        '<td class="py-3 text-center"><button type="button" onclick="delRow(this)" class="text-neutral-500 hover:text-red-400"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td>';
    tbody.appendChild(tr);
    lucide.createIcons();
    tr.querySelector('textarea').focus();
}
function delRow(btn) {
    var rows = document.querySelectorAll('.ir');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    slCount = 0;
    var nums = document.querySelectorAll('.sl-no');
    for (var i = 0; i < nums.length; i++) {
        slCount = i + 1;
        nums[i].textContent = String(slCount).padStart(2, '0');
    }
}
</script>
</body>
</html>
