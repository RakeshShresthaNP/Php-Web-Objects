/**
 * pwo-templates.js
 * Contains the CSS styles and HTML structure for the PWO Chat Widget
 */

export const PWO_STYLES = `
<style>
    #pwo-window { z-index: 9999; display: none; }
    #pwo-bubble { z-index: 9999; }
    
    /* --- CHROME/SAFARI CLIPPING FIXES --- */
    #chat-box {
        overflow-y: auto !important;
        overflow-x: hidden !important; 
        padding: 20px 10px !important; /* Controlled padding for scrollbar room */
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f9fafb;
        /* FIX: This prevents children from stretching to 100% width */
        align-items: flex-start; 
    }

    /* Force your messages to the right side */
    .msg-me-container { 
        align-self: flex-end !important; 
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        width: 100%;
    }

    .relative.group {
        overflow: visible !important;
        position: relative;
        width: fit-content; /* Shrinks the hit-box to the bubble size */
    }

    /* --- DELETE BUTTON LOGIC --- */
    .pwo-delete-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 22px;
        height: 22px;
        background: #ff4444;
        color: white;
        border-radius: 50%;
        border: 2px solid white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 9999 !important;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease-in-out;
        pointer-events: auto !important;
    }

    .group:hover .pwo-delete-btn {
        opacity: 1 !important;
        visibility: visible !important;
        transform: scale(1.1);
    }

    /* --- MESSAGE BODY --- */
    .msg-body { 
        position: relative !important;
        overflow: visible !important; 
        width: fit-content !important; /* Force shrink wrap */
        max-width: 260px; /* Limits width on larger screens */
        word-break: break-word;
        white-space: pre-wrap; 
        z-index: 10;
    }
    
    /* Main Chat Scrollbar - Visible in Chrome */
    #chat-box::-webkit-scrollbar { 
        width: 5px !important; 
        display: block !important; 
    }
    #chat-box::-webkit-scrollbar-thumb { 
        background: #d1d5db !important; 
        border-radius: 10px; 
    }
    #chat-box::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Recording Animation */
    .rec-active { color: #ef4444 !important; animation: pulse 1.5s infinite; }
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>
`;

export const PWO_HTML = `
<div id="pwo-bubble" class="fixed bottom-6 right-6 w-14 h-14 bg-emerald-600 rounded-full shadow-2xl flex items-center justify-center cursor-pointer hover:scale-110 transition-transform">
    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
    </svg>
    <div id="pwo-dot" class="absolute top-0 right-0 w-4 h-4 bg-gray-400 border-2 border-white rounded-full"></div>
</div>

<div id="pwo-window" class="fixed bottom-24 right-6 w-[380px] h-[550px] bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-gray-100">
    
    <div class="bg-emerald-600 p-4 flex items-center justify-between text-white">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold">AI</div>
            <div>
                <h3 class="font-bold text-sm">Support Assistant</h3>
                <p id="pwo-status" class="text-[10px] opacity-80">Connecting...</p>
            </div>
        </div>
        <div class="flex gap-1 items-center">
            <input id="pwo-search-input" type="text" placeholder="Search..." class="hidden w-20 bg-white/20 text-[10px] rounded px-2 py-1 outline-none border-none placeholder-white/70 text-white transition-all">
            
            <button id="pwo-search-toggle" title="Search Messages" class="hover:bg-white/10 p-1 rounded">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>

            <button id="pwo-export" title="Export Chat" class="hover:bg-white/10 p-1 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            </button>
            <button id="pwo-logout" title="Logout" class="hover:bg-white/10 p-1 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </button>
            <button id="pwo-close" class="hover:bg-white/10 p-1 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    <div id="chat-box"></div>

    <div id="pwo-typing" class="hidden px-4 py-1 text-[10px] text-gray-400 italic">Agent is typing...</div>
    
    <div id="pwo-rec-panel" class="hidden bg-emerald-50 p-3 border-t border-emerald-100 flex flex-col items-center">
        <canvas id="pwo-waveform" width="300" height="40" class="w-full h-10 mb-1"></canvas>
        <span id="pwo-timer" class="text-[10px] font-mono text-emerald-600 font-bold">● 0:00</span>
    </div>

    <div id="pwo-preview" class="hidden bg-white p-2 border-t flex items-center justify-between">
        <div class="flex items-center gap-2 overflow-hidden">
            <span class="text-xs text-emerald-600 font-medium truncate" id="pwo-filename">file.jpg</span>
        </div>
        <button id="pwo-clear" class="text-gray-400 hover:text-red-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    
    <div id="pwo-progress-container" class="hidden w-full h-1 bg-gray-200">
        <div id="pwo-progress-bar" class="h-full bg-emerald-500 transition-all duration-150" style="width: 0%"></div>
    </div>

    <div class="p-3 bg-white border-t flex items-end gap-2">
        <button id="pwo-attach" class="p-2 text-gray-400 hover:text-emerald-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
        </button>
        <input type="file" id="pwo-file-input" class="hidden">
        
        <div class="flex-1 relative">
            <textarea id="chat-in" rows="1" placeholder="Type a message..." class="w-full p-2 max-h-32 bg-gray-100 border-none rounded-2xl text-sm focus:ring-2 focus:ring-emerald-500 resize-none outline-none"></textarea>
        </div>

        <button id="pwo-mic" class="p-2 text-gray-400 hover:text-emerald-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
        </button>
        
        <button id="chat-send" class="p-2 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 shadow-md">
            <svg class="w-5 h-5 rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
        </button>
    </div>

    <div id="pwo-auth-overlay" class="absolute inset-0 bg-white/95 z-[100] hidden flex flex-col items-center justify-center p-8 text-center">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <h4 class="text-lg font-bold text-gray-800 mb-2">Login to Chat</h4>
        <input type="text" id="pwo-user" placeholder="Username" class="w-full p-2 mb-2 border rounded-lg text-sm">
        <input type="password" id="pwo-pass" placeholder="Password" class="w-full p-2 mb-4 border rounded-lg text-sm">
        <button id="pwo-do-login" class="w-full py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700">Continue</button>
    </div>
</div>
`;
