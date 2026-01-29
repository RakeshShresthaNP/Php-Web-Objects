<div class="w-full px-4 md:px-8 pb-10">
    <div class="mb-8 flex flex-col gap-1">
        <nav class="flex items-center gap-2 text-[10px] uppercase font-bold tracking-[0.15em]">
            <a href="<?php echo getUrl('manage/dashboard') ?>" class="text-gray-400 hover:text-[#4d7cfe] transition-colors">Admin</a>
            <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <span class="text-white">Support Desk</span>
        </nav>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-3xl font-black tracking-tight text-white">Live Resolution</h2>
            <div id="pwo-typing" class="hidden px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-500 text-[10px] font-black uppercase animate-pulse">
                Customer is typing...
            </div>
        </div>
    </div>

    <hr class="border-white/5 mb-10">

    <div class="flex h-[750px] bg-[#151a21] border border-white/5 rounded-2xl shadow-xl overflow-hidden">
        
        <aside class="w-80 border-r border-white/5 flex flex-col bg-black/20">
            <div class="p-5 border-b border-white/5">
                <input type="text" id="user-search" placeholder="Search customer..." class="w-full bg-black/40 border border-white/10 rounded-xl py-2 px-4 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div id="chat-list" class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                </div>
        </aside>

        <section class="flex-1 flex flex-col bg-black/5">
            <div id="message-flow" class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                <div class="h-full flex flex-col items-center justify-center text-gray-600 opacity-50">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke-width="1.5"></path></svg>
                    <p class="text-xs font-bold uppercase tracking-widest">Select a user to start</p>
                </div>
            </div>

            <footer class="p-6 border-t border-white/5 bg-black/20">
                <div class="flex items-center gap-4 bg-black/40 border border-white/10 rounded-2xl p-2 pl-4">
                    <input type="text" id="admin-input" placeholder="Type message..." class="flex-1 bg-transparent border-none outline-none text-sm text-white py-2">
                    <button id="send-btn" class="bg-[#4d7cfe] text-white p-2.5 rounded-xl hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </div>
            </footer>
        </section>
    </div>
</div>

<script type="module" src="<?php echo getUrl('assets/manage/admin-support.js') ?>"></script>
