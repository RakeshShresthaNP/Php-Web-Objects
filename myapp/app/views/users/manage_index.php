<div class="w-full px-4 md:px-8 pb-10">
    
    <div class="mb-8 flex flex-col gap-1">
        <nav class="flex items-center gap-2 text-[10px] uppercase font-bold tracking-[0.15em]">
            <a href="<?php echo getUrl('manage/dashboard') ?>" class="text-gray-400 hover:text-[#4d7cfe] transition-colors"><?php echo _t('admin'); ?></a>
            
            <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            
            <span class="text-white"><?php echo _t('management'); ?></span>
        </nav>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-3xl font-black tracking-tight text-white"><?php echo _t('users'); ?></h2>
            <a href="<?php echo getUrl('manage/users/add') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#4d7cfe] hover:bg-blue-600 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-blue-500/20 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <?php echo _t('add_user'); ?>
            </a>
        </div>
    </div>

    <hr class="border-white/5 mb-10">

    <?php if (!empty($_SESSION['flash_messages'])): ?>
        <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center gap-3 border <?php 
                    echo $type == 'success' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400'; 
                ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $type == 'success' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'; ?>"></path>
                    </svg>
                    <span class="text-sm font-medium"><?php echo _t($msg); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash_messages']); ?>
    <?php endif; ?>

    <div class="w-full bg-[#151a21] border border-white/5 rounded-2xl shadow-xl overflow-hidden mb-12">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/20 border-b border-white/5 text-[10px] uppercase font-bold text-gray-500 tracking-widest">
                        <th class="px-6 py-5"><?php echo _t('username_email'); ?></th>
                        <th class="px-6 py-5"><?php echo _t('name'); ?></th>
                        <th class="px-6 py-5 text-center"><?php echo _t('registered_date'); ?></th>
                        <th class="px-6 py-5 text-center"><?php echo _t('status'); ?></th>
                        <th class="px-6 py-5 text-right"><?php echo _t('action'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if(!empty($users)): foreach ($users as $user): ?>
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-blue-400 group-hover:text-blue-300 transition-colors"><?php echo $user->email; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-300"><?php echo $user->realname; ?></span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500">
                            <?php echo date('Y-m-d', strtotime($user->created_at)); ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-tight <?php echo $user->status == 1 ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'; ?>">
                                <?php echo $user->status == 1 ? _t('enabled') : _t('disabled'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-2">
                                <a href="<?php echo getUrl('manage/users/edit/'.$user->id) ?>" class="p-2 bg-white/5 hover:bg-blue-500/10 rounded-lg text-gray-500 hover:text-[#4d7cfe] transition-all" title="<?php echo _t('edit'); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                <a href="<?php echo getUrl('manage/users/'.($user->status == 1 ? 'disable' : 'enable').'/'.$user->id) ?>" class="p-2 bg-white/5 rounded-lg transition-all <?php echo $user->status == 1 ? 'text-amber-500/50 hover:text-amber-500' : 'text-emerald-500/50 hover:text-emerald-500'; ?>" title="<?php echo _t('toggle_status'); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </a>
                                <a href="<?php echo getUrl('manage/users/delete/'.$user->id) ?>" onclick="return confirm('<?php echo _t('confirm_delete'); ?>');" class="p-2 bg-red-500/5 hover:bg-red-500/20 rounded-lg text-red-500/40 hover:text-red-500 transition-all" title="<?php echo _t('delete'); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-600 italic"><?php echo _t('no_user_records_found'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-6 flex flex-col gap-1">
        <h3 class="text-xl font-bold text-white pl-1"><?php echo _t('partner_configuration'); ?></h3>
        <p class="text-[10px] text-gray-500 pl-1 uppercase tracking-wider font-bold"><?php echo _t('manage_integrated_partners'); ?></p>
    </div>
    
    <div class="w-full bg-[#151a21] border border-white/5 rounded-2xl shadow-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-black/20 border-b border-white/5 text-[10px] uppercase font-bold text-gray-500 tracking-widest">
                    <th class="px-6 py-5 w-20 text-center"><?php echo _t('id'); ?></th>
                    <th class="px-6 py-5"><?php echo _t('partner_name'); ?></th>
                    <th class="px-6 py-5"><?php echo _t('hostname'); ?></th>
                    <th class="px-6 py-5"><?php echo _t('email_address'); ?></th>
                    <th class="px-6 py-5 text-right"><?php echo _t('settings'); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if(!empty($partners)): foreach ($partners as $p): ?>
                <tr class="hover:bg-white/[0.01] transition-all">
                    <td class="px-6 py-4 text-xs font-mono text-gray-600 text-center">#<?php echo $p->id ?></td>
                    <td class="px-6 py-4 font-bold text-white text-sm"><?php echo $p->c_name ?></td>
                    <td class="px-6 py-4"><code class="px-2 py-1 bg-black/30 rounded text-blue-400 text-[10px] border border-white/5 font-mono"><?php echo $p->hostname ?></code></td>
                    <td class="px-6 py-4 text-sm text-gray-400"><?php echo $p->email ?></td>
                    <td class="px-6 py-4 text-right">
                        <?php if (!empty($p->settings)): ?>
                            <div class="flex flex-col gap-1 items-end">
                                <?php foreach ($p->settings as $set): ?>
                                    <div class="flex items-center gap-2 text-[10px] text-gray-400 bg-white/5 px-2 py-1 rounded border border-white/5">
                                        <span><?php echo _t('host'); ?>: <span class="text-gray-200 font-mono"><?php echo $set->mailhost ?></span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?><span class="text-xs italic text-gray-600"><?php echo _t('no_settings'); ?></span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-600 italic"><?php echo _t('no_partners_found'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
