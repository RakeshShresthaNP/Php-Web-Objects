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
declare(strict_types = 1);

final class partner extends model
{

    public function __construct($id = 0)
    {
        parent::__construct('mst_partners', 'id');

        if ($id > 0) {
            // Find the data
            $res = $this->where('id', $id)
                ->limit(1)
                ->find();

            // If data is found, 'hydrate' this specific instance
            if ($res) {
                $data = $res->getData();
                $this->assign($data);
            }
        }
    }

    public function getAllConfigByHost(string $hostname): mixed
    {
        $schema = [
            'id' => 'id',
            'c_name' => 'c_name',
            'hostname' => 'hostname',
            'sitetitle' => 'sitetitle',
            'email' => 'email',
            'phone1' => 'phone1',
            'phone2' => 'phone2',
            'contactfax' => 'contactfax',
            'address1' => 'address1',
            'address2' => 'address2',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'zip' => 'zip',
            'settings' => [
                'table' => 'mst_partner_settings',
                'foreign_key' => 'partner_id',
                'fields' => [
                    'id' => 'id',
                    'secretkey' => 'secretkey',
                    'mailhost' => 'mailhost',
                    'mailport' => 'mailport',
                    'mailusername' => 'mailusername',
                    'mailpassword' => 'mailpassword',
                    'geoip_api_key' => 'geoip_api_key',
                    'firebase_api_key' => 'firebase_api_key',
                    'gemini_api_key' => 'gemini_api_key'
                ]
            ]
        ];

        // findGraph returns a decoded object directly
        return $this->where('hostname', $hostname)->findGraph($schema);
    }

    public function getAllPartnersAsGraph(): mixed
    {
        $schema = [
            'id' => 'id',
            'c_name' => 'c_name',
            'hostname' => 'hostname',
            'sitetitle' => 'sitetitle',
            'email' => 'email',
            'phone1' => 'phone1',
            'phone2' => 'phone2',
            'contactfax' => 'contactfax',
            'address1' => 'address1',
            'address2' => 'address2',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'zip' => 'zip',
            'settings' => [
                'table' => 'mst_partner_settings',
                'foreign_key' => 'partner_id',
                'fields' => [
                    'id' => 'id',
                    'secretkey' => 'secretkey',
                    'mailhost' => 'mailhost',
                    'mailport' => 'mailport',
                    'mailusername' => 'mailusername',
                    'mailpassword' => 'mailpassword',
                    'geoip_api_key' => 'geoip_api_key',
                    'firebase_api_key' => 'firebase_api_key',
                    'gemini_api_key' => 'gemini_api_key'
                ]
            ]
        ];

        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['perpage'] ?? 1);

        return $this->paginateGraph($schema, $page, $perPage);
    }
}
