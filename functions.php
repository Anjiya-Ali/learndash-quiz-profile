<?php

add_shortcode('ld_quiz_profile','display_profile');

function display_profile(){
    global $learndash_shortcode_used;

	// Add check to ensure user it logged in.
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$defaults = array(
		'user_id'            => get_current_user_id(),
		'per_page'           => false,
		'order'              => 'DESC',
		'orderby'            => 'ID',
		'course_points_user' => 'yes',
		'expand_all'         => false,
		'profile_link'       => 'yes',
		'show_header'        => 'yes',
		'show_quizzes'       => 'yes',
		'show_search'        => 'no',
		'search'             => '',
	);
	$atts     = wp_parse_args( $atts, $defaults );

	/**
	 * LEARNDASH-6274: Patch to ensure the user_id is valid.
	 */
	if ( ( (int) $atts['user_id'] !== (int) get_current_user_id() ) && ( ! learndash_is_admin_user( get_current_user_id() ) ) ) {
		if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			// If group leader user we ensure the preview user_id is within their group(s).
			if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $atts['user_id'] ) ) {
				$atts['user_id'] = get_current_user_id();
			}
		} else {
			// If neither admin or group leader then we don't see the user_id for the shortcode.
			$atts['user_id'] = get_current_user_id();
		}
	}

	$enabled_values = array( 'yes', 'true', 'on', '1' );
	if ( in_array( strtolower( $atts['expand_all'] ), $enabled_values, true ) ) {
		$atts['expand_all'] = true;
	} else {
		$atts['expand_all'] = false;
	}

	if ( in_array( strtolower( $atts['show_header'] ), $enabled_values, true ) ) {
		$atts['show_header'] = 'yes';
	} else {
		$atts['show_header'] = false;
	}

	if ( in_array( strtolower( $atts['show_search'] ), $enabled_values, true ) ) {
		$atts['show_search'] = 'yes';
	} else {
		$atts['show_search'] = false;
	}

	if ( in_array( strtolower( $atts['course_points_user'] ), $enabled_values, true ) ) {
		$atts['course_points_user'] = 'yes';
	} else {
		$atts['course_points_user'] = false;
	}

	if ( false === $atts['per_page'] ) {
		$atts['per_page'] = $atts['quiz_num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	} else {
		$atts['per_page'] = intval( $atts['per_page'] );
	}

	if ( $atts['per_page'] > 0 ) {
		$atts['paged'] = 1;
	} else {
		unset( $atts['paged'] );
		$atts['nopaging'] = true;
	}

	if ( in_array( strtolower( $atts['profile_link'] ), $enabled_values, true ) ) {
		$atts['profile_link'] = true;
	} else {
		$atts['profile_link'] = false;
	}

	if ( in_array( strtolower( $atts['show_quizzes'] ), $enabled_values, true ) ) {
		$atts['show_quizzes'] = true;
	} else {
		$atts['show_quizzes'] = false;
	}

	if ( 'yes' === $atts['show_search'] ) {
		if ( ( isset( $_GET['ld-profile-search'] ) ) && ( ! empty( $_GET['ld-profile-search'] ) ) ) {
			$atts['search'] = esc_attr( $_GET['ld-profile-search'] );
		}
	} else {
		$atts['search'] = '';
	}

	/**
	 * Filters profile shortcode attributes.
	 *
	 * @param array $attributes An array of shortcode attributes.
	 */
	$atts = apply_filters( 'learndash_profile_shortcode_atts', $atts );

	if ( isset( $atts['search'] ) ) {
		$atts['s'] = $atts['search'];
		unset( $atts['search'] );
	}

	if ( empty( $atts['user_id'] ) ) {
		return;
	}

	$current_user = get_user_by( 'id', $atts['user_id'] );
	$user_courses = ld_get_mycourses( $atts['user_id'], $atts );


	$usermeta           = get_user_meta( $atts['user_id'], '_sfwd-quizzes', true );
	$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;
	$quiz_attempts      = array();


	$ids = array();

	foreach($quiz_attempts_meta as $quiz_attempt){
		if(get_the_title($quiz_attempt['quiz'] ) !== '')
			array_push($ids, $quiz_attempt['quiz']);
	}

	if ( ! empty( $quiz_attempts_meta ) ) {

		foreach ( $quiz_attempts_meta as $quiz_attempt ) {
			$c                          = learndash_certificate_details( $quiz_attempt['quiz'], $atts['user_id'] );
			$quiz_attempt['post']       = get_post( $quiz_attempt['quiz'] );
			$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );

			if ( get_current_user_id() == $atts['user_id'] && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
				$quiz_attempt['certificate'] = $c;
				if ( ( isset( $quiz_attempt['certificate']['certificateLink'] ) ) && ( ! empty( $quiz_attempt['certificate']['certificateLink'] ) ) ) {
					$quiz_attempt['certificate']['certificateLink'] = add_query_arg( array( 'time' => $quiz_attempt['time'] ), $quiz_attempt['certificate']['certificateLink'] );
				}
			}

			if ( ! isset( $quiz_attempt['course'] ) ) {
				$quiz_attempt['course'] = learndash_get_course_id( $quiz_attempt['quiz'] );
			}
			$course_id = intval( $quiz_attempt['course'] );

			$quiz_attempts[ $course_id ][] = $quiz_attempt;
		}
	}

	$profile_pager = array();

	if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
		$atts['per_page'] = intval( $atts['per_page'] );

		if ( ( isset( $_GET['ld-profile-page'] ) ) && ( ! empty( $_GET['ld-profile-page'] ) ) ) {
			$profile_pager['paged'] = intval( $_GET['ld-profile-page'] );
		} else {
			$profile_pager['paged'] = 1;
		}

		$profile_pager['total_items'] = count( $user_courses );
		$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );

		$ids = array_unique($ids);

		$user_courses = array_slice( $ids, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
	}

	$learndash_shortcode_used = true;

    $user_id = $atts['user_id'];
    $shortcode_atts = $atts;

    return profile($user_id,$quiz_attempts,$current_user,$user_courses,$shortcode_atts,$profile_pager);
}


add_filter(
	'learndash_settings_fields',
	function ( $setting_option_fields = array(), $settings_metabox_key = '' ) {
		if ( 'learndash-quiz-access-settings' === $settings_metabox_key ) {

			$post_id           = get_the_ID();
			$val = get_post_meta( $post_id, 'link_meta_key', true );
			if ( empty( $val ) ) {
				$val = get_permalink($post_id);
			}

			if ( ! isset( $setting_option_fields['custom-field'] ) ) {
				$setting_option_fields['custom'] = array(
					'name'      => 'custom-field',
					'label'     => sprintf(
						esc_html_x( '%s Redirect Link', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'      => 'text',
					'class'     => '-medium',
					'value'     => $val,
					'default'   => '',
				);
			}
		}

		return $setting_option_fields;
	},
	30,
	2
);

add_action(
	'save_post',
	function( $post_id = 0, $post = null, $update = false ) {
		
		if ( isset( $_POST['learndash-quiz-access-settings']['custom-field'] ) ) {
			$value = esc_attr( $_POST['learndash-quiz-access-settings']['custom-field'] );
			update_post_meta( $post_id, 'link_meta_key', $value );
		}

	},
	30,
	3
);


add_action('wp_ajax_reset', 'reset_stats');
add_action( 'wp_ajax_nopriv_reset', 'reset_stats' );


 function reset_stats()
 { 
	$time = $_POST['timee'];
	$users_quiz_data = get_user_meta(get_current_user_id(), '_sfwd-quizzes', true); 
	$count = 0;

	foreach($users_quiz_data as $key){
		if($key['time'] == $time){
		   unset($users_quiz_data[$count]);
		}
		$count++;
	}

	$arr = array_values($users_quiz_data);

	update_user_meta(get_current_user_id(), '_sfwd-quizzes',$arr);
	echo "deleted";
 }