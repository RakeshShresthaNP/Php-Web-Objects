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
final class cPlanImport extends cAuthController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['pagetitle'] = SITE_TITLE;

        $this->res->view($data);
    }

    public function api_importhr()
    {
        // Validate file upload
        if (! isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new ApiException('Year or month is missing.', 422);
        }

        $db = db();

        $exec_query = FALSE;

        $ses = $this->user->userid;

        $xlsx = new SimpleExcel($_FILES['file']['tmp_name']);

        if ($xlsx->success()) {

            list ($num_cols, $num_rows) = $xlsx->dimension();

            $all_rows_array = $xlsx->rows();

            $n_month = $all_rows_array[1][14];
            $n_year = $all_rows_array[1][28];

            if ($n_month != '' && $n_year != '') {
                list ($num_cols, $num_rows) = $xlsx->dimension();

                $all_rows_data_array = $xlsx->rows();

                $d_created = date("Y-m-d H:i:s", time());

                // We start our transaction.
                $db->beginTransaction();

                $d_sql = "DELETE FROM pmis_hr_tracking WHERE n_package_id = ? AND d_date = ? ";
                $d_stmt = $db->prepare($d_sql);

                $i_sql = "INSERT INTO pmis_hr_tracking (n_plan_id, d_date, n_package_id, n_works_id, c_des, n_required_resource, n_available_resource, n_act_flg, n_user_id, d_created, d_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $i_stmt = $db->prepare($i_sql);

                for ($i = 3; $i < $num_rows; $i ++) {
                    $n_baseline_id = $all_rows_data_array[$i][1];
                    $n_works_id = $all_rows_data_array[$i][2];

                    $sitename = $all_rows_data_array[$i][3];
                    $contractorname = $all_rows_data_array[$i][4];

                    $n_required_resource = $all_rows_data_array[$i][6];

                    $c_des = $all_rows_data_array[$i][4];

                    if ($n_baseline_id != '') {
                        for ($j = 7; $j < $num_cols; $j ++) {
                            $day = $j - 6;
                            $date = $n_year . "-" . $n_month . "-" . $day;
                            $n_available_resource = $all_rows_data_array[$i][$j];

                            if ($day <= 31) {
                                $n_available_resourcearray[$j] = $n_available_resource;
                            }

                            if ($n_available_resource != '' && $day <= 31) {
                                try {
                                    $d_stmt->bindValue(1, $n_baseline_id);
                                    $d_stmt->bindValue(2, $date);

                                    $d_stmt->execute();

                                    $i_stmt->bindValue(1, 0);
                                    $i_stmt->bindValue(2, $date);
                                    $i_stmt->bindValue(3, $n_baseline_id);
                                    $i_stmt->bindValue(4, $n_works_id);
                                    $i_stmt->bindValue(5, $c_des);
                                    $i_stmt->bindValue(6, $n_required_resource);
                                    $i_stmt->bindValue(7, $n_available_resource);
                                    $i_stmt->bindValue(8, 0);
                                    $i_stmt->bindValue(9, $ses);
                                    $i_stmt->bindValue(10, $d_created);
                                    $i_stmt->bindValue(11, $d_created);

                                    $exec_query = $i_stmt->execute();
                                } catch (Exception $e) {
                                    $db->rollBack();
                                    throw new ApiException($e->getMessage(), $e->getCode());
                                }
                            }
                        }
                    }

                    $dateinfo = date('j', strtotime(date('Y-m-d')));
                    $daysdiff = $dateinfo - 7;
                    if ($daysdiff < 0) {
                        $daysdiff = 1;
                    }
                    $day1 = strtotime($n_year . '-' . $n_month . '-' . $daysdiff);
                    $day2 = strtotime(date('Y-m-d'));

                    $datediff = ceil(($day2 - $day1) / 86400);

                    $flag = 0;
                    for ($c = $daysdiff; $c <= $dateinfo; $c ++) {

                        $keytocheck = $c + 6;

                        if (isset($n_available_resourcearray[$keytocheck - 2]) && isset($n_available_resourcearray[$keytocheck - 1]) && $n_available_resourcearray[$keytocheck - 2] == 0 && $n_available_resourcearray[$keytocheck - 1] == 0 && $n_available_resourcearray[$keytocheck] == 0 && $datediff < 8 and $datediff > 0) {
                            $flag = 1;
                        }
                    }
                }

                // We've got this far without an exception, so commit the changes.
                $db->commit();

                if ($exec_query) {
                    $data['message'] = 'Successfully Imported Sheet';

                    $this->res->json($data);
                } else {
                    throw new ApiException('Year or month is missing.', 422);
                }
            } else {
                throw new ApiException('Year or month is missing.', 422);
            }
        } else {
            throw new ApiException('xlsx error: ' . $xlsx->error(), 503);
        }
    }
}
