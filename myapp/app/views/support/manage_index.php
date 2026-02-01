<div class="w-full px-4 md:px-8 pb-10">
    <div class="flex h-[750px] bg-[#151a21] border border-white/5 rounded-2xl shadow-xl overflow-hidden">
        <aside class="w-80 border-r border-white/5 flex flex-col bg-black/20">
            <div class="p-5 border-b border-white/5">
                <input type="text" id="user-search"
                    placeholder="<?php echo _t('search_customer'); ?>..."
                    class="w-full bg-black/40 border border-white/10 rounded-xl py-2 px-4 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div id="chat-list" class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1"></div>
        </aside>

        <section class="flex-1 flex flex-col bg-black/5">
            <div id="chat-box" class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                <div class="h-full flex flex-col items-center justify-center text-gray-600 opacity-50">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke-width="1.5"></path>
                    </svg>
                    <p class="text-xs font-bold uppercase tracking-widest"><?php echo _t('select_user_to_start'); ?></p>
                </div>
            </div>

            <div id="pwo-preview" class="hidden px-6 py-2 bg-blue-500/10 border-t border-white/5 text-[10px] text-blue-400">
                <span id="pwo-filename"></span>
                <button id="pwo-clear" class="ml-2 text-white">✕</button>
            </div>
            <div id="pwo-voice-ui" class="hidden px-6 py-2 bg-red-500/10 text-red-500 text-[10px]">
                Recording... <span id="pwo-voice-time">00:00</span>
            </div>

        <footer class="p-6 border-t border-white/5 bg-black/20">
            <div class="flex items-center gap-4 bg-black/40 border border-white/10 rounded-2xl p-2 pl-4 min-h-[56px]">
                
                <input type="file" id="pwo-file-input" class="hidden">
                
                <textarea id="chat-in" rows="1"
                    placeholder="<?php echo _t('type_message'); ?>..."
                    class="flex-1 bg-transparent border-none outline-none text-sm text-white py-2 resize-none leading-tight self-center"></textarea>
                
                <div class="flex items-center gap-3 pr-2">
                    <button id="pwo-attach" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    </button>
                    
                    <button id="pwo-mic" class="text-gray-500 hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"></path></svg>
                    </button>
                </div>
        
                <button id="chat-send"
                    class="bg-[#4d7cfe] text-white p-2.5 rounded-xl hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </div>
        </footer>
        </section>
    </div>
</div>

<script type="module"
	src="<?php echo getUrl('assets/manage/admin-support.js') ?>"></script>
