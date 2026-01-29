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
        // We group by sender_id to find the latest message from each customer
        $sql = "SELECT
                u.id as user_id,
                u.realname,
                c.message as last_msg,
                c.d_created,
                (SELECT COUNT(*) FROM chat_logs
                 WHERE sender_id = u.id AND is_read = 0) as unread_count
            FROM mst_users u
            JOIN chat_logs c ON c.sender_id = u.id
            WHERE c.id IN (
                SELECT MAX(id) FROM chat_logs
                GROUP BY sender_id
            )
            AND u.perms NOT IN ('superadmin', 'admin')
            ORDER BY c.d_created DESC";

        $tickets = $this->db->query($sql)->fetchAll();
        $data['data'] = [
            'tickets' => $tickets
        ];

        $this->res->json($data);
    }

    /**
     * API: Get messages for a specific user
     */
    public function api_getmessages()
    {
        // Get user_id from GET parameters
        $user_id = $_GET['user_id'];

        if (! $user_id) {
            $data['code'] = 400;
            $data['error'] = 'User ID missing';

            $this->res->json($data);
        }

        // Removed target_id from the query to prevent SQL errors
        $sql = "SELECT * FROM chat_logs
                WHERE sender_id = ?
                AND status = 1
                ORDER BY d_created ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $user_id);
        $stmt->execute();

        $messages = $stmt->fetchAll();

        // Mark messages as read (Removed target_id check here as well)
        /*
         * $this->db->query("UPDATE chat_logs SET is_read = 1 WHERE sender_id = :uid", [
         * 'uid' => $user_id
         * ]);
         */

        $data['data'] = [
            'messages' => $messages
        ];

        // Return using your specific JSON architecture
        $this->res->json($data);
    }
}
