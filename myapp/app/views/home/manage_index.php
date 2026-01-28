<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 bg-[#151a21] border border-white/5 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg">Monthly Sales</h3>
            <select class="bg-[#0b0e14] border border-white/10 text-xs rounded-lg px-2 py-1 outline-none focus:border-blue-500">
                <option>This Year</option>
                <option>Last Year</option>
            </select>
        </div>
        
        <div class="h-64 flex items-end justify-between gap-2 px-2">
            <div class="flex-1 bg-blue-600/20 rounded-t-lg relative group h-[40%]">
                <div class="absolute inset-x-0 bottom-0 bg-blue-500 rounded-t-lg transition-all group-hover:bg-blue-400 h-[60%]"></div>
            </div>
            <div class="flex-1 bg-blue-600/20 rounded-t-lg relative group h-[60%]">
                <div class="absolute inset-x-0 bottom-0 bg-blue-500 rounded-t-lg transition-all group-hover:bg-blue-400 h-[80%]"></div>
            </div>
            <div class="flex-1 bg-blue-600/20 rounded-t-lg relative group h-[85%]">
                <div class="absolute inset-x-0 bottom-0 bg-blue-500 rounded-t-lg transition-all group-hover:bg-blue-400 h-[70%]"></div>
            </div>
            </div>
    </div>

    <div class="bg-[#151a21] border border-white/5 rounded-2xl p-6 flex flex-col items-center justify-center shadow-sm">
        <h3 class="font-bold text-lg self-start mb-8">Monthly Target</h3>
        
        <div class="relative flex items-center justify-center">
            <svg class="w-48 h-48 transform -rotate-90">
                <circle cx="96" cy="96" r="80" stroke="currentColor" stroke-width="12" fill="transparent" class="text-white/5" />
                <circle cx="96" cy="96" r="80" stroke="currentColor" stroke-width="12" fill="transparent" 
                        stroke-dasharray="502.4" 
                        stroke-dashoffset="125.6" 
                        stroke-linecap="round"
                        class="text-blue-500 transition-all duration-1000" />
            </svg>
            
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-4xl font-black tracking-tight">75%</span>
                <span class="text-[10px] uppercase text-gray-500 font-bold tracking-widest">Achieved</span>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-8 w-full border-t border-white/5 pt-6">
            <div class="text-center">
                <p class="text-2xl font-bold">$12.5k</p>
                <p class="text-[10px] uppercase text-gray-500 font-bold">Earned</p>
            </div>
            <div class="text-center border-l border-white/5">
                <p class="text-2xl font-bold">$16.0k</p>
                <p class="text-[10px] uppercase text-gray-500 font-bold">Goal</p>
            </div>
        </div>
    </div>

</div>