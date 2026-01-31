<script src="<?php echo getUrl('assets/manage/chart.js'); ?>"></script>

<div class="w-full px-4 md:px-8 pb-10">

	<div class="mb-8 flex flex-col gap-1">
		<div
			class="flex flex-col md:flex-row md:items-center justify-between gap-4">
			<div>
				<h2 class="text-3xl font-black tracking-tight text-white"><?php echo _t('dashboard'); ?></h2>
			</div>

			<div
				class="flex bg-black/40 p-1 rounded-xl border border-white/10 shadow-2xl">
				<button onclick="switchTab('stats')" id="tab-btn-stats"
					class="tab-btn active-tab px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                    <?php echo _t('market_summary'); ?>
                </button>
				<button onclick="switchTab('activity')" id="tab-btn-activity"
					class="tab-btn inactive-tab px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                    <?php echo _t('live_operations'); ?>
                </button>
				<button onclick="switchTab('analytics')" id="tab-btn-analytics"
					class="tab-btn inactive-tab px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                    <?php echo _t('risk_compliance'); ?>
                </button>
			</div>
		</div>
	</div>

	<hr class="border-white/10 mb-10">

	<div id="tab-stats" class="tab-content block space-y-12">

		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
			<div
				class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
				<p
					class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1"><?php echo _t('npr_settlement'); ?></p>
				<h3 class="text-2xl font-black text-white"><?php echo _t('currency_npr'); ?> 45.2M</h3>
				<div class="mt-4 h-1.5 bg-black/40 rounded-full overflow-hidden">
					<div class="h-full bg-emerald-500 shadow-[0_0_8px_#10b981]"
						style="width: 85%"></div>
				</div>
				<p
					class="text-[9px] text-emerald-400 mt-2 font-bold uppercase tracking-tighter italic"><?php echo _t('healthy_liquidity'); ?></p>
			</div>

			<div
				class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
				<p
					class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1"><?php echo _t('available_lending_pool'); ?></p>
				<h3 class="text-2xl font-black text-purple-400"><?php echo _t('currency_npr'); ?> 12.5M</h3>
				<p
					class="text-[9px] text-gray-500 mt-2 font-bold uppercase tracking-tighter">482 <?php echo _t('active_credit_lines'); ?></p>
			</div>

			<div
				class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl border-l-amber-500/30">
				<p
					class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1"><?php echo _t('usd_funding'); ?></p>
				<h3 class="text-2xl font-black text-amber-500">$128,400</h3>
				<div class="mt-4 h-1.5 bg-black/40 rounded-full overflow-hidden">
					<div class="h-full bg-amber-500 shadow-[0_0_8px_#f59e0b]"
						style="width: 35%"></div>
				</div>
				<p class="text-[9px] text-amber-400 mt-2 font-bold uppercase"><?php echo _t('low_balance_alert'); ?></p>
			</div>

			<div
				class="bg-[#151a21] border border-white/10 p-6 rounded-2xl shadow-xl">
				<p
					class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1"><?php echo _t('total_earnings_mtd'); ?></p>
				<h3 class="text-2xl font-black text-white"><?php echo _t('currency_npr'); ?> 1.2M</h3>
				<p
					class="text-[9px] text-blue-400 mt-2 font-bold uppercase tracking-tighter">+12.4% <?php echo _t('vs_last_month'); ?></p>
			</div>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
			<div
				class="lg:col-span-2 bg-[#151a21] border border-white/10 p-8 rounded-2xl shadow-xl">
				<div class="flex justify-between items-center mb-8">
					<h4
						class="text-[10px] font-bold text-white uppercase tracking-widest"><?php echo _t('revenue_flow_chart_title'); ?></h4>
					<select
						class="bg-black/20 border border-white/10 text-[9px] text-gray-400 rounded-lg px-3 py-1 outline-none">
						<option><?php echo _t('last_7_days'); ?></option>
						<option><?php echo _t('last_30_days'); ?></option>
					</select>
				</div>
				<div class="h-[300px] w-full">
					<canvas id="revenueChart"></canvas>
				</div>
			</div>

			<div
				class="bg-[#151a21] border border-white/10 p-8 rounded-2xl shadow-xl">
				<h4
					class="text-[10px] font-bold text-white uppercase tracking-widest mb-8"><?php echo _t('bank_gateway_health'); ?></h4>
				<div class="space-y-6">
					<div class="flex justify-between items-center">
						<span class="text-xs text-gray-300 font-bold">Global IME Bank</span>
						<div class="flex items-center gap-3">
							<span class="text-[9px] text-emerald-500 font-black"><?php echo _t('status_active'); ?></span>
							<div
								class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_#10b981]"></div>
						</div>
					</div>
					<div class="flex justify-between items-center">
						<span class="text-xs text-gray-300 font-bold">Nabil Connect</span>
						<div class="flex items-center gap-3">
							<span class="text-[9px] text-emerald-500 font-black"><?php echo _t('status_active'); ?></span>
							<div
								class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_#10b981]"></div>
						</div>
					</div>
					<div class="flex justify-between items-center">
						<span class="text-xs text-gray-300 font-bold">NIC Asia Bridge</span>
						<div class="flex items-center gap-3">
							<span class="text-[9px] text-amber-500 font-black"><?php echo _t('status_slow'); ?></span>
							<div
								class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_#f59e0b]"></div>
						</div>
					</div>
				</div>
				<div
					class="mt-10 p-4 bg-white/[0.02] border border-white/5 rounded-xl">
					<p class="text-[9px] text-gray-500 uppercase font-bold mb-1"><?php echo _t('last_sync'); ?></p>
					<p class="text-xs text-gray-300 italic"><?php echo _t('just_now_latency'); ?></p>
				</div>
			</div>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
			<div
				class="bg-[#151a21] border border-white/10 rounded-2xl overflow-hidden shadow-xl">
				<div
					class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
					<h4
						class="text-[10px] font-bold text-white uppercase tracking-widest"><?php echo _t('top_payout_agents'); ?></h4>
				</div>
				<table class="w-full text-left text-xs">
					<tbody class="divide-y divide-white/5">
						<tr class="hover:bg-white/[0.02] transition-colors">
							<td class="px-6 py-4 font-bold text-gray-200">Kathmandu Central
								Hub</td>
							<td class="px-6 py-4 text-gray-400">1,240 <?php echo _t('txns_count'); ?></td>
							<td
								class="px-6 py-4 text-right text-blue-400 font-black tracking-tight"><?php echo _t('currency_npr'); ?> 42,000</td>
						</tr>
						<tr class="hover:bg-white/[0.02] transition-colors">
							<td class="px-6 py-4 font-bold text-gray-200">Pokhara Payout
								Center</td>
							<td class="px-6 py-4 text-gray-400">842 <?php echo _t('txns_count'); ?></td>
							<td
								class="px-6 py-4 text-right text-blue-400 font-black tracking-tight"><?php echo _t('currency_npr'); ?> 28,150</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div
				class="bg-[#151a21] border border-red-500/10 p-6 rounded-2xl shadow-xl border-l-red-500/20">
				<h4
					class="text-[10px] font-bold text-white uppercase tracking-widest mb-6 flex items-center gap-2">
					<i class='bx bx-lock-alt text-red-500'></i> <?php echo _t('security_audit_trail'); ?>
                </h4>
				<div class="space-y-3">
					<div
						class="flex justify-between items-center text-[10px] p-3 bg-red-500/5 border border-red-500/10 rounded-xl">
						<span class="text-gray-300 font-bold uppercase tracking-tight">[<?php echo _t('failed_login'); ?>] Admin User (103.2.1.4)</span>
						<span class="text-gray-500 italic">2m <?php echo _t('time_ago'); ?></span>
					</div>
					<div
						class="flex justify-between items-center text-[10px] p-3 bg-white/5 border border-white/5 rounded-xl">
						<span class="text-gray-300 font-bold uppercase tracking-tight">[<?php echo _t('pass_reset'); ?>] Payout Agent #12</span>
						<span class="text-gray-500 italic">45m <?php echo _t('time_ago'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
