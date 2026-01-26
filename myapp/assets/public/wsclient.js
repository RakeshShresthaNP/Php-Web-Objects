/**
 # WSClient for PHP MVC WebSocket Engine
 # Handles Internal Heartbeat, MVC Bridging, JWT Injection, and Visibility Reconnects
 */
class WSClient {
    /**
     * @param {string} url - The WebSocket URL (e.g., ws://127.0.0.1:8080)
     * @param {string} tokenKey - The key where your JWT is stored in LocalStorage
     */
    constructor(url, tokenKey = 'pwo_token') {
        this.url = url;
        this.tokenKey = tokenKey;
        this.socket = null;
        this.pingInterval = 30000; // 30s to keep MySQL connection alive
        this.reconnectTimeout = 5000;
        this.heartbeatTimer = null;

        // Initialize visibility listener for mobile/tab switching
        this.initVisibilityHandler();
    }

    /**
     * Initializes the connection and binds events
     */
    connect() {
        // Prevent duplicate connections if one is already opening
        if (this.socket && this.socket.readyState === WebSocket.CONNECTING) return;

        console.log("🚀 WSClient: Attempting connection...");
        this.socket = new WebSocket(this.url);

        this.socket.onopen = () => {
            console.log("✅ WSClient: Connected to Server");
            this.startHeartbeat();
            this.dispatchGlobalEvent('ws_connected', { url: this.url });
        };

        this.socket.onmessage = (event) => {
            try {
                const response = JSON.parse(event.data);
                this.handleIncoming(response);
            } catch (e) {
                console.warn("WSClient: Received non-JSON data:", event.data);
            }
        };

        this.socket.onclose = (e) => {
            console.warn(`WSClient: Connection lost (${e.code}). Retrying in ${this.reconnectTimeout / 1000}s...`);
            this.stopHeartbeat();
            this.dispatchGlobalEvent('ws_disconnected');
            
            // Auto-reconnect logic
            setTimeout(() => this.connect(), this.reconnectTimeout);
        };

        this.socket.onerror = (error) => {
            console.error("WSClient: Socket Error", error);
        };
    }

    /**
     * The MVC Bridge
     * Sends a request structured for the PHP WSSocket::dispatch method
     */
	call(controller, method, params = {}, headers = {}) {
	    const payload = JSON.stringify({
	        controller: controller,
	        method: method,
	        params: params,
	        headers: headers
	    });

	    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
	        this.socket.send(payload);
	    }
	}
	
    /**
     * Internal Heartbeat
     * Triggers WSSocket::process internal ping to keep DB connection warm
     */
    startHeartbeat() {
        this.stopHeartbeat(); // Clear any existing timer
        this.heartbeatTimer = setInterval(() => {
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify({ method: 'ping' }));
            }
        }, this.pingInterval);
    }

    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    /**
     * Visibility Handler
     * Detects when a user wakes up their phone or switches back to the tab
     */
    initVisibilityHandler() {
        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === 'visible') {
                if (!this.socket || this.socket.readyState === WebSocket.CLOSED) {
                    console.log("🚀 WSClient: Tab became visible, reconnecting...");
                    this.connect();
                } else if (this.socket.readyState === WebSocket.OPEN) {
                    // Send an immediate ping to verify the "zombie" connection
                    this.socket.send(JSON.stringify({ method: 'ping' }));
                }
            }
        });
    }

    /**
     * Routes incoming messages and dispatches browser events
     */
    handleIncoming(res) {
        // 1. Handle Internal Pong (No UI action needed)
        if (res.type === 'pong') {
            console.debug("WSClient: Heartbeat Acknowledged", res.time);
            return;
        }

        // 2. Handle PHP Framework Errors
        if (res.status === 'error') {
            console.error(`WSClient: Server Error [${res.code}] - ${res.message}`);
            this.dispatchGlobalEvent('ws_error', res);
            return;
        }

        // 3. Dispatch Controller Events
        // PHP Controllers should return ['type' => 'event_name', 'data' => [...]]
        const eventName = res.type ? `ws_${res.type}` : 'ws_message';
        this.dispatchGlobalEvent(eventName, res.data || res);
    }

    /**
     * Helper to dispatch standard JS events
     */
    dispatchGlobalEvent(name, detail = {}) {
        const event = new CustomEvent(name, { detail: detail });
        window.dispatchEvent(event);
    }
}

export default WSClient;

