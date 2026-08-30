// import { FirebaseService } from 'firebase.js';

class ChatApp {
    
    constructor() {
        this.currentUser = null;
        this.chats = {}; // Inicialização correta de this.chats
        this.currentRoom = null;
    }

    init() {
        this.firebaseService = FirebaseService;
        this.currentUser = this.firebaseService.currentUser;
        this.currentRoom = null;
        this.activeChats = {};
        this.initElements();
        this.loadRooms();
        this.setupEventListeners();

        $('#chat-start-icon,#chat-list-close-btn').on('click', () => {
            const wasHidden = $('#chat-container').is(':hidden');
            $('#chat-container').toggle();
            
            // Se o chat estava escondido e agora está sendo mostrado, reseta o contador
            if (wasHidden && $('#chat-container').is(':visible')) {
                this.firebaseService.resetUnreadCount();
            }
        });
    }

    setupEventListeners() {
        // Evento quando uma sala é deletada
        window.addEventListener('roomDeleted', (event) => {
            const { roomId } = event.detail;
            
            // Remove a sala do cache local
            if (this.chats[roomId]) {
                delete this.chats[roomId];
            }

            // Remove a janela do chat se estiver aberta
            this.closeChatWindow(roomId);

            // Remove o item da lista de chats
            $(`#chat-list .contact[data-room-id="${roomId}"]`).remove();
        });

        // Evento quando o usuário é removido de uma sala
        window.addEventListener('userRemovedFromRoom', (event) => {
            const { roomId, userId } = event.detail;
            
            // Se o usuário removido é o usuário atual
            if (userId === this.currentUser.uid) {
                // Remove a sala do cache local
                if (this.chats[roomId]) {
                    delete this.chats[roomId];
                }

                // Remove a janela do chat se estiver aberta
                this.closeChatWindow(roomId);

                // Remove o item da lista de chats
                $(`#chat-list .contact[data-room-id="${roomId}"]`).remove();

                // Se houver algum modal aberto relacionado a esta sala, fecha
                if ($('[role=dialog]').length > 0) {
                    const currentModal = $('[role=dialog]').last();
                    if (currentModal.find(`[data-room-id="${roomId}"]`).length > 0) {
                        currentModal.remove();
                    }
                }
            }
        });
    }

    closeChatWindow(roomId) {
        const chatWindow = $(`#chat-window-${roomId}`);
        if (chatWindow.length > 0) {
            chatWindow.remove();
        }
    }

    disable() {

        this.currentRoom = null;
        this.activeChats = {};        

        $('#chat-start-icon, #chat-list-close-btn, #chat-container').remove();
        $('#chat-windows, #chat-list, #newChatBtn, #userSelect, #startChatBtn').remove();
    }

    initElements() {
        this.chatContainer = document.getElementById('chat-container');
        this.chatWindows = document.getElementById('chat-windows');
        this.chatList = document.getElementById('chat-list');
        this.newChatBtn = document.getElementById('newChatBtn');
        this.userSelect = document.getElementById('userSelect');
        this.startChatBtn = document.getElementById('startChatBtn');
    }

    loadRooms() {

        this.chatList.innerHTML = ''; 

        this.firebaseService.listenForRooms((room, roomId) => {
        
            this.addRoomToList(roomId, room);
            
            if(typeof this.chats[roomId] == 'undefined')
            {
                this.chats[roomId] = {
                    window: null,
                    messagesContainer: null,
                    participants: room.participants,
                    participantsColor: room.participantsColor,
                    lastMessage: room.lastMessage,
                    lastMessageTime: room.lastMessageTime,
                    roomId: room.roomId,
                    type: room.type,
                };
            }
            else
            {
                const chat = this.chats[roomId];
                chat.lastMessage = room.lastMessage;
                chat.lastMessageTime = room.lastMessageTime;
            }

        });
    }

    addRoomToList(roomId, roomData) {
        const roomElement = document.createElement('div');
        roomElement.className = 'contact';
        roomElement.setAttribute('data-room-id', roomId); // Adicionando o atributo data-room-id
        
        let otherParticipantId = null;
        for (let participantId in roomData.participants) {
            if (participantId !== this.currentUser.uid) {
                otherParticipantId = participantId;
                break;
            }
        }
        
        if(typeof BuilderTemplate.users[otherParticipantId] == 'undefined')
        {
            return false;
        }

        const otherParticipant = BuilderTemplate.users[otherParticipantId];

        let lastMessageTime = '';

        if(typeof roomData.lastMessage != 'undefined')
        {
            lastMessageTime = this.formatTimestamp(roomData.lastMessage.timestamp);    
        }

        if(roomData.type == 'chat')
        {
            roomElement.innerHTML = `
                <div class="contact-info">
                <span class="contact-name">${otherParticipant}</span>
                <span class="status-indicator" title="Online" id="chat-stauts-indicator-${otherParticipantId}">
                    <span class="status-dot online"></span>
                </span>
                </div>
                <div class="last-message">
                    <p class="message-preview" id="last-message-preview-${roomId}">
                        ${roomData?.lastMessage?.text ?? ''}
                    </p>
                    <span class="message-time" id="message-time-${roomId}">
                        ${lastMessageTime}
                    </span>
                    <span class="chat-unread-messages" id="unread-badge-${roomId}" data-count="0" style="display: none;">0</span>
                </div>
            `;

            roomElement.addEventListener('click', () => this.openChat(roomId, otherParticipant));
        }
        else
        {
            // Adiciona os botões de ação do grupo
            const deleteButton = roomData.owner === this.currentUser.uid 
                ? `<button class="delete-group-btn" title="Deletar grupo"><i class="fas fa-trash"></i></button>`
                : '';

            const participantsButton = `<button class="view-participants-btn" title="Ver participantes"><i class="fas fa-users"></i></button>`;

            roomElement.innerHTML = `
                <div class="contact-info">
                    <span class="contact-name">${roomData.groupName}</span>
                    <div class="group-actions">
                        ${participantsButton}
                        ${deleteButton}
                    </div>
                </div>
                <div class="last-message">
                    <p class="message-preview" id="last-message-preview-${roomId}">
                        ${roomData?.lastMessage?.text ?? ''}
                    </p>
                    <span class="message-time" id="message-time-${roomId}">
                        ${lastMessageTime}
                    </span>
                    <span class="chat-unread-messages" id="unread-badge-${roomId}" data-count="0" style="display: none;">0</span>
                </div>
            `;

            // Adiciona o evento de click para abrir o chat
            const contactInfo = roomElement.querySelector('.contact-info');
            contactInfo.addEventListener('click', (e) => {
                // Não abre o chat se clicou em algum botão de ação
                if (!e.target.closest('.group-actions')) {
                    this.openChat(roomId, roomData.groupName);
                }
            });

            // Adiciona o evento de click no botão de deletar
            if (roomData.owner === this.currentUser.uid) {
                const deleteBtn = roomElement.querySelector('.delete-group-btn');
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation(); // Previne a propagação do click para o elemento pai
                    this.deleteGroup(roomId);
                });
            }

            // Adiciona o evento de click no botão de ver participantes
            const participantsBtn = roomElement.querySelector('.view-participants-btn');
            participantsBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Previne a propagação do click para o elemento pai
                this.showParticipants(roomId);
            });
        }
        
        this.chatList.appendChild(roomElement);

        if(typeof this.activeChats[otherParticipantId] == 'undefined' && roomData.type == 'chat')
        {
            this.activeChats[otherParticipantId] = this.firebaseService.listenForMultipleUserStatuses([otherParticipantId], function(userId, status){

                if(status == 'online')
                {
                    $(`#chat-stauts-indicator-${otherParticipantId}`).attr('title', 'Online');
                    $(`#chat-stauts-indicator-${otherParticipantId} .status-dot`).removeClass('offline').addClass('online');
                }
                else
                {
                    $(`#chat-stauts-indicator-${otherParticipantId}`).attr('title', 'Offline');
                    $(`#chat-stauts-indicator-${otherParticipantId} .status-dot`).removeClass('online').addClass('offline');
                }
            })
        }
    }

    async startNewChat(userId) {
        const room = await this.firebaseService.createNewRoom([this.currentUser.uid, userId]);
        this.chats[room.roomId] = room;
        this.openChat(room.roomId, BuilderTemplate.users[userId]);
    }

    async startNewChatGroup(userIds, groupName) {
    
        const room = await this.firebaseService.createNewRoom(userIds, 'group', groupName);
        this.chats[room.roomId] = room;
        this.openChat(room.roomId, groupName);
    }

    openChat(roomId, participantName) {
        if (!this.chats[roomId]) {
            console.error('Chat não encontrado:', roomId);
            return;
        }

        // Reseta o contador de mensagens não lidas ao abrir um chat
        this.firebaseService.resetUnreadCount();
        
        // Reseta o contador de mensagens não lidas para esta sala específica
        this.firebaseService.resetRoomUnreadCount(roomId);

        // Inicia o monitoramento da sala
        this.firebaseService.listenForRoomChanges(roomId);

        if (this.chats[roomId] && this.chats[roomId].window) {
            // Se a janela do chat já existe, apenas a mostra
            this.chats[roomId].window.style.display = 'flex';
            return;
        }
        
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chat-window';
        chatWindow.id = `chat-window-${roomId}`; // Adicionando o ID correto
        chatWindow.innerHTML = `
            <div class="chat-header">
                <span>${participantName}</span>
                <button class="close-btn">&times;</button>
            </div>
            <div class="chat-messages"></div>
            <div class="chat-input">
                <input type="text" placeholder="${Builder.translate('type_message')}">
                <button class="chat-send-btn">${Builder.translate('send')}</button>
            </div>
        `;

        const closeBtn = chatWindow.querySelector('.close-btn');
        const input = chatWindow.querySelector('input');
        const sendBtn = chatWindow.querySelector('.chat-send-btn');

        closeBtn.addEventListener('click', () => {
            chatWindow.style.display = 'none';
        });

        const sendMessage = () => {
            const message = input.value.trim();
            if (message) {
                this.firebaseService.sendMessage(roomId, {
                    sender: this.currentUser.uid,
                    text: message,
                    timestamp: Date.now(),
                    messageId: Math.random().toString(36)
                });

                this.chats[roomId].lastMessageTime = Date.now();
                this.chats[roomId].lastMessage = message;
                
                input.value = '';
            }
        };

        sendBtn.addEventListener('click', () => {
            sendMessage();
        });

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        this.chatWindows.appendChild(chatWindow);

        // Atualiza o objeto this.chats com a janela e o container de mensagens
        this.chats[roomId].window = chatWindow;
        this.chats[roomId].messagesContainer = chatWindow.querySelector('.chat-messages');

        // Carrega as mensagens existentes e configura o listener para novas mensagens
        this.firebaseService.listenForMessages(roomId, (message) => {
            this.displayMessage(roomId, message);
        });
    }

    async deleteGroup(roomId) {
        __adianti_question(
            'Deletar Grupo',
            'Tem certeza que deseja deletar este grupo?',
            async () => {
                await this.firebaseService.deleteRoom(roomId);
                // O chat será removido automaticamente da lista quando o Firebase notificar a mudança
            },
            () => {}, // Não faz nada se cancelar
            'Sim',
            'Não'
        );
    }

    async showParticipants(roomId) {
        const roomData = await this.firebaseService.getRoom(roomId);
        if (!roomData) {
            console.error('Dados do grupo não encontrados:', roomId);
            return;
        }

        let participantsList = '';
        for (let participantId in roomData.participants) {
            const isOwner = participantId === roomData.owner;
            const canRemove = roomData.owner === this.currentUser.uid && !isOwner;
            
            participantsList += `
                <div class="participant-item">
                    <div class="participant-info">
                        <span>${BuilderTemplate.users[participantId]}</span>
                        ${isOwner ? '<span class="owner-badge">Criador</span>' : ''}
                    </div>
                    ${canRemove ? `
                        <button class="remove-participant-btn" data-participant-id="${participantId}" title="Remover participante">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    ` : ''}
                </div>
            `;
        }

        // Cria o modal usando o template do Adianti
        __adianti_window('Participantes do Grupo', '450px', '350', `
            <div class="participants-list">
                ${roomData.owner === this.currentUser.uid ? `
                    <div class="add-participant-section">
                        <a class="btn btn-primary add-participant-btn" href="index.php?class=SystemAddUserGroupForm&method=onShow&group_id=${roomId}" generator="adianti" > 
                            <i class="fas fa-user-plus"></i> Participante
                        </a>
                    </div>
                    <hr class="participant-divider">
                ` : ''}
                <div class="participants-container">
                    ${participantsList}
                </div>
                ${roomData.owner !== this.currentUser.uid ? `
                    <hr class="participant-divider">
                    <div class="leave-group-section">
                        <button class="btn btn-danger leave-group-btn">
                            <i class="fas fa-sign-out-alt"></i> Sair do Grupo
                        </button>
                    </div>
                ` : ''}
            </div>
        `);

        // Adiciona os eventos de click nos botões de remover após o modal ser criado
        if (roomData.owner === this.currentUser.uid) {
            document.querySelectorAll('.remove-participant-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const participantId = e.currentTarget.dataset.participantId;
                    const participantName = BuilderTemplate.users[participantId];
                    
                    __adianti_question(
                        'Remover Participante',
                        `Tem certeza que deseja remover ${participantName} do grupo?`,
                        async () => {
                            try {
                                // Primeiro envia a mensagem do sistema
                                await this.sendSystemMessage(roomId, `${participantName} foi removido do grupo`);
                                // Depois remove o participante
                                await this.firebaseService.removeParticipant(roomId, participantId);
                                // Fecha o modal atual
                                $('[role=dialog]').last().remove();
                                
                                // Verifica se o grupo ainda existe e tem participantes suficientes
                                const updatedRoom = await this.firebaseService.getRoom(roomId);
                                if (!updatedRoom) {
                                    // Se o grupo não existe mais, remove da interface
                                    if (this.chats[roomId]) {
                                        delete this.chats[roomId];
                                    }
                                    return;
                                }

                                // Conta participantes excluindo o owner
                                const participantsCount = Object.keys(updatedRoom.participants || {}).filter(
                                    pid => pid !== updatedRoom.owner
                                ).length;

                                if (participantsCount === 0) {
                                    // Se não há mais participantes além do owner, remove o grupo
                                    await this.firebaseService.deleteRoom(roomId);
                                    if (this.chats[roomId]) {
                                        delete this.chats[roomId];
                                    }
                                    return;
                                }

                                // Se ainda há participantes, reabre o modal com a lista atualizada
                                this.showParticipants(roomId);
                            } catch (error) {
                                console.error('Erro ao remover participante:', error);
                            }
                        },
                        () => {}, // Não faz nada se cancelar
                        'Sim',
                        'Não'
                    );
                });
            });
        } else {
            // Adiciona o evento de click no botão de sair do grupo
            const leaveBtn = document.querySelector('.leave-group-btn');
            if (leaveBtn) {
                leaveBtn.addEventListener('click', () => {
                    __adianti_question(
                        'Sair do Grupo',
                        'Tem certeza que deseja sair deste grupo?',
                        async () => {
                            try {
                                const userName = BuilderTemplate.users[this.currentUser.uid];
                                // Primeiro envia a mensagem do sistema
                                await this.sendSystemMessage(roomId, `${userName} saiu do grupo`);
                                // Depois sai do grupo
                                await this.firebaseService.leaveGroup(roomId);
                                $('[role=dialog]').last().remove();
                                
                                // O listener do Firebase já vai cuidar de remover o grupo da lista e fechar a janela do chat
                            } catch (error) {
                                console.error('Erro ao sair do grupo:', error);
                            }
                        },
                        () => {}, // Não faz nada se cancelar
                        'Sim',
                        'Não'
                    );
                });
            }
        }
    }

    async sendSystemMessage(roomId, text) {
        await this.firebaseService.sendMessage(roomId, {
            sender: this.currentUser.uid, // Usa o ID do usuário atual em vez de 'system'
            text: text,
            timestamp: Date.now(),
            type: 'system' // Mantém o tipo como 'system' para exibição diferenciada
        });
    }

    async addParticipants(userIds, groupId) {
        if (!Array.isArray(userIds) || !groupId) {
            console.error('Parâmetros inválidos para addParticipants');
            return;
        }
        
        try {
            await this.firebaseService.addParticipantsToGroup(userIds, groupId);
            
            // Fecha o modal atual de participantes se estiver aberto
            $('[role=dialog]').last().remove();
            
            // Obtém os dados atualizados do grupo
            const roomData = await this.firebaseService.getRoom(groupId);
            
            // Reabre o modal com a lista atualizada de participantes
            if (roomData) {
                this.showParticipants(groupId);
            }
        } catch (error) {
            console.error('Erro ao adicionar participantes:', error);
            throw error;
        }
    }

    // async sendMessage() {
    //     const message = this.messageInput.value.trim();
    //     if (message && this.currentRoom && this.currentUser) {
    //         await this.firebaseService.sendMessage(this.currentRoom, {
    //             sender: this.currentUser.uid,
    //             text: message,
    //             timestamp: Date.now()
    //         });
    //         this.messageInput.value = '';
    //     }
    // }

    // Função para comparar se duas datas são do mesmo dia
    isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
            date1.getMonth() === date2.getMonth() &&
            date1.getDate() === date2.getDate();
    }


    displayMessage(roomId, message) {
        
        const chat = this.chats[roomId];
        if (!chat) return;    
        const chatContainer = chat.messagesContainer;
        const messageDate = new Date(message.timestamp);

        if(typeof this.chats[roomId].lastMessageDate == 'undefined')
        {
            this.chats[roomId].lastMessageDate = null;
        }

        // Verifica se é um novo dia ou o primeiro mensagem
        if (!this.chats[roomId].lastMessageDate || !this.isSameDay(this.chats[roomId].lastMessageDate, messageDate)) {
            const dateSeparator = document.createElement('div');
            dateSeparator.className = 'date-separator';
            dateSeparator.textContent = this.formatTimestamp(message.timestamp, 'd/m/Y');
            chatContainer.appendChild(dateSeparator);
        }

        const messageElement = document.createElement('div');
        messageElement.className = `message ${message.sender === this.currentUser.uid ? 'outgoing' : 'incoming'}`;

        if(chat.type == 'group' && message.sender != this.currentUser.uid)
        {
            const senderName = BuilderTemplate.users[message.sender];

            if (message.type === 'system') {
                messageElement.className = 'message system-message';
                messageElement.innerHTML = `
                    <div class="message-content">
                        <p>${message.text}</p>
                        <span class="message-time">${this.formatTimestamp(message.timestamp, 'H:i')}</span>
                    </div>
                `;
            }
            else{
                messageElement.innerHTML = `
                    <div class="message-content">
                        <span class="message-sender" style="color:${chat.participantsColor[message.sender]}">${senderName}:</span>    
                        <span class="message-text">${message.text}</span>
                        <span class="message-time">${this.formatTimestamp(message.timestamp, 'H:i')}</span>
                    </div>
                `;
            }            
        }
        else if (message.type === 'system') {
            messageElement.className = 'message system-message';
            messageElement.innerHTML = `
                <div class="message-content">
                    <p>${message.text}</p>
                    <span class="message-time">${this.formatTimestamp(message.timestamp, 'H:i')}</span>
                </div>
            `;
        }
        else {
            messageElement.innerHTML = `
                <div class="message-content">
                    <span class="message-text">${message.text}</span>
                    <span class="message-time">${this.formatTimestamp(message.timestamp, 'H:i')}</span>
                </div>
            `;
        }

        chatContainer.appendChild(messageElement);

        // Atualiza a data da última mensagem
        this.chats[roomId].lastMessageDate = messageDate;

        // Rola para a última mensagem
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    formatTimestamp(timestamp, mask = 'd/m/Y H:i:s') {
        const date = new Date(timestamp);
        
        const replacements = {
            'd': date.getDate().toString().padStart(2, '0'),
            'm': (date.getMonth() + 1).toString().padStart(2, '0'),
            'Y': date.getFullYear(),
            'y': date.getFullYear().toString().slice(-2),
            'H': date.getHours().toString().padStart(2, '0'),
            'i': date.getMinutes().toString().padStart(2, '0'),
            's': date.getSeconds().toString().padStart(2, '0')
        };
    
        return mask.replace(/[dmYyHis]/g, match => replacements[match] || match);
    }

    getColors(){
        return [
            "#d42a66",
            "#1fa855",
            "#0063cb",
            "#fa6533",
            "#a32553",
            "#00a884",
            "#046692",
            "#ffbc38",
            "#b80531",
            "#008069",
            "#5e47de",
            "#25d366",
            "#911435",
            "#028377",
            "#4837af",
            "#06cf9c",
            "#027eb5",
            "#667781",
            "#ff2e74",
            "#9a4529",
            "#046a62",
            "#54656f",
            "#ea0038",
            "#c89631",
            "#0451a3",
            "#02a698",
            "#9d792c",
            "#1b8748",
            "#009de2"
          ]
    }
}


window.ChatApp = new ChatApp();

// // Iniciar a aplicação quando o Firebase estiver carregado
// window.addEventListener('firebaseLoaded', () => {
//     alert(1);
    
// });