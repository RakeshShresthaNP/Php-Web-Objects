<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
final class cSupport extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function manage_index()
    {
        $data['pagename'] = $this->partner->sitetitle;

        $this->res->view($data);
    }

    public function api_gettickets()
    {
        $m = new model('mst_users');

        $tickets = $m->selectRaw("mst_users.id as user_id, mst_users.realname, c.message, c.created_at, c.sender_id as last_sender_id, (SELECT COUNT(*) FROM chat_logs WHERE sender_id = mst_users.id AND is_read = 0) as unread_count")
            ->join('chat_logs c', 'c.id', '=', "(SELECT id FROM chat_logs WHERE (sender_id = mst_users.id OR target_id = mst_users.id) ORDER BY created_at DESC LIMIT 1)", 'INNER')
            ->whereNotIn('mst_users.perms', [
            'superadmin',
            'admin'
        ])
            ->orderBy('c.created_at', 'DESC')
            ->find();

        $data = [
            'code' => 200,
            'data' => [
                'tickets' => $tickets
            ]
        ];

        return $this->res->json($data);
    }

    public function api_getmessages()
    {
        $user_id = $_GET['user_id'] ?? null;

        if (! $user_id) {
            $data['code'] = 400;
            $data['error'] = _t('id') . ' ' . _t('not_exist');
            return $this->res->json($data);
        }

        $m = new model('chat_logs');

        $messages = $m->selectRaw("chat_logs.*, r.message AS reply_to_text, u.realname as sender_name, u.perms as sender_perms")
            ->join('chat_logs r', 'chat_logs.reply_to', '=', 'r.id', 'LEFT')
            ->join('mst_users u', 'chat_logs.sender_id', '=', 'u.id', 'LEFT')
            ->whereRaw("(chat_logs.sender_id = ? OR chat_logs.target_id = ?)", [
            $user_id,
            $user_id
        ])
            ->where('chat_logs.status', 1)
            ->orderBy('chat_logs.created_at', 'ASC')
            ->find();

        $m->where('sender_id', $user_id)
            ->where('is_read', 0)
            ->updateWhere([
            'is_read' => 1
        ]);

        $data['code'] = 200;
        $data['data'] = [
            'messages' => $messages
        ];
        $this->res->json($data);
    }
}
