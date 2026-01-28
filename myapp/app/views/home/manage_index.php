<div class="w-full px-4 md:px-8 pb-10">
    
    <div class="mb-8 flex flex-col gap-1">        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-3xl font-black tracking-tight text-white">Dashboard</h2>
            
            <div class="flex bg-black/40 p-1 rounded-xl border border-white/10">
                <button onclick="switchTab('stats')" id="tab-btn-stats" class="tab-btn active-tab px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                    Market Summary
                </button>
                <button onclick="switchTab('activity')" id="tab-btn-activity" class="tab-btn inactive-tab px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                    Live Operations
                </button>
                <button onclick="switchTab('analytics')" id="tab-btn-analytics" class="tab-btn inactive-tab px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                    Risk & Compliance
                </button>
            </div>
        </div>
    </div>

    <hr class="border-white/10 mb-10">

    <div id="tab-stats" class="tab-content block">
        <div class="mb-6 flex items-center gap-2">
            <div class="h-4 w-1 bg-blue-500 rounded-full"></div>
            <h4 class="text-xs font-bold text-white uppercase tracking-widest">Global Liquidity & FX</h4>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">NPR Settlement</p>
                <h3 class="text-2xl font-black text-white">रू 45.2M</h3>
                <div class="mt-4 h-1.5 bg-black/40 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 shadow-[0_0_8px_#10b981]" style="width: 85%"></div>
                </div>
                <p class="text-[9px] text-emerald-400 mt-2 font-bold uppercase">Healthy Status</p>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">USD Funding</p>
                <h3 class="text-2xl font-black text-white">$128,400</h3>
                <div class="mt-4 h-1.5 bg-black/40 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 shadow-[0_0_8px_#f59e0b]" style="width: 35%"></div>
                </div>
                <p class="text-[9px] text-amber-400 mt-2 font-bold uppercase">Low Balance Alert</p>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Active Senders</p>
                <h3 class="text-2xl font-black text-white">1,240</h3>
                <p class="text-[9px] text-blue-400 mt-2 font-bold uppercase">+5.2% Growth</p>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Avg. Payout Time</p>
                <h3 class="text-2xl font-black text-white">4.2 <span class="text-sm font-normal text-gray-400">min</span></h3>
                <p class="text-[9px] text-gray-500 mt-2 font-bold uppercase">Instant Corridor</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-[#151a21] border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                    <h4 class="text-[10px] font-bold text-white uppercase tracking-widest">Compliance Alerts</h4>
                    <span class="px-2 py-0.5 bg-red-500/10 text-red-500 text-[9px] font-bold rounded">3 ACTION REQUIRED</span>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-red-500/5 border border-red-500/10 rounded-xl">
                        <span class="text-xs text-gray-200">#KYC-8842 - Sanction Hit</span>
                        <button class="text-[9px] font-bold uppercase text-red-400">Review</button>
                    </div>
                </div>
            </div>

            <div class="bg-[#151a21] border border-white/10 rounded-2xl p-6 shadow-xl">
                <h4 class="text-[10px] font-bold text-white uppercase tracking-widest mb-6">Top Corridors (Today)</h4>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-400">UAE → NEPAL</span>
                        <span class="text-white font-bold">$42,500</span>
                    </div>
                    <div class="w-full h-1 bg-black/40 rounded-full"><div class="h-full bg-blue-500 w-[80%]"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-activity" class="tab-content hidden">
        <div class="bg-[#151a21] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                <h4 class="text-xs font-bold text-white uppercase tracking-widest">Live Security Logs</h4>
            </div>
            <div class="divide-y divide-white/5">
                <div class="px-6 py-5 flex items-center gap-4 hover:bg-white/[0.01] transition-colors">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse shadow-[0_0_10px_#4d7cfe]"></div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-200">User <span class="text-blue-400 font-bold">Admin</span> updated Partner Configuration #2</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase mt-1">Just Now</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-analytics" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-[#151a21] border border-white/10 p-8 rounded-2xl shadow-xl">
                <h4 class="text-xs font-bold text-white uppercase tracking-widest mb-8">Resource Usage</h4>
                <div class="space-y-6">
                    <div class="space-y-2">
                        <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                            <span>CPU Usage</span>
                            <span class="text-blue-400">32%</span>
                        </div>
                        <div class="w-full h-1.5 bg-black/40 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 shadow-[0_0_8px_#4d7cfe]" style="width: 32%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ensure styles are always loaded */
    .active-tab { background-color: #4d7cfe !important; color: white !important; box-shadow: 0 4px 20px rgba(77, 124, 254, 0.3); }
    .inactive-tab { color: #9ca3af; }
    .inactive-tab:hover { color: white; background-color: rgba(255, 255, 255, 0.05); }
    .tab-content.hidden { display: none !important; }
    .tab-content.block { display: block !important; animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
    // Fixed: Standardized function to handle tab switching
    function switchTab(tabId) {
        // 1. Target all content containers and hide them
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => {
            content.classList.remove('block');
            content.classList.add('hidden');
        });

        // 2. Show the specific content
        const activeContent = document.getElementById('tab-' + tabId);
        if(activeContent) {
            activeContent.classList.remove('hidden');
            activeContent.classList.add('block');
        }

        // 3. Update button styling
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            btn.classList.remove('active-tab');
            btn.classList.add('inactive-tab');
        });

        const activeBtn = document.getElementById('tab-btn-' + tabId);
        if(activeBtn) {
            activeBtn.classList.add('active-tab');
            activeBtn.classList.remove('inactive-tab');
        }
    }
</script>