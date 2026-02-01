<div class="w-full px-4 md:px-8 pb-10">
	<div
		class="flex h-[750px] bg-[#151a21] border border-white/5 rounded-2xl shadow-xl overflow-hidden">
		<aside class="w-80 border-r border-white/5 flex flex-col bg-black/20">
			<div class="p-5 border-b border-white/5">
				<input type="text" id="user-search"
					placeholder="<?php echo _t('search_customer'); ?>..."
					class="w-full bg-black/40 border border-white/10 rounded-xl py-2 px-4 text-xs text-white outline-none focus:border-blue-500">
			</div>
			<div id="chat-list"
				class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1"></div>
		</aside>

		<section class="flex-1 flex flex-col bg-black/5">
			<div id="chat-box"
				class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
				<div
					class="h-full flex flex-col items-center justify-center text-gray-600 opacity-50">
					<svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor"
						viewBox="0 0 24 24">
                        <path
							d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
							stroke-width="1.5"></path>
                    </svg>
					<p class="text-xs font-bold uppercase tracking-widest"><?php echo _t('select_user_to_start'); ?></p>
				</div>
			</div>

			<div id="pwo-preview"
				class="hidden px-6 py-2 bg-blue-500/10 border-t border-white/5 text-[10px] text-blue-400">
				<span id="pwo-filename"></span>
				<button id="pwo-clear" class="ml-2 text-white">✕</button>
			</div>

            <div id="pwo-rec-panel" class="hidden bg-emerald-50 p-3 border-t border-emerald-100 flex flex-col items-center">
                <canvas id="pwo-waveform" width="300" height="40" class="w-full h-10 mb-1"></canvas>
                <span id="pwo-timer" class="text-[10px] font-mono text-emerald-600 font-bold">● 0:00</span>
            </div>
            
            <footer class="p-6 border-t border-white/5 bg-black/20">
                <div class="flex items-center gap-3 bg-black/40 border border-white/10 rounded-2xl p-2 pl-4 min-h-[56px] w-full">
                    
                    <input type="file" id="pwo-file-input" class="hidden">
                    
                    <button id="pwo-attach" class="text-gray-500 hover:text-white transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </button>
            
                    <textarea id="chat-in" rows="1"
                        placeholder="<?php echo _t('type_message'); ?>..."
                        class="flex-1 bg-transparent border-none outline-none text-sm text-white py-3 resize-none leading-tight self-center"></textarea>
                    
                    <div class="flex items-center gap-3 pr-1 flex-shrink-0">
                        <button id="pwo-mic" class="text-gray-500 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 10v1a7 7 0 01-14 0v-1m7 11v-4m-3 4h6"></path>
                            </svg>
                        </button>
            
                        <button id="chat-send"
                            class="bg-[#4d7cfe] text-white p-2.5 rounded-xl hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </footer>
		</section>
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


<script type="module"
	src="<?php echo getUrl('assets/manage/admin-support.js') ?>"></script>