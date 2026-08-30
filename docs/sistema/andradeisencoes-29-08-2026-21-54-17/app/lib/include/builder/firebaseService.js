import { initializeApp } from "https://www.gstatic.com/firebasejs/10.14.0/firebase-app.js";
import { equalTo, orderByChild, getDatabase, ref, onValue, set, onDisconnect, serverTimestamp, update, onChildChanged, query, limitToLast, get, onChildAdded, push, startAfter } from "https://www.gstatic.com/firebasejs/10.14.0/firebase-database.js";
import { getAuth, signInWithCustomToken } from "https://www.gstatic.com/firebasejs/10.14.0/firebase-auth.js";

class FirebaseService {
    constructor() {    
        this.chats = {};
        this.roomListeners = {}; // Adicionando controle de listeners
        this.isInitializing = true; // Flag para controlar inicialização
        this.lastNotifiedMessages = {}; // Controle de mensagens já notificadas
        this.unreadCount = 0; // Contador de mensagens não lidas
        this.roomUnreadCounts = {}; // Contador de mensagens não lidas por sala
    }

    // Incrementa o contador de mensagens não lidas
    incrementUnreadCount() {
        this.unreadCount++;
        this.updateUnreadCountDisplay();
    }

    // Reseta o contador de mensagens não lidas
    resetUnreadCount() {
        this.unreadCount = 0;
        this.updateUnreadCountDisplay();
    }

    // Incrementa o contador de mensagens não lidas para uma sala específica
    incrementRoomUnreadCount(roomId) {


        if (!this.roomUnreadCounts[roomId]) {
            this.roomUnreadCounts[roomId] = 0;
        }
        this.roomUnreadCounts[roomId]++;
        this.updateRoomUnreadCountDisplay(roomId);
    }

    // Reseta o contador de mensagens não lidas para uma sala específica
    resetRoomUnreadCount(roomId) {
        this.roomUnreadCounts[roomId] = 0;
        this.updateRoomUnreadCountDisplay(roomId);
    }

    // Atualiza a exibição do contador de mensagens não lidas para uma sala específica
    updateRoomUnreadCountDisplay(roomId) {
        const badgeElement = document.getElementById(`unread-badge-${roomId}`);

        if (badgeElement) {
            const count = this.roomUnreadCounts[roomId] || 0;
            badgeElement.setAttribute('data-count', count);
            if (count > 0) {
                badgeElement.style.display = 'block';
                badgeElement.textContent = count > 99 ? '99+' : count;
            } else {
                badgeElement.style.display = 'none';
            }
        }
    }

    // Atualiza a exibição do contador no DOM
    updateUnreadCountDisplay() {
        const chatCountElement = document.getElementById('chat-count');
        if (chatCountElement) {
            chatCountElement.setAttribute('data-count', this.unreadCount);
            
            // Mostra/esconde o contador baseado na quantidade
            if (this.unreadCount > 0) {
                chatCountElement.style.display = 'block';
                chatCountElement.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            } else {
                chatCountElement.style.display = 'none';
            }
        }
    }

    handleUserPresence(userId) {
        const userStatusRef = ref(this.db, '/users/' + userId);
        const connectedRef = ref(this.db, '.info/connected');

        onValue(connectedRef, (snap) => {
            if (snap.val() === true) {

                // set(userStatusRef, {
                //     status: 'online',
                //     last_seen: serverTimestamp()
                // });

                update(userStatusRef, {
                    status: 'online',
                    command: '',
                    last_seen: serverTimestamp()
                });

                onDisconnect(userStatusRef).update({
                    command: '',
                    status: 'offline',
                    last_seen: serverTimestamp()
                }).then(() => {

                }).catch((error) => {
                    console.error("Erro ao configurar onDisconnect:", error);
                });
            } 
        });
    }

    listenForDatabaseChanges(userId) {

        const userRef = ref(this.db, '/users/' + userId);

        onValue(userRef, (snapshot) => {
            const userData = snapshot.val();

            if (userData && userData.command === 'force_logout') {
                this.logoutUser(userId);
            }
        }, (error) => {
            console.error(`Erro ao ouvir mudanças para o usuário ${userId}:`, error);
        });
    }

    listenForMultipleUserStatuses(userIds, callback) {
        const listeners = {};

        userIds.forEach(userId => {
            const userRef = ref(this.db, '/users/' + userId);
            
            const listener = onValue(userRef, (snapshot) => {
                const userData = snapshot.val();

                if (userData) {
                    callback(userId, userData.status || 'offline');
                } else {
                    callback(userId, 'offline');
                }
            }, (error) => {
                console.error(`Erro ao ouvir mudanças para o usuário ${userId}:`, error);
                callback(userId, 'error');
            });

            listeners[userId] = listener;
        });

        // Retorna uma função para remover os listeners quando não forem mais necessários
        return () => {
            userIds.forEach(userId => {
                if (listeners[userId]) {
                    off(ref(this.db, '/users/' + userId), 'value', listeners[userId]);
                }
            });
        };
    }

    logoutUser(userId) {
        
        __adianti_load_page('index.php?class=LoginForm&method=onLogout&static=1');
        
    }

    startPresenceSystem(userId) {
        this.handleUserPresence(userId);
        this.listenForDatabaseChanges(userId);
    }

    // Nova função para definir um atributo do usuário
    setUserAttribute( attributeName, attributeValue) {
        const userId = this.auth.currentUser.uid;
        const userRef = ref(this.db, '/users/' + userId);
        
        return update(userRef, {
            [attributeName]: attributeValue
        }).catch((error) => {
            console.error(`Erro ao atualizar o atributo ${attributeName} para o usuário ${userId}:`, error);
            throw error; // Re-throw o erro para que possa ser tratado pelo chamador
        });
    }

    async init(options, callback) {
        this.currentUser = null;
        this.currentRoom = 'general'; // Default room

        this.firebaseConfig = options.config;
        this.firebaseApp = await initializeApp(this.firebaseConfig);
        this.db = await getDatabase(this.firebaseApp);
        this.auth = await getAuth(this.firebaseApp);

        await signInWithCustomToken(this.auth, options.token)
            .then((userCredential) => {
                const user = userCredential.user;
                
                this.currentUser = user;
                this.handleUserPresence(user.uid);
                this.listenForDatabaseChanges(user.uid);

                callback();
                
                // Marca inicialização como completa após um pequeno delay
                // para permitir que os listeners sejam configurados
                setTimeout(() => {
                    this.isInitializing = false;
                }, 2000);
            })
            .catch((error) => {
                
                console.error("Erro ao autenticar o usuário:", error);
                
            });
    }

    listenForRooms(callback) {
        
        const userRoomsRef = ref(this.db, `userRooms/${this.currentUser.uid}`);
        let room;

        onValue(userRoomsRef, (snapshot) => {
            const userRoomsData = snapshot.val();
            const roomIds = userRoomsData ? Object.keys(userRoomsData) : [];
            
            // Limpa listeners de salas que o usuário não tem mais acesso
            Object.keys(this.roomListeners).forEach(roomId => {
                if (!roomIds.includes(roomId)) {
                    // Remove o listener
                    if (this.roomListeners[roomId]) {
                        this.roomListeners[roomId]();
                        delete this.roomListeners[roomId];
                    }
                    // Remove o chat do cache
                    if (this.chats[roomId]) {
                        delete this.chats[roomId];
                    }
                    // Dispara evento de usuário removido da sala
                    const event = new CustomEvent('userRemovedFromRoom', { 
                        detail: { 
                            roomId,
                            userId: this.currentUser.uid 
                        }
                    });
                    window.dispatchEvent(event);
                }
            });
        
            roomIds.forEach((roomId) => {
                // Se já existe um listener para esta sala, não cria outro
                if (this.roomListeners[roomId]) {
                    return;
                }

                const roomRef = ref(this.db, `rooms/${roomId}`);
                const unsubscribe = onValue(roomRef, (roomSnapshot) => {

                    room = roomSnapshot.val();
                    if (room) {
                        room.roomId = roomId;

                        // Atualiza o cache local com os dados mais recentes
                        if (this.chats[roomId]) {
                            // Mantém as referências da janela e container de mensagens
                            const window = this.chats[roomId].window;
                            const messagesContainer = this.chats[roomId].messagesContainer;
                            this.chats[roomId] = { ...room, window, messagesContainer };
                        } else {
                            this.chats[roomId] = room;
                            callback(room, roomId);
                        }
                        
                        if(typeof room.lastMessage != 'undefined') {
                            $(`#last-message-preview-${room.roomId}`).html(room.lastMessage.text);
                            const lastMessageTime = window.ChatApp.formatTimestamp(room.lastMessage.timestamp);    
                            
                            $(`#message-time-${room.roomId}`).html(lastMessageTime);

                            // Verifica se é uma nova mensagem e se o chat está fechado
                            // Só notifica se a mensagem não é do usuário atual (para não notificar a si mesmo)
                            // E se não estamos na inicialização e a mensagem é realmente nova
                            const messageKey = `${roomId}_${room.lastMessage.messageId}`;
                            const isNewMessage = !this.lastNotifiedMessages[messageKey];
                            const isRecentMessage = room.lastMessage.timestamp > (Date.now() - 30000); // Últimos 30 segundos
                            
                        
                            if (!this.isInitializing && 
                                isNewMessage && 
                                isRecentMessage &&
                                room.lastMessage.sender !== this.currentUser.uid && 
                                $('#chat-container').is(':hidden')) {
                                
                                __adianti_show_toast('info', 'Nova mensagem recebida no chat', 'topRight', 'fa fa-comment');
                                this.lastNotifiedMessages[messageKey] = true;
                                
                                // Incrementa o contador de mensagens não lidas
                                this.incrementUnreadCount();
                                
                                // Incrementa o contador de mensagens não lidas para esta sala específica
                                
                                this.incrementRoomUnreadCount(roomId);
                                
                                // Limpa mensagens antigas do controle (manter apenas as últimas 50)
                                const messageKeys = Object.keys(this.lastNotifiedMessages);
                                if (messageKeys.length > 50) {
                                    const oldestKeys = messageKeys.slice(0, messageKeys.length - 50);
                                    oldestKeys.forEach(key => delete this.lastNotifiedMessages[key]);
                                }
                            }
                            else if(!this.isInitializing && 
                                isNewMessage && 
                                isRecentMessage &&
                                room.lastMessage.sender !== this.currentUser.uid &&
                                $('#chat-window-'+roomId).is(':visible') == false )
                            {
                                this.lastNotifiedMessages[messageKey] = true;
                                this.incrementRoomUnreadCount(roomId);

                                const messageKeys = Object.keys(this.lastNotifiedMessages);
                                if (messageKeys.length > 50) {
                                    const oldestKeys = messageKeys.slice(0, messageKeys.length - 50);
                                    oldestKeys.forEach(key => delete this.lastNotifiedMessages[key]);
                                }
                            }

                            
                        }
                    }
                }, (error) => {
                    // Se receber erro de permissão, remove o listener e limpa o cache
                    if (error.code === 'PERMISSION_DENIED') {
                        if (this.roomListeners[roomId]) {
                            this.roomListeners[roomId]();
                            delete this.roomListeners[roomId];
                        }
                        if (this.chats[roomId]) {
                            delete this.chats[roomId];
                        }
                        // Dispara evento de usuário removido da sala
                        const event = new CustomEvent('userRemovedFromRoom', { 
                            detail: { 
                                roomId,
                                userId: this.currentUser.uid 
                            }
                        });
                        window.dispatchEvent(event);
                    } else {
                        console.error(`Erro ao escutar sala ${roomId}:`, error);
                    }
                });

                // Armazena a função de cleanup do listener
                this.roomListeners[roomId] = unsubscribe;
            });
        }, (error) => {
            console.error("Erro ao escutar salas do usuário:", error);
        });
    }

    listenForRoomChanges(roomId) {
        const roomRef = ref(this.db, `rooms/${roomId}`);
        const userRoomRef = ref(this.db, `userRooms/${this.currentUser.uid}/${roomId}`);

        // Monitora mudanças na sala
        onValue(roomRef, (snapshot) => {
            const roomData = snapshot.val();
            
            // Se a sala não existe mais
            if (!roomData) {
                // Dispara evento de sala removida
                const event = new CustomEvent('roomDeleted', { 
                    detail: { roomId }
                });
                window.dispatchEvent(event);
                return;
            }

            // Verifica se o usuário atual ainda é participante
            if (!roomData.participants || !roomData.participants[this.currentUser.uid]) {
                // Dispara evento de usuário removido da sala
                const event = new CustomEvent('userRemovedFromRoom', { 
                    detail: { 
                        roomId,
                        userId: this.currentUser.uid 
                    }
                });
                window.dispatchEvent(event);
            }
        });

        // Monitora a referência da sala no userRooms do usuário atual
        onValue(userRoomRef, (snapshot) => {
            if (!snapshot.exists()) {
                // Se a referência não existe mais, significa que o usuário foi removido
                const event = new CustomEvent('userRemovedFromRoom', { 
                    detail: { 
                        roomId,
                        userId: this.currentUser.uid 
                    }
                });
                window.dispatchEvent(event);
            }
        });
    }

    async createNewRoom(participants, type = 'chat', name = '') {
        const newRoomRef = push(ref(this.db, 'rooms'));
        const roomId = newRoomRef.key;

        // Prepare updates for room data and userRooms
        let updates = {};
        let room = {
            window: null,
            messagesContainer: null,
            participants: {},
            participantsColor: {},
            lastMessage: null,
            lastMessageTime: null,
            roomId: null,
            type: type,
            groupName: name,
            owner: this.currentUser.uid // Adicionando o owner como o usuário atual
        };
        // Add participants to the room's participants list
        let cont = 0;
        const colors = window.ChatApp.getColors();

        participants.forEach(userId => {
            updates[`rooms/${roomId}/participants/${userId}`] = true;
            updates[`rooms/${roomId}/participantsColor/${userId}`] = colors[cont];

            updates[`userRooms/${userId}/${roomId}`] = true;

            room.participants[userId] = true;
            room.participantsColor[userId] = colors[cont];

            cont++;
        });

        updates[`rooms/${roomId}/lastMessage`] = null;
        updates[`rooms/${roomId}/lastMessageTime`] = null;
        updates[`rooms/${roomId}/type`] = type;
        updates[`rooms/${roomId}/groupName`] = name;
        updates[`rooms/${roomId}/owner`] = this.currentUser.uid; // Adicionando o owner no update
        

        await update(ref(this.db), updates);

        room.roomId = newRoomRef.key

        return room;
    }

    async listenForMessages(roomId, callback) {
        const messagesRef = ref(this.db, `rooms/${roomId}/messages`);
        const messagesQuery = query(messagesRef, orderByChild('timestamp'), limitToLast(50));
        
        try {
            // Carregar mensagens existentes
            
            let message = null;

            const snapshot = await get(messagesQuery);
            if (snapshot.exists()) {
                
                snapshot.forEach((childSnapshot) => {
                    message = childSnapshot.val();
                    callback(childSnapshot.val(), 'initial');
                });
            }
    
            // Obter o timestamp da última mensagem
            let lastTimestamp = 0;
            if (message) {
                lastTimestamp = message.timestamp;
            }

            // Configurar listener apenas para novas mensagens
            const newMessagesQuery = query(messagesRef, orderByChild('timestamp'), startAfter(lastTimestamp));
            const unsubscribe = onChildAdded(newMessagesQuery, (snapshot) => {
                const newMessage = snapshot.val();
                callback(newMessage, 'new');
            });

            return unsubscribe;
    
        } catch (error) {
            console.error("Error fetching or listening for messages:", error);
        }
    }

    async sendMessage(roomId, message) {
        const messagesRef = ref(this.db, `rooms/${roomId}/messages`);
        await push(messagesRef, message);

        let updates = {};

        updates[`rooms/${roomId}/lastMessage`] = message;
        await update(ref(this.db), updates);
    }

    async deleteRoom(roomId) {
        try {
            const roomRef = ref(this.db, `rooms/${roomId}`);
            const roomSnapshot = await get(roomRef);
            
            if (!roomSnapshot.exists()) {
                return;
            }

            const roomData = roomSnapshot.val();
            
            // Verifica se o usuário atual é o dono do grupo
            if (roomData.owner !== this.currentUser.uid) {
                console.error('Não autorizado a deletar este grupo');
                throw new Error('Não autorizado a deletar este grupo');
            }

            const participants = roomData.participants || {};
            
            // Remove a sala da lista de salas de todos os participantes
            const updates = {};
            Object.keys(participants).forEach(participantId => {
                updates[`userRooms/${participantId}/${roomId}`] = null;
            });
            
            // Remove a sala
            updates[`rooms/${roomId}`] = null;
            
            // Executa todas as atualizações em uma única transação
            await update(ref(this.db), updates);
            
            // Atualiza o cache local
            if (this.chats[roomId]) {
                delete this.chats[roomId];
            }
            
            return true;
        } catch (error) {
            console.error('Erro ao deletar sala:', error);
            throw error;
        }
    }

    async removeParticipant(roomId, participantId) {
        try {
            // Remove o participante do nó de participantes da sala
            const roomRef = ref(this.db, `rooms/${roomId}/participants/${participantId}`);
            await set(roomRef, null);

            // Remove a sala da lista de salas do usuário
            const userRoomRef = ref(this.db, `userRooms/${participantId}/${roomId}`);
            await set(userRoomRef, null);

            // Atualiza a variável local de chats
            if (this.chats[roomId]) {
                delete this.chats[roomId].participants[participantId];
            }

            return true;
        } catch (error) {
            console.error('Erro ao remover participante:', error);
            throw error;
        }
    }

    async getRoom(roomId) {
        try {
            const roomRef = ref(this.db, `rooms/${roomId}`);
            const snapshot = await get(roomRef);
            
            if (snapshot.exists()) {
                const roomData = snapshot.val();
                // Atualiza o cache local se necessário
                if (this.chats[roomId]) {
                    this.chats[roomId] = {
                        ...this.chats[roomId],
                        ...roomData
                    };
                }
                return roomData;
            }
            return null;
        } catch (error) {
            console.error('Erro ao buscar sala:', error);
            throw error;
        }
    }

    async addParticipantsToGroup(userIds, groupId) {
        try {
            const roomRef = ref(this.db, `rooms/${groupId}`);
            const roomSnapshot = await get(roomRef);
            
            if (!roomSnapshot.exists()) {
                throw new Error('Grupo não encontrado');
            }

            const roomData = roomSnapshot.val();
            
            // Verifica se o usuário atual é o dono do grupo
            if (roomData.owner !== this.currentUser.uid) {
                console.error('Não autorizado a adicionar participantes neste grupo');
                throw new Error('Não autorizado a adicionar participantes neste grupo');
            }

            const updates = {};

            // Para cada novo usuário
            for (const userId of userIds) {
                // Adiciona o usuário aos participantes do grupo
                updates[`rooms/${groupId}/participants/${userId}`] = true;
                // Adiciona o grupo à lista de grupos do usuário
                updates[`userRooms/${userId}/${groupId}`] = true;
            }

            // Atualiza o banco de dados
            return update(ref(this.db), updates);
        } catch (error) {
            console.error('Erro ao adicionar participantes ao grupo:', error);
            throw error;
        }
    }

    async leaveGroup(roomId) {
        try {
            const roomRef = ref(this.db, `rooms/${roomId}`);
            const roomSnapshot = await get(roomRef);
            
            if (!roomSnapshot.exists()) {
                throw new Error('Grupo não encontrado');
            }

            const roomData = roomSnapshot.val();
            
            // Verifica se o usuário não é o dono do grupo
            if (roomData.owner === this.currentUser.uid) {
                throw new Error('O dono do grupo não pode sair, apenas deletar o grupo');
            }

            // Remove o usuário do grupo
            const updates = {};
            updates[`rooms/${roomId}/participants/${this.currentUser.uid}`] = null;
            updates[`userRooms/${this.currentUser.uid}/${roomId}`] = null;

            // Executa todas as atualizações em uma única transação
            await update(ref(this.db), updates);
            
            return true;
        } catch (error) {
            console.error('Erro ao sair do grupo:', error);
            throw error;
        }
    }

}

const firebaseService = new FirebaseService();

// Expondo a instância no escopo global
window.FirebaseService = firebaseService;


window.dispatchEvent(new Event('firebaseLoaded'));