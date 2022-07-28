<?php
/**
 * Plugin Name:       Learndash Quiz Profile
 * Description:       Add user profile based on completed quizzes
 * Version:           1.1.0
 * Requires at least: 5.1
 * Requires PHP:      7.2
 * Author:            Anjiya
 * Text Domain:       learndash-quiz-profile
 * Domain Path:       /languages
 */


global $title;
$title = 5;

require_once( WP_PLUGIN_DIR . '/learndash-quiz-profile/functions.php');
require_once( WP_PLUGIN_DIR . '/learndash-quiz-profile/profile.php');
require_once( WP_PLUGIN_DIR . '/learndash-quiz-profile/course-row.php');
require_once( WP_PLUGIN_DIR . '/learndash-quiz-profile/quizzes.php');
require_once( WP_PLUGIN_DIR . '/learndash-quiz-profile/quiz-row.php');
require_once( WP_PLUGIN_DIR . '/learndash-quiz-profile/avg-score.php');


add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );

function enqueue_scripts(){
    wp_enqueue_script('graph1',"https://cdn.jsdelivr.net/npm/chart.js",array(), '1.0.0', true);
    wp_enqueue_script('graph',plugin_dir_url( __FILE__ ) . 'assets/js/graph.js',array(), '1.0.0', true);
    wp_localize_script('graph', 'global', array(
			'data' =>title(),
		) );
    wp_enqueue_script( 'ajax-script', plugin_dir_url( __FILE__ ) . 'assets/js/graph.js', array('jquery') );
    wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}


