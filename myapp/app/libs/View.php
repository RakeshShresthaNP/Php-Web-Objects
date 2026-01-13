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

final class View
{

    public static function assign(array &$vars = array(), string $viewname = ''): string
    {
        $req = req();
        if (is_array($vars)) {
            extract($vars);
        }
        ob_start();
        if ($viewname == null) {
            $viewname = $req->controller . '/' . $req->method;
        }
        include VIEW_DIR . mb_strtolower($viewname) . '.php';
        return ob_get_clean();
    }

    public static function display(array &$vars = array(), string $viewname = ''): void
    {
        $req = req();
        if ($viewname == null) {
            $viewname = mb_strtolower($req->controller . '/' . $req->method);
        }
        if (! isset($vars['layout'])) {
            $playout = 'layouts/' . $req->pathprefix . 'layout';
            $vars['mainregion'] = self::assign($vars, $viewname);
        } else {
            if ($vars['layout']) {
                $playout = $vars['layout'];
            } else {
                $playout = $viewname;
            }
        }
        if (is_array($vars)) {
            extract($vars);
        }
        include VIEW_DIR . mb_strtolower($playout) . '.php';
    }
}
