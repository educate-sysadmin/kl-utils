<?php
/*
Plugin Name: KL Utils
Plugin URI: https://github.com/educate-sysadmin/kl-utils
Description: General Wordpress utility functions
Version: 0.2.2
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/

try {    
    if (!class_exists('KLUtils')) {
        class KLUtils {

            /* search associative array for key value pair */ // untested
            public static function exists_in_array($key,$value,$array) {
                foreach ($array as $a) {
                    if (isset($a[$key]) && $a[$key] == $value) {
                        return true;
                    }
                }
                // else
                return false;
            }

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
            
            /* member-role-category related functions */
                /* Ref: 
                    [members_access role=array()] shortcode.
                    members/inc/functions-users.php: e.g.:
                        members_get_user_role_names( $user_id )
                        members_user_has_role( $user_id, $roles )
                     members/inc/functions-content-permissions.php e.g.:
                        members_add_post_role( $post_id, $role )
                        members_remove_post_role( $post_id, $role )
                        members_get_post_roles( $post_id )
                 */
                
            /* lookup category term id for category name or slug */
            public static function get_category_id($category) {                        
                $category_object = get_term_by('name', $category, 'category');
                if (!$category_object) { $category_object = get_term_by('slug', $category, 'category'); }
                return ($category_object)?$category_object->term_id:false;
            }

            /* Return an array of post ids in category (name or slug) */
            public static function get_posts_in_category($category) {
                global $wpdb;
                
                // validate
                if (!preg_match('/[A-Za-z_\s]/',$category)) { return null; }

                // lookup category id
                $category_id = KLUtils::get_category_id($category);
                
                // find posts and pages in category 
                // ref https://codex.wordpress.org/Function_Reference/get_pages but using get_posts
                $args = array(
	                'post_type' => array('page','post'),
	                'post_status' => 'publish',
                   'cat'=> $category_id
                ); 
                $posts_objects = get_posts($args); 
                
                // extract post ids
                $posts = array();
                foreach ($posts_objects as $posts_object) {
                    $posts[] = $posts_object->ID;
                }
                
                return $posts;
                
                // query for older wp for reference
                //$sql = 'SELECT ID,post_title FROM '.$wpdb->prefix.'posts, '.$wpdb->prefix.'term_relationships WHERE object_id = ID AND (post_type = "page" OR post_type="post") AND term_taxonomy_id = '.$category_id;          
                // query for wp circa 4.9.6 for reference
                //$sql = 'SELECT ID,post_title FROM '.$wpdb->prefix.'posts, '.$wpdb->prefix.'term_relationships, '.$wpdb->prefix. 'term_taxonomy WHERE object_id = ID AND (post_type = "page" OR post_type="post") AND '.$wpdb->prefix. 'term_taxonomy.term_taxonomy_id = '.$wpdb->prefix.'term_relationships.term_taxonomy_id AND term_id = '.$category_id;                
            }
            
            /* add role(s) to all posts in category */
            public static function add_category_role($category, $role) {
            
                // handle array or single role parameter
                if (!is_array($role)) {
                    $role = array($role);
                }
            
                // get posts
                $posts = KLUtils::get_posts_in_category($category);
                
                // add if not already existing
                foreach ($posts as $post) {
                    foreach ($role as $rol) {
                        $current_roles = members_get_post_roles($post);
                        if (!in_array($rol, $current_roles)) {
                            members_add_post_role( $post, $rol );
                        }
                    }            
                }    
            }
            
            /* remove role(s) from all posts in category */    
            public static function remove_category_role($category, $role) {
                // handle array or single role parameter
                if (!is_array($role)) {
                    $role = array($role);
                }
            
                // get posts
                $posts = KLUtils::get_posts_in_category($category);
                
                // update
                foreach ($posts as $post) {
                    foreach ($role as $rol) {
                        members_remove_post_role( $post, $rol );
                    }            
                }        
            }    
            
            /* return array of all roles with access to (any) posts in category */
            public static function get_category_roles($category) {
                $roles = array();
                
                // validate
                if (!preg_match('/[A-Za-z_\s]/',$category)) { return null; }
                
                // get posts
                $posts = KLUtils::get_posts_in_category($category);
                
                // populate $roles array
                if ($posts && !empty($posts)) {
                    foreach ($posts as $post) {
                        $post_roles = members_get_post_roles( $post );
                        foreach ($post_roles as $post_role) {
                            if (!in_array($post_role,$roles)) {
                                $roles[] = $post_role;
                            }
                        }
                    }
                }
                
                return $roles;    
            }

            /* check whether user's roles has access to (any) posts in category */
            public static function user_has_category_permissions($category, $user_id) {
                $user_roles = members_get_user_role_names( $user_id );
                $category_roles = KLUtils::get_category_roles($category);
                foreach ($category_roles as $category_role) {
                    foreach ($user_roles as $user_role_key => $user_role_val) {
                        // match either role title or role name
                        if (strtolower($category_role) == strtolower($user_role_key) || strtolower($category_role) == strtolower($user_role_val)) {
                            return true;
                        }
                    }
                }
                // else
                return false;
            }
        }
    }
} catch (Exception $e) {
    ;    
}
