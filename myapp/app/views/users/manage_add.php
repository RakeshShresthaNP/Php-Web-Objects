<div class="w-full px-4 md:px-8 pb-10">
    
    <div class="mb-8 flex flex-col gap-1">
        <nav class="flex items-center gap-2 text-[10px] uppercase font-bold tracking-[0.15em]">
            <a href="<?php echo getUrl('manage/dashboard') ?>" class="text-gray-400 hover:text-[#4d7cfe] transition-colors"><?php echo _t('admin'); ?></a>
            
            <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            
            <a href="<?php echo getUrl('manage/users') ?>" class="text-gray-400 hover:text-[#4d7cfe] transition-colors uppercase"><?php echo _t('management'); ?></a>
            
            <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            
            <span class="text-white"><?php echo _t('create_account'); ?></span>
        </nav>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-3xl font-black tracking-tight text-white"><?php echo _t('add_user'); ?></h2>
            <a href="<?php echo getUrl('manage/users') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-bold rounded-xl transition-all active:scale-95 border border-white/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <?php echo _t('back_to_list'); ?>
            </a>
        </div>
    </div>

    <hr class="border-white/5 mb-10">

    <div class="max-w-4xl mx-auto">
        
        <?php if (!empty($_SESSION['flash_errors'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                <?php foreach($_SESSION['flash_errors'] as $field => $msgs): ?>
                    <?php foreach($msgs as $m): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-1 h-1 bg-red-400 rounded-full"></span>
                            <?php echo _t($m); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; unset($_SESSION['flash_errors']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-[#151a21] border border-white/5 rounded-2xl shadow-2xl overflow-hidden">
            <form action="<?php echo getUrl('manage/users/add') ?>" method="post" id="addprofile" class="p-8 space-y-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-widest pl-1"><?php echo _t('full_name'); ?></label>
                        <input type="text" name="realname" required placeholder="<?php echo _t('eg_john_doe'); ?>" value="<?php echo $user['realname'] ?? '' ?>" 
                               class="w-full bg-[#0b0e14] border border-white/10 rounded-xl px-4 py-4 text-sm text-white focus:border-[#4d7cfe] outline-none transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-widest pl-1"><?php echo _t('home_path'); ?></label>
                        <input type="text" name="homepath" required placeholder="/home/user" value="<?php echo $user['homepath'] ?? '' ?>" 
                               class="w-full bg-[#0b0e14] border border-white/10 rounded-xl px-4 py-4 text-sm text-white focus:border-[#4d7cfe] outline-none transition-all">
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-widest pl-1"><?php echo _t('email_username'); ?></label>
                        <input type="email" name="email" required placeholder="email@example.com" value="<?php echo $user['email'] ?? '' ?>" 
                               class="w-full bg-[#0b0e14] border border-white/10 rounded-xl px-4 py-4 text-sm text-white focus:border-[#4d7cfe] outline-none transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-widest pl-1"><?php echo _t('password'); ?></label>
                        <input type="password" name="password" required placeholder="••••••••" 
                               class="w-full bg-[#0b0e14] border border-white/10 rounded-xl px-4 py-4 text-sm text-white focus:border-[#4d7cfe] outline-none transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-widest pl-1"><?php echo _t('confirm_password'); ?></label>
                        <input type="password" id="confirm_password" required placeholder="••••••••" 
                               class="w-full bg-[#0b0e14] border border-white/10 rounded-xl px-4 py-4 text-sm text-white focus:border-[#4d7cfe] outline-none transition-all">
                    </div>
                </div>

                <div class="pt-8 border-t border-white/5 flex justify-end">
                    <button type="submit" name="submit" class="w-full md:w-auto px-12 py-4 bg-[#4d7cfe] hover:bg-blue-600 text-white font-extrabold rounded-xl shadow-lg shadow-blue-500/20 transition-all active:scale-95">
                        <?php echo _t('create_user_account'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
