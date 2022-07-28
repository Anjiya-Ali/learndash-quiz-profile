<?php
function profile ($user_id,$quiz_attempts,$current_user,$user_courses,$shortcode_atts,$profile_pager){
    ob_start();
    global $learndash_assets_loaded;
    $learndash_shortcode_data_json = htmlspecialchars( wp_json_encode( $shortcode_atts ) );
    
    /**
     * Logic to load assets as needed
     *
     * @var [type]
     */
    
    if ( ! isset( $learndash_assets_loaded['scripts']['learndash_template_script_js'] ) ) :
        $learndash_template_script_filepath = SFWD_LMS::get_template( 'learndash_template_script.js', null, null, true );
        if ( ! empty( $learndash_template_script_filepath ) ) :
            wp_enqueue_script( 'learndash_template_script_js', learndash_template_url_from_path( $learndash_template_script_filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
            $learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;
    
            $learndash_data            = array();
            $learndash_data['ajaxurl'] = admin_url( 'admin-ajax.php' );
            $learndash_data            = array( 'json' => wp_json_encode( $learndash_data ) );
            wp_localize_script( 'learndash_template_script_js', 'sfwd_data', $learndash_data );
        endif;
    endif;
    
    
    /**
     * We don't want to include this if this is a paginated or search view as we'll end up with duplicates
     *
     * @var $_GET['action'] (string)    is set to 'ld30_ajax_pager' if paginating or set to 'ld30_ajax_profile_search' on search.
     */
    
    if ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], array( 'ld30_ajax_pager', 'ld30_ajax_profile_search' ), true ) ) :
        LD_QuizPro::showModalWindow();
    endif; ?> 
    <div style="max-width: 950px" class="<?php learndash_the_wrapper_class(); ?>">
        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped on line 20 ?>
        <div id="ld-profile" data-shortcode_instance="<?php echo $learndash_shortcode_data_json; ?>">
            <?php
            /**
             * If the user wants to include the summary, they use the shortcode attr summary="true"
             *
             * @var [type]
             */
            if ( isset( $shortcode_atts['show_header'] ) && 'yes' === $shortcode_atts['show_header'] ) :
                ?>
                <div class="ld-profile-summary">
                    <div class="ld-profile-card">
                        <div class="ld-profile-avatar">
                            <?php
                            /**
                             * Related to the CSS themes/ld30/assets/css/learndash.css
                             * .learndash-wrapper .ld-profile-summary .ld-profile-card .ld-profile-avatar
                             */
                            echo wp_kses_post( get_avatar( $user_id, 150 ) );
                            ?>
                        </div> <!--/.ld-profile-avatar-->
                        <?php
                        if ( ! empty( $current_user->user_lastname ) || ! empty( $current_user->user_firstname ) ) :
                            ?>
                            <div class="ld-profile-heading">
                                <?php echo esc_html( $current_user->user_firstname . ' ' . $current_user->user_lastname ); ?>
                            </div>
                        <?php endif; ?>
    
                        <?php
                        /**
                         * Filters whether to show the user profile link.
                         *
                         * @since 2.5.8
                         *
                         * @param boolean $show_profile Whether to show profile link.
                         */
                        if ( current_user_can( 'read' ) && isset( $shortcode_atts['profile_link'] ) && true === (bool) $shortcode_atts['profile_link'] && apply_filters( 'learndash_show_profile_link', $shortcode_atts['profile_link'] ) ) :
                            ?>
                            <a class="ld-profile-edit-link" href='<?php echo esc_url( get_edit_user_link() ); ?>'><?php esc_html_e( 'Edit profile', 'learndash' ); ?></a>
                        <?php endif; ?>
                    </div> <!--/.ld-profile-card-->
                    <div class="ld-profile-stats">
                        <?php
                        $learndash_user_stats = learndash_get_user_stats( $user_id );
                        
                        global $wpdb;



                        $text = array();

                        $users_quiz_data = get_user_meta($user_id, '_sfwd-quizzes', true );
	
                        $count = 0;
                        $diff=0;

                        foreach($users_quiz_data as $data){
                            if(get_the_title($data['quiz'])!=""){
                                array_push($text,get_the_title($data['quiz']));

                                $d1 = $data['started'];
                                $d2 = $data['completed'];
                                
                                $diff+=$d2-$d1;

                                $score += $data['score'];
                                $count++;
                                $percent += $data['percentage'];
                            }
                        }

                        $quiz_completed_count=count(array_unique($text));
                        $time = gmdate("H:i:s", $diff);
                        $avg = round($percent/$count,1);
                        if($avg<0){
                            $avg = 0;
                        }

                        $learndash_profile_stats = array(
                            array(
                                'title' => __( 'total quizzes', 'learndash' ),
                                'value' => $quiz_completed_count,
                                'class' => 'ld-profile-stat-courses',
                            ),
                            array(
                                'title' => __( 'total time spent', 'learndash' ),
                                'value' => $time,
                                'class' => 'ld-profile-stat-completed',
                            ),
    
                            array(
                                'title' => __( 'Avg Score', 'learndash' ),
                                'value' => $avg."%",
                                'class' => 'ld-profile-stat-certificates',
                            ),

                            array(
                                'title' => __( 'Total points', 'learndash' ),
                                'value' => $score,
                                'class' => 'ld-profile-stat-certificates',
                            ),
                        );
    
    
                        /**
                         * Filters LearnDash user profile statistics.
                         *
                         * @since 3.1.0
                         *
                         * @param array $learndash_profile_stats An array of profile stats data.
                         * @param int   $user_id                 User ID.
                         */
                        $learndash_profile_stats = apply_filters( 'learndash_profile_stats', $learndash_profile_stats, $user_id );
                        if ( ( ! empty( $learndash_profile_stats ) ) && ( is_array( $learndash_profile_stats ) ) ) {
                            foreach ( $learndash_profile_stats as $learndash_profile_stat ) {
                                $learndash_stat_class = 'ld-profile-stat';
                                if ( ( isset( $learndash_profile_stat['class'] ) ) && ( ! empty( $learndash_profile_stat['class'] ) ) ) {
                                    $learndash_stat_class .= ' ' . $learndash_profile_stat['class'];
                                }
                                ?>
                                <div class="<?php echo esc_attr( $learndash_stat_class ); ?>">
                                    <strong><?php echo esc_html( $learndash_profile_stat['value'] ); ?></strong>
                                    <span><?php echo esc_html( $learndash_profile_stat['title'] ); ?></span>
                                </div> <!--/.ld-profile-stat-->
                                <?php
                            }
                        }
                        ?>
                    </div> <!--/.ld-profile-stats-->
                </div>
            <?php endif; ?>

            <canvas id="canvas" width="400" height="200"></canvas>

             
            <div class="ld-item-list ld-course-list">
    
                <div class="ld-section-heading">
                    <h3>
                        <?php
                        printf(
                            // translators: Profile Course Content Label.
                            esc_html_x( 'Your Quizzes', 'learndash' ),
                            esc_attr( LearnDash_Custom_Label::get_label( 'courses' ) )
                        );
                        ?>
                    </h3>
                    <div class="ld-item-list-actions">
                        <?php if ( isset( $shortcode_atts['show_search'] ) && 'yes' === $shortcode_atts['show_search'] ) { ?>
                        <button class="ld-search-prompt ld-icon-search ld-icon" data-ld-expands="ld-course-search" aria-label="
                            <?php
                            printf(
                                // translators: placeholder: Profile Search Prompt Label.
                                esc_html_x( 'Show %s Search Field', 'placeholder: Profile Search Prompt Label', 'learndash' ),
                                esc_attr( LearnDash_Custom_Label::get_label( 'courses' ) )
                            );
                            ?>
                        ">
                        </button> <!--/.ld-search-prompt-->
                        <?php } ?>
                        <div class="ld-expand-button" data-ld-expands="ld-main-course-list" data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'learndash' ); ?>" data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'learndash' ); ?>">
                            <span class="ld-icon-arrow-down ld-icon"></span>
                            <span class="ld-text"><?php echo esc_html_e( 'Expand All', 'learndash' ); ?></span>
                        </div> <!--/.ld-expand-button-->
                    </div> <!--/.ld-course-list-actions-->
                </div> <!--/.ld-section-heading-->
                <?php
                if ( isset( $shortcode_atts['show_search'] ) && 'yes' === $shortcode_atts['show_search'] ) {
                    learndash_get_template_part(
                        'shortcodes/profile/search.php',
                        array(
                            'user_id'      => $user_id,
                            'user_courses' => $user_courses,
                        ),
                        true
                    );
                }
                ?>
                
    
                <div class="ld-item-list-items" id="ld-main-course-list" data-ld-expand-list="true" data-ld-expand-id="<?php echo esc_attr( 'ld-main-course-list' ); ?>">
    
                    <?php
                        
                    if ( ! empty( $user_courses ) ) :
                        foreach ( $user_courses as $quiz_id ) :
                            $course_id = learndash_get_course_id($quiz_id);
                            echo course_row($user_id,$quiz_id,$course_id,$quiz_attempts,$shortcode_atts);
    
                        endforeach;
                        else :
    
                            $learndash_no_courses_found_alert = array(
                                'icon'    => 'alert',
                                // translators: placeholder: Courses.
                                'message' => sprintf( esc_html_x( 'No %s found', 'placeholder: Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
                                'type'    => 'warning',
                            );
                            learndash_get_template_part( 'modules/alert.php', $learndash_no_courses_found_alert, true );
    
                    endif;
                        ?>
    
                </div> <!--/.ld-course-list-items-->
    
            </div> <!--/ld-course-list-->
    
            <?php
            $learndash_profile_search = isset( $_GET['ld-profile-search'], $_GET['learndash_profile_course_search_nonce'] ) &&
                                        ! empty( $_GET['ld-profile-search'] ) &&
                                        wp_verify_nonce( $_GET['learndash_profile_course_search_nonce'], $learndash_profile_course_search_nonce_field )
                                        ? sanitize_text_field( $_GET['ld-profile-search'] )
                                        : false;
            learndash_get_template_part(
                'modules/pagination',
                array(
                    'pager_results' => $profile_pager,
                    'pager_context' => 'profile',
                    'search'        => $learndash_profile_search,
                ),
                true
            );
            ?>
    
        </div> <!--/#ld-profile-->
    
    </div> <!--/.ld-course-wrapper-->
    
    <?php
    /** This filter is documented in themes/ld30/templates/course.php */
    if ( apply_filters( 'learndash_course_steps_expand_all', $shortcode_atts['expand_all'], 0, 'profile_shortcode' ) ) :
        ?>
        <script>
            jQuery( function() {
                setTimeout(function() {
                    jQuery(".ld-item-list-actions .ld-expand-button").trigger('click');
                }, 1000 );
            });
        </script>
        <?php
    endif;
    return ob_get_clean();
}

function title(){
    if ( ! is_user_logged_in() ) {
		return '';
	}

    $usermeta           = get_user_meta(get_current_user_id(), '_sfwd-quizzes', true );
	$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;

	$quiz_attempts      = array();


	$ids = array();
    $score = array();
    $temp = array();

	foreach($quiz_attempts_meta as $quiz_attempt){
        if(get_the_title($quiz_attempt['quiz']) != "")
		    array_push($ids, $quiz_attempt['quiz']);
	}

    $ids = array_unique($ids);

    $i=0;

    $data = array();
    $max=0;

    foreach($ids as $id){
        $count =0;
        $data[$i] = array();
        foreach($quiz_attempts_meta as $quiz_attempt){
            if($quiz_attempt['quiz']==$ids[$i]){
                $count++;
                array_push($data[$i],$quiz_attempt['percentage']);
            }
        }
        if($count>$max){
            $max = $count;
        }
        $i++;
    }

    $title = array();

    if ( ! empty( $ids) ){
        foreach ( $ids as $quiz_id ){
            if(get_the_title( $quiz_id ) != "")
                array_push($title,get_the_title( $quiz_id ));
        }
    }

    array_push($data,$title);
    array_push($data,$max);

    return $data;
}