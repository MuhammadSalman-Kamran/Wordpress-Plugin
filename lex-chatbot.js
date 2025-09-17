document.addEventListener('DOMContentLoaded', function () {
  AWS.config.region = lexChatbotParams.region;
  AWS.config.credentials = new AWS.CognitoIdentityCredentials({
    IdentityPoolId: lexChatbotParams.identityPoolId
  });

  AWS.config.credentials.get(err => {
    if (err) return console.error(err);
    initializeChatbot();
  });

  function initializeChatbot() {
    const sessionId = 'session-' + Date.now();
    const lex = new AWS.LexRuntimeV2();

    const chatButton = document.getElementById('lex-chatbot-button');
    const chatContainer = document.getElementById('lex-chatbot-container');
    const chatToggle = document.getElementById('lex-chatbot-toggle');
    const chatInput = document.getElementById('lex-chatbot-input');
    const chatSend = document.getElementById('lex-chatbot-send');
    const chatMessages = document.getElementById('lex-chatbot-messages');

    let conversationEnded = false;

    chatButton.addEventListener('click', () => {
      chatContainer.classList.add('open');
      chatButton.style.display = 'none';
      if (!chatMessages.children.length) {
        displayMessage('bot', 'Welcome to ACE360TECH! Looking for digital transformation services or a custom solution? I am here to help!');
      }
    });

    chatToggle.addEventListener('click', () => {
      chatContainer.classList.remove('open');
      chatButton.style.display = 'block';
    });

    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', e => {
      if (e.key === 'Enter') sendMessage();
    });

    function sendMessage() {
      const msg = chatInput.value.trim();
      if (!msg) return;

      // ✅ Block further input if chat has ended
      if (conversationEnded) {
        chatInput.value = '';
        return;
      }

      displayMessage('user', msg);
      chatInput.value = '';
      showTyping();

      lex.recognizeText({
        botId: lexChatbotParams.botId,
        botAliasId: lexChatbotParams.aliasId,
        localeId: 'en_US',
        sessionId,
        text: msg
      }, (err, data) => {
        hideTyping();
        if (err) return displayMessage('bot', 'Error—please try again.');
        parseMsgs(data);

        if (data.sessionState.dialogAction.type === 'Close') {
          conversationEnded = true;
          displayMessage('bot', 'The chat conversation has ended.');
        }
      });
    }

    function parseMsgs(data) {
      data.messages.forEach(msg => {
        if (msg.contentType === 'CustomPayload') {
          let payload;
          try {
            payload = JSON.parse(msg.content);
          } catch {
            return displayMessage('bot', msg.content);
          }

          if (payload.type === 'options' && payload.options) {
            displayMessage('bot', payload.text);
            payload.options.forEach(opt => {
              const btn = document.createElement('button');
              btn.className = 'option-button';
              btn.textContent = opt.text;
              btn.onclick = () => {
                chatInput.value = opt.value;
                sendMessage();
              };
              chatMessages.appendChild(btn);
            });
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return;
          }
        } else {
          displayMessage('bot', msg.content);
        }
      });
    }

    function displayMessage(sender, text) {
      const div = document.createElement('div');
      div.className = sender + '-message';
      const content = document.createElement('div');
      content.className = 'message-content';
      content.textContent = text;
      div.appendChild(content);
      chatMessages.appendChild(div);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTyping() {
      const div = document.createElement('div');
      div.className = 'bot-typing';
      div.textContent = 'Typing...';
      chatMessages.appendChild(div);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function hideTyping() {
      const el = chatMessages.querySelector('.bot-typing');
      if (el) el.remove();
    }
  }
});
