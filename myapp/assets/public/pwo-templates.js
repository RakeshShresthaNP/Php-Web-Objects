/**
 * pwo-templates.js
 * Contains the CSS styles and HTML structure for the PWO Chat Widget
 */

export const PWO_STYLES = `
<style>
    #pwo-window { z-index: 9999; display: none; }
    #pwo-bubble { z-index: 9999; }
    
    /* SCROLL BADGE */
    #pwo-scroll-bottom {
        position: absolute !important;
        bottom: 80px !important;
        right: 20px !important;
        z-index: 50 !important;
        transition: opacity 0.3s ease;
    }

    #chat-box {
        overflow-y: auto !important; 
        overflow-x: hidden !important; 
        scrollbar-gutter: stable; 
        padding: 20px 15px !important;
        flex: 1; 
        display: flex; 
        flex-direction: column;
        background: #f9fafb; 
        gap: 8px;
    }

	.pwo-welcome-card {
	    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	    color: white;
	    padding: 24px 20px;
	    border-radius: 24px;
	    margin: 10px 0 20px 0;
	    text-align: center;
	    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3);
	    animation: welcomeSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
	}

	.pwo-welcome-icon {
	    width: 50px;
	    height: 50px;
	    background: rgba(255, 255, 255, 0.2);
	    backdrop-filter: blur(5px);
	    border-radius: 50%;
	    display: flex;
	    align-items: center;
	    justify-content: center;
	    margin: 0 auto 12px;
	    font-size: 24px;
	}

	@keyframes welcomeSlideUp {
	    from { opacity: 0; transform: translateY(20px); }
	    to { opacity: 1; transform: translateY(0); }
	}
	
    .pwo-msg-container { position: relative !important; overflow: visible !important; }

    .pwo-delete-btn {
        position: absolute !important;
        top: -8px !important;
        right: -8px !important;
        width: 24px !important;
        height: 24px !important;
        background: #ef4444 !important;
        color: white !important;
        border-radius: 9999px !important;
        border: 2px solid white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 60 !important;
        
        /* Hidden by default */
        opacity: 0;
        visibility: hidden;
        transform: scale(0.8);
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    /* Show when hovering over the relative container */
    .pwo-msg-container:hover .pwo-delete-btn {
        opacity: 1;
        visibility: visible;
        transform: scale(1);
    }

    .pwo-delete-btn:hover {
        background: #dc2626 !important;
        transform: scale(1.15) !important;
    }

    /* SEARCH NAV & HIGHLIGHTS */
    #pwo-search-nav {
        display: flex; gap: 8px; align-items: center; justify-content: center;
        padding: 10px; background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px); border-bottom: 1px solid #e2e8f0;
    }
    .search-mark { background-color: #fde047 !important; color: #000000 !important; font-weight: bold !important; }

    /* SCROLLBARS */
    #chat-box::-webkit-scrollbar { width: 6px !important; }
    #chat-box::-webkit-scrollbar-thumb { background: #cbd5e1 !important; border-radius: 10px; }
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
			<div id="search-nav" class="hidden absolute top-14 left-0 right-0 mx-4 p-2 bg-white/95 backdrop-blur shadow-lg border rounded-xl items-center gap-3 z-50">
			    <div class="flex-1 px-2 border-r text-sm text-gray-600 truncate">Searching...</div>
			    <span id="search-count" class="text-xs font-mono bg-gray-100 px-2 py-1 rounded">0/0</span>
			    <div class="flex gap-1">
			        <button id="btn-prev" type="button" class="p-1 hover:bg-gray-200 rounded">▲</button>
			        <button id="btn-next" type="button" class="p-1 hover:bg-gray-200 rounded">▼</button>
			    </div>
			    <button id="btn-close-search" type="button" class="text-gray-400 hover:text-red-500">✕</button>
			</div>
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

    <div id="chat-box" class="flex-1 overflow-y-auto"></div>

    <div id="pwo-typing" class="hidden px-4 py-2 flex items-center gap-2 text-gray-400 text-xs bg-white">
        <div class="flex gap-1">
            <span class="w-1 h-1 bg-gray-400 rounded-full animate-bounce"></span>
            <span class="w-1 h-1 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
            <span class="w-1 h-1 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
        </div>
        <span class="italic font-medium text-[10px]">Agent is typing...</span>
    </div>

    <div id="pwo-progress-container" class="hidden w-full h-1 bg-gray-100">
        <div id="pwo-progress-bar" class="h-full bg-emerald-500 transition-all duration-300" style="width: 0%"></div>
    </div>
    
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
	
    <div class="p-3 bg-white border-t relative">
        <div id="pwo-emoji-picker" class="hidden absolute bottom-full left-4 mb-2 bg-white border rounded-lg shadow-xl p-2 grid grid-cols-6 gap-2 z-[10000] w-48">
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">😀</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">😂</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">😍</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">👍</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">🔥</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">🙌</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">❤️</span>
            <span class="cursor-pointer hover:bg-gray-100 p-1 rounded text-center">✨</span>
        </div>

		<div class="flex items-end gap-2 bg-gray-100 rounded-2xl px-3 py-2">
		    <button id="pwo-emoji-btn" type="button" class="text-gray-500 hover:text-emerald-600 pb-1">😀</button>
		    
		    <button id="pwo-attach" class="text-emerald-700 pb-1">
		        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
		            <path stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
		        </svg>
		    </button>
			<input type="file" id="pwo-file-input" class="hidden" accept="image/*,video/*,.pdf">
		    
			<textarea id="chat-in" rows="1" placeholder="Type a message..." 
			                class="flex-1 bg-transparent border-none focus:ring-0 text-sm resize-none py-1"
			                style="height: 36px; line-height: 20px; outline: none; border: none;"></textarea>

		    <button id="pwo-mic" class="text-emerald-700 pb-1">
		        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
		            <path stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
		        </svg>
		    </button>
		    
		    <button id="chat-send" class="text-emerald-700 pb-1">
		        <svg class="w-5 h-5 rotate-90" fill="currentColor" viewBox="0 0 20 20">
		            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
		        </svg>
		    </button>
		</div>
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
<div id="pwo-lightbox" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[9999] flex flex-col items-center justify-center p-4">
    <div class="absolute top-5 right-5 flex gap-4">
        <a id="pwo-lightbox-download" href="" download class="text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition-colors" title="Download">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2"></path></svg>
        </a>
        <button id="pwo-lightbox-close" class="text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"></path></svg>
        </button>
    </div>
    <img id="pwo-lightbox-img" src="" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl object-contain cursor-zoom-out">
</div>	
<div id="pdf-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[9999] flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-5xl h-[90vh] bg-white rounded-2xl overflow-hidden flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="font-bold text-gray-700">PDF Viewer</h3>
            <button onclick="document.getElementById('pdf-modal').classList.add('hidden')" class="text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
        </div>
        <div class="flex-1 w-full h-full">
            <iframe id="pdf-frame" src="" class="w-full h-full border-none"></iframe>
        </div>
    </div>
</div>
<div id="video-modal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-md z-[10000] flex items-center justify-center p-4">
    <button onclick="window.closeVideoModal()" class="absolute top-6 right-6 text-white/70 hover:text-white text-4xl">&times;</button>
    
    <div class="w-full max-w-5xl h-auto max-h-[85vh] flex items-center justify-center">
        <video id="modal-video-player" controls class="w-full h-full rounded-xl shadow-2xl">
            <source src="" type="video/mp4">
        </video>
    </div>
</div>
<div id="pwo-scroll-bottom" class="hidden absolute bottom-20 right-6 bg-emerald-600 text-white text-[10px] font-bold py-1.5 px-3 rounded-full shadow-lg cursor-pointer z-50 animate-bounce">
    New Message ↓
</div>
`;
