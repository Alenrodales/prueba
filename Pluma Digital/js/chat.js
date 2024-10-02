document.addEventListener('DOMContentLoaded', function () {
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');

    function fetchMessages() {
        fetch('get_messages.php')
            .then(response => response.json())
            .then(data => {
                chatBox.innerHTML = '';
                data.forEach(message => {
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('message');

                    const userIcon = document.createElement('img');
                    userIcon.src = message.foto_perfil;
                    userIcon.classList.add('user-icon');

                    const messageContent = document.createElement('div');
                    messageContent.classList.add('message-content');

                    const strong = document.createElement('strong');
                    strong.textContent = message.nombre_usuario;

                    const paragraph = document.createElement('p');
                    paragraph.innerHTML = message.contenido.replace(/\n/g, '<br>');

                    const timestamp = document.createElement('span');
                    timestamp.classList.add('timestamp');
                    timestamp.textContent = new Date(message.fecha_envio).toLocaleString();

                    messageContent.appendChild(strong);
                    messageContent.appendChild(paragraph);
                    messageContent.appendChild(timestamp);

                    messageElement.appendChild(userIcon);
                    messageElement.appendChild(messageContent);

                    chatBox.appendChild(messageElement);
                });
                chatBox.scrollTop = chatBox.scrollHeight; // Scroll to the bottom
            });
    }

    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(chatForm);
        fetch(chatForm.action, {
            method: 'POST',
            body: formData
        }).then(() => {
            messageInput.value = ''; // Clear input field
            fetchMessages(); // Fetch messages again to update the chat
        });
    });

    setInterval(fetchMessages, 3000); // Fetch new messages every 3 seconds
    fetchMessages(); // Initial fetch
});
