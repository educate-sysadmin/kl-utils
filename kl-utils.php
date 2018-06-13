<?php
/*
Plugin Name: KL Utils
Plugin URI: https://github.com/educate-sysadmin/kl-utils
Description: General Wordpress utility functions
Version: 0.1
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/

class KLUtils {

    /* return array of user's role */
    // if not logged in, adds role 'visitor'
    public static function get_user_roles() {
        $roles = array();
        if (!is_user_logged_in()) {
            $roles[] = 'visitor';
        } else {
            $user_object = wp_get_current_user();
            foreach ($user_object->roles as $role) {
                $roles[] = $role;
            }
        }    
        return $roles;
    }

}