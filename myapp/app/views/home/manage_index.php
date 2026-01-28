<script src="<?php echo getUrl('assets/manage/chart.js'); ?>"></script>

<div class="w-full px-4 md:px-8 pb-10">
    
    <div class="mb-8 flex flex-col gap-1">        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black tracking-tight text-white">Dashboard</h2>
            </div>
            
            <div class="flex bg-black/40 p-1 rounded-xl border border-white/10 shadow-2xl">
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

    <div id="tab-stats" class="tab-content block space-y-12">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">NPR Settlement</p>
                <h3 class="text-2xl font-black text-white">रू 45.2M</h3>
                <div class="mt-4 h-1.5 bg-black/40 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 shadow-[0_0_8px_#10b981]" style="width: 85%"></div>
                </div>
                <p class="text-[9px] text-emerald-400 mt-2 font-bold uppercase tracking-tighter italic">Healthy Liquidity</p>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Available Lending Pool</p>
                <h3 class="text-2xl font-black text-purple-400">रू 12.5M</h3>
                <p class="text-[9px] text-gray-500 mt-2 font-bold uppercase tracking-tighter">482 Active Credit Lines</p>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl border-l-amber-500/30">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">USD Funding</p>
                <h3 class="text-2xl font-black text-amber-500">$128,400</h3>
                <div class="mt-4 h-1.5 bg-black/40 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 shadow-[0_0_8px_#f59e0b]" style="width: 35%"></div>
                </div>
                <p class="text-[9px] text-amber-400 mt-2 font-bold uppercase">Low Balance Alert</p>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Total Earnings (MTD)</p>
                <h3 class="text-2xl font-black text-white">रू 1.2M</h3>
                <p class="text-[9px] text-blue-400 mt-2 font-bold uppercase tracking-tighter">+12.4% vs Last Month</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-[#151a21] border border-white/10 p-8 rounded-2xl shadow-xl">
                <div class="flex justify-between items-center mb-8">
                    <h4 class="text-[10px] font-bold text-white uppercase tracking-widest">Revenue Flow (FX + Loan Interest)</h4>
                    <select class="bg-black/20 border border-white/10 text-[9px] text-gray-400 rounded-lg px-3 py-1 outline-none">
                        <option>Last 7 Days</option>
                        <option>Last 30 Days</option>
                    </select>
                </div>
                <div class="h-[300px] w-full">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="bg-[#151a21] border border-white/10 p-8 rounded-2xl shadow-xl">
                <h4 class="text-[10px] font-bold text-white uppercase tracking-widest mb-8">Bank Gateway Health</h4>
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-300 font-bold">Global IME Bank</span>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] text-emerald-500 font-black">ACTIVE</span>
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_#10b981]"></div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-300 font-bold">Nabil Connect</span>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] text-emerald-500 font-black">ACTIVE</span>
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_#10b981]"></div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-300 font-bold">NIC Asia Bridge</span>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] text-amber-500 font-black">SLOW</span>
                            <div class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_#f59e0b]"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-10 p-4 bg-white/[0.02] border border-white/5 rounded-xl">
                    <p class="text-[9px] text-gray-500 uppercase font-bold mb-1">Last Sync</p>
                    <p class="text-xs text-gray-300 italic">Just Now (0.042ms latency)</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-[#151a21] border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                    <h4 class="text-[10px] font-bold text-white uppercase tracking-widest">Top Payout Agents</h4>
                </div>
                <table class="w-full text-left text-xs">
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-200">Kathmandu Central Hub</td>
                            <td class="px-6 py-4 text-gray-400">1,240 Txns</td>
                            <td class="px-6 py-4 text-right text-blue-400 font-black tracking-tight">रू 42,000</td>
                        </tr>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-200">Pokhara Payout Center</td>
                            <td class="px-6 py-4 text-gray-400">842 Txns</td>
                            <td class="px-6 py-4 text-right text-blue-400 font-black tracking-tight">रू 28,150</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-[#151a21] border border-red-500/10 p-6 rounded-2xl shadow-xl border-l-red-500/20">
                <h4 class="text-[10px] font-bold text-white uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class='bx bx-lock-alt text-red-500'></i> Security Audit Trail
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-[10px] p-3 bg-red-500/5 border border-red-500/10 rounded-xl">
                        <span class="text-gray-300 font-bold uppercase tracking-tight">[FAILED LOGIN] Admin User (103.2.1.4)</span>
                        <span class="text-gray-500 italic">2m ago</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] p-3 bg-white/5 border border-white/5 rounded-xl">
                        <span class="text-gray-300 font-bold uppercase tracking-tight">[PASS RESET] Payout Agent #12</span>
                        <span class="text-gray-500 italic">45m ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-activity" class="tab-content hidden">
        <div class="bg-[#151a21] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                <h4 class="text-xs font-bold text-white uppercase tracking-widest">Live Transaction Stream</h4>
            </div>
            <div class="divide-y divide-white/5">
                <div class="px-6 py-5 flex items-center gap-4 hover:bg-white/[0.01] transition-colors">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <div class="flex-1 text-xs">
                        <p class="text-gray-200">Txn <span class="text-blue-400 font-bold">#99210</span> - UAE to Kathmandu processed via Nabil Bank</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase mt-1">Just Now</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-analytics" class="tab-content hidden">
        <div class="bg-[#151a21] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
            <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-white/[0.01]">
                <h4 class="text-xs font-black text-white uppercase tracking-widest">KYC Verification Queue</h4>
                <span class="px-3 py-1 bg-red-500/10 text-red-500 text-[10px] font-black rounded-full border border-red-500/20">24 PENDING</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-black/20 text-[9px] uppercase text-gray-500 tracking-widest">
                        <tr>
                            <th class="px-8 py-4">User</th>
                            <th class="px-8 py-4">Risk Level</th>
                            <th class="px-8 py-4">Submitted</th>
                            <th class="px-8 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-xs">
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-8 py-5 font-bold text-white">Abhishek Karki</td>
                            <td class="px-8 py-5"><span class="px-2 py-0.5 bg-red-500/10 text-red-500 font-bold text-[9px] rounded uppercase border border-red-500/10">High Risk</span></td>
                            <td class="px-8 py-5 text-gray-500 italic">4 mins ago</td>
                            <td class="px-8 py-5 text-right"><button class="text-blue-400 font-black uppercase text-[10px] hover:text-white">Review</button></td>
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-8 py-5 font-bold text-white">Sunita Prajapati</td>
                            <td class="px-8 py-5"><span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-500 font-bold text-[9px] rounded uppercase border border-emerald-500/10">Low Risk</span></td>
                            <td class="px-8 py-5 text-gray-500 italic">12 mins ago</td>
                            <td class="px-8 py-5 text-right"><button class="text-blue-400 font-black uppercase text-[10px] hover:text-white">Review</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* COMPONENT STYLES */
    .active-tab { background-color: #4d7cfe !important; color: white !important; box-shadow: 0 4px 20px rgba(77, 124, 254, 0.3); }
    .inactive-tab { color: #9ca3af; }
    .inactive-tab:hover { color: white; background-color: rgba(255, 255, 255, 0.05); }
    .tab-content.hidden { display: none !important; }
    .tab-content.block { display: block !important; animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
    // 1. TAB SWITCHING LOGIC
    function switchTab(tabId) {
        // Handle Content Visibility
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('block');
            content.classList.add('hidden');
        });
        const activeContent = document.getElementById('tab-' + (tabId === 'analytics' ? 'analytics' : (tabId === 'activity' ? 'activity' : 'stats')));
        if(activeContent) {
            activeContent.classList.remove('hidden');
            activeContent.classList.add('block');
        }

        // Handle Button Styling
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active-tab');
            btn.classList.add('inactive-tab');
        });
        const activeBtn = document.getElementById('tab-btn-' + tabId);
        if(activeBtn) {
            activeBtn.classList.add('active-tab');
            activeBtn.classList.remove('inactive-tab');
        }
    }

    // 2. REVENUE CHART INITIALIZATION
    window.addEventListener('load', () => {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Net Profit (NPR)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                    borderColor: '#4d7cfe',
                    borderWidth: 3,
                    backgroundColor: 'rgba(77, 124, 254, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#4d7cfe'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#666', font: { size: 10, weight: 'bold' } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#666', font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });
    });
</script>