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

    public function index()
    {
        $data = [
            'title' => 'Admin Dashboard',
            'stats' => [
                'total_users' => 3782,
                'revenue' => '20k'
            ]
        ];

        $this->view('admin/dashboard', $data);
    }

    public function support()
    {
        // You might want to fetch last 20 chats from DB for initial load
        $chatModel = $this->model('ChatSession');

        $data = [
            'title' => 'Live Support System',
            'active_sessions' => $chatModel->getActiveSessions()
        ];

        $this->view('admin/support', $data);
    }
}
