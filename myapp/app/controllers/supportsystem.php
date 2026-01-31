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
final class cSupportSystem extends cController
{

    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = db();
    }

    public function manage_index()
    {
        $data['pagename'] = $this->partner->sitetitle;

        $this->res->view($data);
    }

    public function api_gettickets()
    {
        // This query finds the latest message for each conversation involving a non-admin user
        $sql = "SELECT
                u.id as user_id,
                u.realname,
                c.message as last_msg,
                c.d_created,
                c.sender_id as last_sender_id,
                (SELECT COUNT(*) FROM chat_logs
                 WHERE sender_id = u.id AND is_read = 0) as unread_count
            FROM mst_users u
            JOIN chat_logs c ON c.id = (
                SELECT id FROM chat_logs
                WHERE sender_id = u.id OR target_id = u.id
                ORDER BY d_created DESC LIMIT 1
            )
            WHERE u.perms NOT IN ('superadmin', 'admin')
            ORDER BY c.d_created DESC";
        
        $tickets = $this->db->query($sql)->fetchAll();
        
        $data['code'] = 200;
        $data['data'] = [
            'tickets' => $tickets
        ];
        
        $this->res->json($data);
    }
    
    /**
     * API: Get messages for a specific user
     */
    /**
     * API: Get messages for a specific user
     * All admins can view this history
     */
    public function api_getmessages()
    {
        $user_id = $_GET['user_id'] ?? null;
        
        if (!$user_id) {
            $data['code'] = 400;
            $data['error'] = 'User ID missing';
            return $this->res->json($data);
        }
        
        /**
         * The Logic:
         * 1. Show messages where the USER is the sender.
         * 2. Show messages where the USER is the target (sent by ANY admin).
         */
        $sql = "SELECT
                    m.*,
                    r.message AS reply_to_text,
                    u.realname as sender_name,
                    u.perms as sender_perms
                FROM chat_logs m
                LEFT JOIN chat_logs r ON m.reply_to = r.id
                LEFT JOIN mst_users u ON m.sender_id = u.id
                WHERE (m.sender_id = :uid OR m.target_id = :uid)
                AND m.status = 1
                ORDER BY m.d_created ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $messages = $stmt->fetchAll();
        
        // Mark messages SENT BY THE USER as read, because an admin is now looking at them
        $update = "UPDATE chat_logs SET is_read = 1
                   WHERE sender_id = :uid AND is_read = 0";
        $this->db->prepare($update)->execute(['uid' => $user_id]);
        
        $data['code'] = 200;
        $data['data'] = ['messages' => $messages];
        $this->res->json($data);
    }
}

