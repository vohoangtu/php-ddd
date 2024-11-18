class SessionSyncClient {
    constructor(config) {
        this.config = {
            wsUrl: config.wsUrl,
            syncInterval: config.syncInterval || 30000,
            retryAttempts: config.retryAttempts || 3,
            encryption: config.encryption || false
        };
        
        this.pendingChanges = new Map();
        this.syncQueue = [];
        this.connected = false;
        this.sessionId = null;
        
        this.initializeWebSocket();
    }

    initializeWebSocket() {
        this.ws = new WebSocket(this.config.wsUrl);
        
        this.ws.onopen = () => {
            this.connected = true;
            this.authenticate();
            this.processPendingSync();
        };

        this.ws.onmessage = (event) => {
            const message = this.decryptMessage(event.data);
            this.handleSyncMessage(message);
        };

        this.ws.onclose = () => {
            this.connected = false;
            this.scheduleReconnect();
        };
    }

    async syncChanges(changes) {
        const encryptedChanges = await this.encryptChanges(changes);
        
        if (!this.connected) {
            this.queueChanges(encryptedChanges);
            return;
        }

        this.ws.send(JSON.stringify({
            type: 'sync',
            sessionId: this.sessionId,
            changes: encryptedChanges,
            timestamp: Date.now()
        }));
    }

    async handleSyncMessage(message) {
        if (message.type === 'session_sync') {
            const changes = await this.decryptChanges(message.data.changes);
            
            // Apply changes locally
            this.applyChanges(changes);
            
            // Notify application
            this.config.onSync?.(changes);
        }
    }

    async encryptChanges(changes) {
        if (!this.config.encryption) {
            return changes;
        }

        const key = await this.deriveKey();
        return await this.encrypt(JSON.stringify(changes), key);
    }

    async decryptChanges(encryptedChanges) {
        if (!this.config.encryption) {
            return encryptedChanges;
        }

        const key = await this.deriveKey();
        const decrypted = await this.decrypt(encryptedChanges, key);
        return JSON.parse(decrypted);
    }

    queueChanges(changes) {
        this.syncQueue.push({
            changes,
            timestamp: Date.now(),
            attempts: 0
        });
    }

    async processPendingSync() {
        while (this.syncQueue.length > 0) {
            const item = this.syncQueue[0];
            
            if (item.attempts >= this.config.retryAttempts) {
                this.syncQueue.shift();
                continue;
            }

            try {
                await this.syncChanges(item.changes);
                this.syncQueue.shift();
            } catch (error) {
                item.attempts++;
                break;
            }
        }
    }

    scheduleReconnect() {
        setTimeout(() => {
            this.initializeWebSocket();
        }, 5000);
    }
} 