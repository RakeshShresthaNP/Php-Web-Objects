<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo _t($pagename ?? 'dashboard'); ?> | AdminJS</title>
    
    <link rel="stylesheet" href="<?php echo getUrl('assets/manage/tailwind.css'); ?>">
    <link rel="stylesheet" href="<?php echo getUrl('assets/manage/boxicons.min.css'); ?>">
    
    <style>
        .sidebar-transition { transition: width 0.3s ease, transform 0.3s ease; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#0b0e14] text-white font-sans overflow-hidden"> 
    <div class="flex h-screen w-full overflow-hidden">
        
        <aside id="sidebar" class="sidebar-transition fixed inset-y-0 left-0 z-50 bg-[#151a21] border-r border-white/5 flex flex-col shrink-0 w-64 -translate-x-full md:relative md:translate-x-0 md:w-64">
            
            <div class="h-20 flex items-center px-6 gap-3 border-b border-white/5 shrink-0">
                <div class="w-8 h-8 bg-[#4d7cfe] rounded-lg shrink-0 shadow-lg shadow-blue-500/20"></div>
                <span class="sidebar-text text-xl font-bold tracking-tight whitespace-nowrap">AdminJS</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                <a href="#" class="flex items-center gap-3 p-3 bg-blue-600/10 text-[#4d7cfe] rounded-xl group">
                    <i class='bx bxs-dashboard text-xl'></i>
                    <span class="sidebar-text font-bold whitespace-nowrap"><?php echo _t('dashboard'); ?></span>
                </a>
                
                <div class="sidebar-text pt-6 pb-2 px-3 text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em]">
                    <?php echo _t('lending_ops'); ?>
                </div>
                
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-money text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('loan_applications'); ?></span>
                </a>
                
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-calendar-check text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('repayment_schedule'); ?></span>
                </a>    
        
                <div class="sidebar-text pt-6 pb-2 px-3 text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em]">
                    <?php echo _t('operations'); ?>
                </div>
                
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-transfer-alt text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('transactions'); ?></span>
                </a>
                
                <a href="<?php echo getUrl('manage/supportsystem'); ?>" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-user-voice text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('live_support'); ?></span>
                </a>
            
                <div class="sidebar-text pt-6 pb-2 px-3 text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em]">
                    <?php echo _t('treasury'); ?>
                </div>
            
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-trending-up text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('fx_rate_setup'); ?></span>
                </a>
            
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-wallet text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('settlements'); ?></span>
                </a>
            
                <div class="sidebar-text pt-6 pb-2 px-3 text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em]">
                    <?php echo _t('compliance'); ?>
                </div>
            
                <a href="#" class="flex items-center justify-between p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i class='bx bx-shield-quarter text-xl'></i>
                        <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('kyc_queue'); ?></span>
                    </div>
                    <span class="sidebar-text bg-red-500 text-[10px] text-white px-2 py-0.5 rounded-full font-bold">3</span>
                </a>
            
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-error-alt text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('aml_alerts'); ?></span>
                </a>
            
                <div class="sidebar-text pt-6 pb-2 px-3 text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em]">
                    <?php echo _t('management'); ?>
                </div>
                
                <a href="<?php echo getUrl('manage/users'); ?>" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-group text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('users'); ?></span>
                </a>
                
                <a href="#" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
                    <i class='bx bx-cog text-xl'></i>
                    <span class="sidebar-text font-medium whitespace-nowrap"><?php echo _t('system_settings'); ?></span>
                </a>
            </nav>

            <div class="p-4 border-t border-white/5 bg-black/10">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=4d7cfe&color=fff" class="w-9 h-9 rounded-full shrink-0 border border-white/10">
                    <div class="sidebar-text overflow-hidden">
                        <p class="text-sm font-medium truncate text-white">Rakesh S.</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest leading-none"><?php echo _t('administrator'); ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 bg-[#0b0e14] relative h-full">
            
            <header class="h-20 border-b border-white/5 flex items-center justify-between px-4 md:px-8 bg-[#0b0e14]/80 backdrop-blur-xl sticky top-0 z-40 w-full">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition-all cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-lg font-semibold tracking-tight"><?php echo _t($pagename ?? 'dashboard'); ?></h1>
                </div>

                <div class="flex items-center gap-2 sm:gap-4">
                    <div class="relative">
                        <button id="settingsBtn" class="p-2 text-gray-400 hover:text-white transition-colors cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </button>
                        <div id="settingsDropdown" class="hidden absolute right-0 mt-3 w-52 bg-[#1c222d] border border-white/10 rounded-xl shadow-2xl py-2 z-50">
                             <div class="px-4 py-2 border-b border-white/5"><p class="text-xs font-bold uppercase text-gray-500"><?php echo _t('quick_settings'); ?></p></div>
                             <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-[#4d7cfe] hover:text-white transition-all"><?php echo _t('system_config'); ?></a>
                             <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-[#4d7cfe] hover:text-white transition-all"><?php echo _t('theme_options'); ?></a>
                        </div>
                    </div>

                    <div class="relative">
                        <button id="notifButton" class="p-2 text-gray-400 hover:text-white transition-colors relative cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-[#0b0e14]"></span>
                        </button>
                        <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-80 bg-[#1c222d] border border-white/10 rounded-xl shadow-2xl py-2 z-50">
                            <div class="px-4 py-2 border-b border-white/5"><p class="text-xs font-bold uppercase text-gray-500"><?php echo _t('notifications'); ?></p></div>
                            <div class="p-4 text-center text-sm text-gray-500"><?php echo _t('no_new_alerts'); ?></div>
                        </div>
                    </div>

                    <div class="h-8 w-[1px] bg-white/5 mx-1 sm:mx-2"></div>

                    <div class="relative">
                        <button id="avatarButton" class="flex items-center gap-3 cursor-pointer group">
                            <div class="hidden md:block text-right mr-1">
                                <p class="text-xs font-medium text-white group-hover:text-[#4d7cfe]">Rakesh S.</p>
                                <p class="text-[10px] text-green-500"><?php echo _t('online'); ?></p>
                            </div>
                            <img src="https://ui-avatars.com/api/?name=Admin" class="w-10 h-10 rounded-full border border-white/10 group-hover:border-[#4d7cfe]">
                        </button>

                        <div id="avatarDropdown" class="hidden absolute right-0 mt-3 w-52 bg-[#1c222d] border border-white/10 rounded-xl shadow-2xl py-2 z-50">
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-300 hover:bg-[#4d7cfe] hover:text-white transition-all">
                                <i class='bx bx-user-circle text-lg'></i> <?php echo _t('edit_profile'); ?>
                            </a>
                            <div class="my-1 border-t border-white/5"></div>
                            <a href="<?php echo getUrl('login/logout'); ?>" class="flex items-center gap-3 px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition-all">
                                <i class='bx bx-log-out text-lg'></i> <?php echo _t('logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar w-full">
                <div class="w-full">
                    <?php echo $mainregion; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ELEMENTS
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebarTexts = document.querySelectorAll('.sidebar-text');
        
        const avatarButton = document.getElementById('avatarButton');
        const avatarDropdown = document.getElementById('avatarDropdown');
        const notifButton = document.getElementById('notifButton');
        const notifDropdown = document.getElementById('notifDropdown');
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsDropdown = document.getElementById('settingsDropdown');

        // SIDEBAR TOGGLE
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('md:w-64');
                sidebar.classList.toggle('md:w-20');
                sidebarTexts.forEach(text => text.classList.toggle('md:hidden'));
            } else {
                sidebar.classList.toggle('-translate-x-full');
            }
        });

        // DROPDOWN MANAGER
        const allMenus = [avatarDropdown, notifDropdown, settingsDropdown];
        const setupDropdown = (btn, menu) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                allMenus.forEach(m => { if(m !== menu) m.classList.add('hidden'); });
                menu.classList.toggle('hidden');
            });
        };

        setupDropdown(avatarButton, avatarDropdown);
        setupDropdown(notifButton, notifDropdown);
        setupDropdown(settingsBtn, settingsDropdown);

        // CLOSE ON CLICK OUTSIDE
        document.addEventListener('click', (e) => {
            allMenus.forEach(m => m.classList.add('hidden'));
            if (window.innerWidth < 768 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    </script>
</body>
</html>
