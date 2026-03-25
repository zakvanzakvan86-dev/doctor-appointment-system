<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name    = $_SESSION['username'];
$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

define('GROQ_API_KEY', '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');
define('GROQ_MODEL',   'llama-3.3-70b-versatile');

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// ── AJAX: receive message, call Groq, return reply ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    header('Content-Type: application/json');

    $user_msg = trim($_POST['message']);
    if (empty($user_msg)) { echo json_encode(['error'=>'Empty']); exit; }

    $_SESSION['chat_history'][] = ['role'=>'user','content'=>$user_msg];
    if (count($_SESSION['chat_history']) > 20)
        $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);

    $messages = [[
        'role'    => 'system',
        'content' => "You are MediBot, a friendly AI health assistant inside a Doctor Appointment App.
Help users with: medical specializations, symptoms guidance, when to see a doctor, app usage (booking, finding doctors), and wellness tips.
Be concise, warm and professional. Never diagnose — always recommend seeing a real doctor.
User's name: " . $name
    ]];
    foreach ($_SESSION['chat_history'] as $m) $messages[] = $m;

    $payload = json_encode([
        'model'       => GROQ_MODEL,
        'messages'    => $messages,
        'max_tokens'  => 512,
        'temperature' => 0.7,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) { echo json_encode(['error'=>$err]); exit; }

    $data = json_decode($resp, true);
    if (isset($data['choices'][0]['message']['content'])) {
        $reply = $data['choices'][0]['message']['content'];
        $_SESSION['chat_history'][] = ['role'=>'assistant','content'=>$reply];
        echo json_encode(['reply'=>$reply]);
    } else {
        echo json_encode(['error' => $data['error']['message'] ?? 'AI error']);
    }
    exit;
}

// ── Clear chat ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_chat'])) {
    $_SESSION['chat_history'] = [];
    header("Location: chat.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>MediBot – AI Assistant</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --blue:#1a73e8;--blue-dark:#0d47a1;--blue-light:#e8f0fe;
    --green:#00c853;--shadow:0 4px 24px rgba(26,115,232,.10);
    --sidebar-w:260px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Nunito',sans-serif;background:#f0f4ff;color:#1a1a2e;height:100vh;overflow:hidden;}
.wrapper{display:flex;height:100vh;}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);background:linear-gradient(175deg,#1e88e5,#0d47a1);color:#fff;padding:28px 20px;display:flex;flex-direction:column;height:100vh;overflow-y:auto;flex-shrink:0;}
.sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px;padding:0 6px;}
.sidebar-logo .logo-icon{width:40px;height:40px;background:rgba(255,255,255,.25);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.sidebar-logo span{font-family:'Poppins',sans-serif;font-weight:700;font-size:18px;}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;opacity:.55;padding:0 14px;margin:18px 0 8px;}
.sidebar a{display:flex;align-items:center;gap:13px;color:rgba(255,255,255,.82);text-decoration:none;padding:12px 14px;border-radius:12px;margin-bottom:4px;font-size:14.5px;font-weight:600;transition:.25s;position:relative;}
.sidebar a i{width:20px;text-align:center;font-size:15px;}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,.18);color:#fff;}
.sidebar a.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:4px;height:60%;background:#fff;border-radius:0 4px 4px 0;}
.sidebar-spacer{flex:1;}
.logout-link{background:rgba(255,82,82,.18);color:#ffcdd2 !important;}
.logout-link:hover{background:rgba(255,82,82,.35) !important;}

/* CHAT LAYOUT */
.chat-main{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;}

/* HEADER */
.chat-header{background:white;padding:14px 24px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 12px rgba(26,115,232,.08);flex-shrink:0;}
.bot-avatar{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(26,115,232,.3);}
.bot-name{font-size:16px;font-weight:800;}
.bot-status{font-size:12px;color:#00c853;font-weight:600;display:flex;align-items:center;gap:5px;margin-top:2px;}
.bot-status::before{content:'';width:7px;height:7px;border-radius:50%;background:#00c853;display:block;}
.header-right{margin-left:auto;display:flex;gap:10px;align-items:center;}
.hdr-btn{padding:8px 14px;border-radius:10px;font-family:'Nunito',sans-serif;font-size:12.5px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:6px;border:none;text-decoration:none;}
.hdr-btn.back{background:var(--blue-light);color:var(--blue);}
.hdr-btn.back:hover{background:var(--blue);color:#fff;}
.hdr-btn.clear{background:#fce4ec;color:#c62828;}
.hdr-btn.clear:hover{background:#e53935;color:#fff;}

/* MESSAGES AREA */
.chat-messages{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:14px;background:#f0f4ff;}
.chat-messages::-webkit-scrollbar{width:5px;}
.chat-messages::-webkit-scrollbar-thumb{background:#c5d8f8;border-radius:10px;}

/* BUBBLES */
.msg-row{display:flex;align-items:flex-end;gap:10px;animation:msgIn .3s ease;}
.msg-row.user{flex-direction:row-reverse;}
@keyframes msgIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}

.msg-av{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}
.msg-row.bot  .msg-av{background:linear-gradient(135deg,#1a73e8,#42a5f5);color:#fff;}
.msg-row.user .msg-av{background:linear-gradient(135deg,#7c4dff,#b39ddb);color:#fff;}

.bubble{max-width:68%;padding:12px 16px;border-radius:18px;font-size:14px;line-height:1.7;word-break:break-word;}
.msg-row.bot  .bubble{background:white;color:#1a1a2e;border-bottom-left-radius:4px;box-shadow:0 2px 10px rgba(26,115,232,.07);}
.msg-row.user .bubble{background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;border-bottom-right-radius:4px;box-shadow:0 4px 14px rgba(26,115,232,.3);}
.msg-time{font-size:10.5px;opacity:.5;margin-top:4px;display:block;}
.msg-row.user .msg-time{text-align:right;}
.bubble b,.bubble strong{font-weight:800;}
.bubble ul{padding-left:18px;margin-top:4px;}
.bubble li{margin-bottom:3px;}

/* TYPING */
.typing-row{display:flex;align-items:flex-end;gap:10px;}
.typing-bubble{background:white;padding:13px 17px;border-radius:18px;border-bottom-left-radius:4px;box-shadow:0 2px 10px rgba(26,115,232,.07);display:flex;gap:5px;}
.dot{width:8px;height:8px;border-radius:50%;background:#1a73e8;animation:bounce .9s infinite;}
.dot:nth-child(2){animation-delay:.15s;}
.dot:nth-child(3){animation-delay:.30s;}
@keyframes bounce{0%,60%,100%{transform:translateY(0);}30%{transform:translateY(-7px);}}

/* WELCOME */
.welcome-wrap{display:flex;align-items:center;justify-content:center;flex:1;padding:20px;}
.welcome-card{background:white;border-radius:20px;padding:30px;box-shadow:var(--shadow);text-align:center;max-width:500px;width:100%;}
.welcome-card .big-icon{font-size:54px;margin-bottom:14px;}
.welcome-card h2{font-size:20px;font-weight:800;margin-bottom:8px;}
.welcome-card p{font-size:13.5px;color:#888;line-height:1.7;margin-bottom:20px;}
.chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;}
.chip{background:var(--blue-light);color:var(--blue);padding:8px 14px;border-radius:20px;font-size:12.5px;font-weight:700;cursor:pointer;border:none;font-family:'Nunito',sans-serif;transition:.2s;}
.chip:hover{background:var(--blue);color:#fff;transform:translateY(-1px);}

/* INPUT */
.chat-input-wrap{background:white;padding:14px 20px 16px;box-shadow:0 -2px 12px rgba(26,115,232,.06);flex-shrink:0;}
.input-row{display:flex;align-items:flex-end;gap:10px;}
.textarea-box{flex:1;display:flex;align-items:flex-end;background:#f0f4ff;border:2px solid #e8edf5;border-radius:14px;padding:10px 14px;transition:.2s;}
.textarea-box:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(26,115,232,.08);}
#msgInput{flex:1;border:none;background:transparent;outline:none;font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;resize:none;max-height:100px;line-height:1.5;}
#msgInput::placeholder{color:#bbb;}
.send-btn{width:46px;height:46px;border-radius:12px;border:none;background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;cursor:pointer;font-size:17px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(26,115,232,.35);transition:.2s;flex-shrink:0;}
.send-btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,115,232,.45);}
.send-btn:disabled{background:#c5d8f8;box-shadow:none;cursor:not-allowed;}
.hint{font-size:11.5px;color:#bbb;text-align:center;margin-top:8px;}
</style>
</head>
<body>
<div class="wrapper">

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-stethoscope"></i></div>
        <span>Doctor App</span>
    </div>
    <div class="nav-label">Menu</div>
    <a href="dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a>
    <?php if ($role === 'user'): ?>
        <a href="doctors.php"><i class="fa-solid fa-user-doctor"></i>Find Doctors</a>
        <a href="book.php"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
        <a href="my_appointments.php"><i class="fa-solid fa-list-check"></i>My Appointments</a>
        <div class="nav-label">More</div>
        <a href="feedback.php"><i class="fa-solid fa-star"></i>Feedback</a>
        <a href="chat.php" class="active"><i class="fa-solid fa-robot"></i>AI Assistant</a>
        <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
        <a href="admin_add_doctor.php"><i class="fa-solid fa-user-plus"></i>Add Doctor</a>
        <a href="admin_bookings.php"><i class="fa-solid fa-table-list"></i>All Appointments</a>
        <div class="nav-label">More</div>
        <a href="admin_feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a>
        <a href="chat.php" class="active"><i class="fa-solid fa-robot"></i>AI Assistant</a>
        <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>
    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- CHAT -->
<div class="chat-main">

    <!-- Header -->
    <div class="chat-header">
        <div class="bot-avatar">🤖</div>
        <div>
            <div class="bot-name">MediBot <span style="font-size:11px;background:#e8f0fe;color:#1a73e8;padding:2px 8px;border-radius:20px;font-weight:700;margin-left:4px;">AI</span></div>
            <div class="bot-status">Online · Powered by Groq + LLaMA 3</div>
        </div>
        <div class="header-right">
            <a href="dashboard.php" class="hdr-btn back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            <form method="POST" style="margin:0;">
                <button type="submit" name="clear_chat" class="hdr-btn clear"
                        onclick="return confirm('Clear chat history?')">
                    <i class="fa-solid fa-trash-can"></i> Clear
                </button>
            </form>
        </div>
    </div>

    <!-- Messages -->
    <div class="chat-messages" id="chatBox">

        <?php if (empty($_SESSION['chat_history'])): ?>
        <div class="welcome-wrap" id="welcomeWrap">
            <div class="welcome-card">
                <div class="big-icon">🏥</div>
                <h2>Hi <?php echo htmlspecialchars($name); ?>, I'm MediBot!</h2>
                <p>Your personal AI health assistant. Ask me about symptoms, which doctor to visit, wellness tips, or how to use this app.</p>
                <div class="chips">
                    <button class="chip" onclick="quickSend(this)">Which doctor for back pain?</button>
                    <button class="chip" onclick="quickSend(this)">What does a cardiologist treat?</button>
                    <button class="chip" onclick="quickSend(this)">How to book an appointment?</button>
                    <button class="chip" onclick="quickSend(this)">Healthy diet tips</button>
                    <button class="chip" onclick="quickSend(this)">When should I see a doctor?</button>
                    <button class="chip" onclick="quickSend(this)">What is a general physician?</button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($_SESSION['chat_history'] as $m): ?>
            <?php if ($m['role'] === 'user'): ?>
            <div class="msg-row user">
                <div class="bubble"><?php echo nl2br(htmlspecialchars($m['content'])); ?><span class="msg-time">You</span></div>
                <div class="msg-av"><i class="fa-solid fa-user"></i></div>
            </div>
            <?php else: ?>
            <div class="msg-row bot">
                <div class="msg-av"><i class="fa-solid fa-robot"></i></div>
                <div class="bubble"><?php echo nl2br(htmlspecialchars($m['content'])); ?><span class="msg-time">MediBot</span></div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- Input -->
    <div class="chat-input-wrap">
        <div class="input-row">
            <div class="textarea-box">
                <textarea id="msgInput" rows="1"
                    placeholder="Ask me anything about health or this app..."
                    onkeydown="handleKey(event)"
                    oninput="autoResize(this)"></textarea>
            </div>
            <button class="send-btn" id="sendBtn" onclick="sendMsg()">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
        <div class="hint">⚕️ MediBot provides general information only — always consult a real doctor for medical advice.</div>
    </div>

</div>
</div>

<script>
const chatBox = document.getElementById('chatBox');
const input   = document.getElementById('msgInput');
const btn     = document.getElementById('sendBtn');

function scrollDown(){ chatBox.scrollTop = chatBox.scrollHeight; }
scrollDown();

function autoResize(el){
    el.style.height='auto';
    el.style.height=Math.min(el.scrollHeight,100)+'px';
}

function handleKey(e){
    if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); sendMsg(); }
}

function quickSend(el){
    input.value = el.textContent;
    sendMsg();
}

function now(){
    return new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
}

function formatBot(t){
    return t
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
        .replace(/\*(.*?)\*/g,'<em>$1</em>')
        .replace(/\n/g,'<br>');
}

function addBubble(role, text){
    const ww = document.getElementById('welcomeWrap');
    if(ww) ww.remove();

    const row = document.createElement('div');
    row.className = 'msg-row ' + role;

    if(role==='user'){
        const safe = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        row.innerHTML=`<div class="bubble">${safe}<span class="msg-time">${now()}</span></div><div class="msg-av"><i class="fa-solid fa-user"></i></div>`;
    } else {
        row.innerHTML=`<div class="msg-av"><i class="fa-solid fa-robot"></i></div><div class="bubble">${formatBot(text)}<span class="msg-time">MediBot · ${now()}</span></div>`;
    }
    chatBox.appendChild(row);
    scrollDown();
}

function showTyping(){
    const r=document.createElement('div');
    r.className='typing-row'; r.id='typing';
    r.innerHTML=`<div class="msg-av" style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;"><i class="fa-solid fa-robot"></i></div><div class="typing-bubble"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>`;
    chatBox.appendChild(r);
    scrollDown();
}
function hideTyping(){ const t=document.getElementById('typing'); if(t)t.remove(); }

async function sendMsg(){
    const text=input.value.trim();
    if(!text||btn.disabled) return;

    addBubble('user', text);
    input.value=''; input.style.height='auto';
    btn.disabled=true; input.disabled=true;
    showTyping();

    try{
        const res = await fetch('chat.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'message='+encodeURIComponent(text)
        });
        const data = await res.json();
        hideTyping();
        addBubble('bot', data.reply || ('⚠️ Error: '+(data.error||'Unknown')));
    } catch(e){
        hideTyping();
        addBubble('bot','⚠️ Could not reach AI. Please check your internet connection.');
    }

    btn.disabled=false; input.disabled=false; input.focus(); scrollDown();
}
</script>
</body>
</html>
