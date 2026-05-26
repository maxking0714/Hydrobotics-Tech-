<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? 'Guest';
?>
<style>
    .ai-chatbot-launch {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.75rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .ai-chatbot-button {
        background: #00a8e8;
        color: white;
        border: none;
        border-radius: 999px;
        padding: 0.95rem 1.2rem;
        cursor: pointer;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
        font-size: 1rem;
        font-weight: 700;
    }
    .ai-chatbot-window {
        width: min(360px, calc(100vw - 32px));
        max-height: 520px;
        background: white;
        border-radius: 22px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.2);
        overflow: hidden;
        display: none;
        flex-direction: column;
        border: 1px solid #dbe7f0;
    }
    .ai-chatbot-window.active { display: flex; }
    .ai-chatbot-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.2rem;
        background: #0d3b66;
        color: white;
        gap: 0.75rem;
    }
    .ai-chatbot-header strong { font-size: 1rem; }
    .ai-chatbot-header p { margin: 0; color: #cfe8ff; font-size: 0.85rem; }
    .ai-chatbot-close {
        background: rgba(255,255,255,0.15);
        border: none;
        color: white;
        border-radius: 50%;
        width: 34px;
        height: 34px;
        cursor: pointer;
        font-size: 1rem;
    }
    .ai-chatbot-messages {
        padding: 1rem;
        overflow-y: auto;
        min-height: 220px;
        max-height: 320px;
        background: #f7fbff;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .ai-chatbot-message {
        padding: 0.9rem 1rem;
        border-radius: 16px;
        max-width: 100%;
        line-height: 1.4;
    }
    .ai-chatbot-message.bot {
        background: #e8f3ff;
        align-self: flex-start;
        color: #102a43;
    }
    .ai-chatbot-message.user {
        background: #0d3b66;
        color: white;
        align-self: flex-end;
    }
    .ai-chatbot-input-area {
        padding: 0.9rem 1rem 1rem;
        border-top: 1px solid #e2ecf8;
        display: flex;
        gap: 0.5rem;
        background: white;
    }
    .ai-chatbot-input {
        flex: 1;
        padding: 0.85rem 1rem;
        border-radius: 14px;
        border: 1px solid #dbe7f0;
        font-size: 0.95rem;
        outline: none;
    }
    .ai-chatbot-send {
        border: none;
        background: #00a8e8;
        color: white;
        padding: 0.85rem 1rem;
        border-radius: 14px;
        cursor: pointer;
        font-weight: 700;
    }
    .ai-chatbot-footer {
        padding: 0 1rem 1rem;
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .ai-chatbot-link {
        width: 100%;
        background: #f3f8ff;
        border: 1px solid #dbe7f0;
        color: #0d3b66;
        border-radius: 14px;
        padding: 0.85rem 1rem;
        cursor: pointer;
        text-align: center;
        font-weight: 700;
    }
    .ai-chatbot-link:hover { background: #e1efff; }
    .ai-chatbot-alert {
        position: fixed;
        bottom: 100px;
        right: 20px;
        z-index: 9998;
        padding: 1rem 1.2rem;
        background: #ffefc1;
        border: 1px solid #f5d88c;
        border-radius: 16px;
        color: #664c00;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        display: none;
    }
    .ai-chatbot-alert.active { display: block; }
    .ai-chatbot-alert button {
        margin-left: 1rem;
        background: transparent;
        border: none;
        color: #0d3b66;
        cursor: pointer;
        font-weight: 700;
    }
</style>
<div class="ai-chatbot-launch">
    <button type="button" class="ai-chatbot-button" id="aiChatbotToggle">🤖 AI Chat</button>
    <div class="ai-chatbot-window" id="aiChatbotWindow">
        <div class="ai-chatbot-header">
            <div>
                <strong>HYDROBOTICS Assistant</strong>
                <p>Ask your question and I’ll do my best to help.</p>
            </div>
            <button type="button" class="ai-chatbot-close" onclick="toggleAiChatbot()">✕</button>
        </div>
        <div class="ai-chatbot-messages" id="aiChatbotMessages">
            <div class="ai-chatbot-message bot">Hello! I am your HYDRO assistant. Ask me anything about the platform or request a human assistant if I can't solve it.</div>
        </div>
        <div class="ai-chatbot-input-area">
            <input id="aiChatbotInput" class="ai-chatbot-input" type="text" placeholder="Type your question here...">
            <button type="button" class="ai-chatbot-send" onclick="sendAiChatbotMessage()">Send</button>
        </div>
        <div class="ai-chatbot-footer">
            <button type="button" class="ai-chatbot-link" onclick="sendHelpRequestPrompt()">Request live support</button>
        </div>
    </div>
</div>
<div class="ai-chatbot-alert" id="aiChatbotAlert">
    Need a real assistant? <button type="button" onclick="openAiChatbot()">Open chat</button>
</div>
<script>
    var aiChatbotOpen = false;
    var aiUsername = <?php echo json_encode($currentUser); ?>;

    document.getElementById('aiChatbotToggle').addEventListener('click', toggleAiChatbot);

    function toggleAiChatbot() {
        var windowEl = document.getElementById('aiChatbotWindow');
        aiChatbotOpen = !aiChatbotOpen;
        windowEl.classList.toggle('active', aiChatbotOpen);
    }

    function openAiChatbot() {
        aiChatbotOpen = true;
        document.getElementById('aiChatbotWindow').classList.add('active');
        document.getElementById('aiChatbotAlert').classList.remove('active');
    }

    function appendAiChatbotMessage(text, sender) {
        var container = document.getElementById('aiChatbotMessages');
        var message = document.createElement('div');
        message.className = 'ai-chatbot-message ' + sender;
        message.innerText = text;
        container.appendChild(message);
        container.scrollTop = container.scrollHeight;
    }

    function sendAiChatbotMessage() {
        var input = document.getElementById('aiChatbotInput');
        var text = input.value.trim();
        if (!text) return;
        appendAiChatbotMessage(text, 'user');
        input.value = '';
        setTimeout(function() {
            botReply(text);
        }, 450);
    }

    function botReply(text) {
        var normalized = text.toLowerCase();
        var response = '';
        if (normalized.includes('help') || normalized.includes('support') || normalized.includes('admin') || normalized.includes('real assistant') || normalized.includes('human')) {
            response = 'I can help with general platform questions. If you need a real assistant, click the button below and I will create a support request for our admin team.';
            appendAiChatbotMessage(response, 'bot');
            showHelpAlert();
            return;
        }
        if (normalized.includes('feed') || normalized.includes('post') || normalized.includes('story') || normalized.includes('image') || normalized.includes('video') || normalized.includes('song')) {
            response = 'You can use the story creator to upload images and videos, add text on media, and set optional background music. Use the post creator on the feed or account page to publish it.';
            appendAiChatbotMessage(response, 'bot');
            return;
        }
        if (normalized.includes('login') || normalized.includes('register') || normalized.includes('phone') || normalized.includes('location')) {
            response = 'For login or registration help, use the login page and make sure your phone number is filled in. You can also use GPS to capture your location if your browser allows it.';
            appendAiChatbotMessage(response, 'bot');
            return;
        }
        if (normalized.includes('follow') || normalized.includes('like') || normalized.includes('comment') || normalized.includes('share')) {
            response = 'The feed supports likes, comments, follow/unfollow, shares, and reposts. You can interact with posts directly from the feed page.';
            appendAiChatbotMessage(response, 'bot');
            return;
        }
        if (normalized.includes('admin page') || normalized.includes('admin')) {
            response = 'If the bot cannot solve your issue, use the live support request button to notify the admin team. They will see it in pending requests.';
            appendAiChatbotMessage(response, 'bot');
            return;
        }
        response = 'I am not sure about that exact request. If you need help from an actual person, click "Request live support" below and I will send your message to the admin team.';
        appendAiChatbotMessage(response, 'bot');
    }

    function showHelpAlert() {
        var alertEl = document.getElementById('aiChatbotAlert');
        alertEl.classList.add('active');
    }

    function sendHelpRequestPrompt() {
        var input = document.getElementById('aiChatbotInput');
        var text = input.value.trim() || 'I need help from a real assistant.';
        appendAiChatbotMessage(text, 'user');
        appendAiChatbotMessage('I am sending your request to the admin team. Please wait for their response.', 'bot');
        input.value = '';
        sendHelpRequest(text);
    }

    function sendHelpRequest(message) {
        fetch('ai_chatbot_endpoint.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'request_help', message: message, user: aiUsername })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                appendAiChatbotMessage('Your request has been submitted. The admin team will review it as a pending help request.', 'bot');
            } else {
                appendAiChatbotMessage('Unable to send your request right now. Please try again later.', 'bot');
            }
        })
        .catch(function() {
            appendAiChatbotMessage('Unable to connect to support at the moment. Please try again later.', 'bot');
        });
    }
</script>