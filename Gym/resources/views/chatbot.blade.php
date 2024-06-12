@extends('layouts.user-layout')
@section('content')
<div class="content-wrapper pb-4">
    <div class="chat-container">
        <div class="chat-box" id="chat-box"></div>
        <div class="chat-input">
            <input type="text" id="chat-input" placeholder="Type your question..." onfocus="showFAQ()" onblur="hideFAQ()">
            <button onclick="sendMessage()">Send</button>
        </div>
        <div class="faq" id="faq">
            <ul>
                <li onclick="sendFAQ('What are your opening hours?')">What are your opening hours?</li>
                <li onclick="sendFAQ('What services do you offer?')">What services do you offer?</li>
                <li onclick="sendFAQ('How can I contact support?')">How can I contact support?</li>
            </ul>
        </div>
    </div>

    <script>
        function showFAQ() {
            document.getElementById('faq').style.display = 'block';
        }

        function hideFAQ() {
            setTimeout(function() { 
                document.getElementById('faq').style.display = 'none'; 
            }, 100); // Delay to allow click event to register
        }

        function typeText(element, text, interval = 50) {
            let i = 0;
            const timer = setInterval(() => {
                if (i < text.length) {
                    element.textContent = text.substring(0, i + 1) + '•';
                    i++;
                    element.scrollTop = element.scrollHeight;  // Scroll to bottom as text appears
                } else {
                    clearInterval(timer);
                    element.textContent = text;  // Remove the dot after typing ends
                }
            }, interval);
        }


        function sendMessage() {
            const chatBox = document.getElementById('chat-box');
            const chatInput = document.getElementById('chat-input');
            const question = chatInput.value;

            if (!question.trim()) return;

            // Display user message
            const userMessage = document.createElement('div');
            userMessage.className = 'message user';
            userMessage.textContent = `${question}`;
            chatBox.appendChild(userMessage);

            // Send request to Laravel API
            fetch(`/chatbot/get_answer?question=${encodeURIComponent(question)}`)
                .then(response => response.json())
                .then(data => {
                    // Display bot response
                    const botMessage = document.createElement('div');
                    botMessage.className = 'message bot';
                    chatBox.appendChild(botMessage);

                    typeText(botMessage, `${data.answer}`);
                })
                .catch(error => {
                    console.error('Error:', error);
                });

            chatInput.value = '';
        }

        function sendFAQ(question) {
            const chatBox = document.getElementById('chat-box');

            // Display user message
            const userMessage = document.createElement('div');
            userMessage.className = 'message user';
            userMessage.textContent = `${question}`;
            chatBox.appendChild(userMessage);

            // Send request to Laravel API
            fetch(`/chatbot/get_answer?question=${encodeURIComponent(question)}`)
                .then(response => response.json())
                .then(data => {
                    // Display bot response
                    const botMessage = document.createElement('div');
                    botMessage.className = 'message bot';
                    chatBox.appendChild(botMessage);

                    typeText(botMessage, `${data.answer}`);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    </script>
</div>
@endsection
