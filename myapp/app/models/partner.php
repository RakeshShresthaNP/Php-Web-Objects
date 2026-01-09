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
final class partner extends model
{

    public function __construct(int $id = 0)
    {
        parent::__construct('mst_partners');

        if ($id)
            $this->select('*', 'id=?', $id);
    }

    public function getAllConfigByHost(string $hostname)
    {
        $sql = "SELECT 
                    JSON_OBJECT(
                        'id', p.id, 
                        'c_name', p.c_name,
                        'hostname', p.hostname,
                        'sitetitle', p.sitetitle,
                        'email', p.email,
                        'phone1', p.phone1,
                        'phone2', p.phone2,
                        'contactfax', p.contactfax,
                        'address1', p.address1,
                        'address2', p.address2,
                        'city', p.city,
                        'state', p.state,
                        'country', p.country,
                        'zip', p.zip,
                        'settings', COALESCE(
                            (
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        'id', ps.id, 
                                        'mailtype', ps.mailtype,
                                        'mailhost', ps.mailhost, 
                                        'mailport', ps.mailport,
                                        'mailusername', ps.mailusername, 
                                        'mailpassword', ps.mailpassword,
                                        'mailencryption', ps.mailencryption, 
                                        'geoip_api_key', ps.geoip_api_key,
                                        'firebase_api_key', ps.firebase_api_key,
                                        'gemini_api_key', ps.gemini_api_key
                                    )
                                )
                                FROM mst_partner_settings ps 
                                WHERE ps.partner_id = p.id
                            ), 
                            JSON_ARRAY() -- Returns [] if no settings found
                        )
                    ) AS partners
                FROM mst_partners p WHERE p.hostname=?";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(1, $hostname);

        $stmt->execute();

        return $stmt->fetchColumn();
    }
}
