<?php
/**
 * LearnDash LD30 Displays a user's profile course progress row.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */
function course_row($user_id,$quiz_id,$id,$quiz_attempts,$shortcode_atts)
{
        ob_start();
        $course      = get_post( $quiz_id );
        $course_link =  get_post_meta($quiz_id, 'link_meta_key', true );

        if ( empty( $course_link ) ) {
            $course_link = get_permalink($quiz_id);
        }

        if ( absint( $progress['percentage'] ) > 0 && 100 !== absint( $progress['percentage'] ) ) {
            $status = 'progress';
        }

        /**
         * Filters shortcode course row CSS class.
         *
         * @since 3.0.0
         *
         * @param string $course_row_class List of the course row CSS classes
         */
        $course_class = apply_filters(
            'learndash-course-row-class',
            'ld-item-list-item ld-item-list-item-course ld-expandable ' . ( 100 === absint( $progress['percentage'] ) ? 'learndash-complete' : 'learndash-incomplete' ),
            $course,
            $user_id
        ); 
        $users_quiz_data = get_user_meta($user_id, '_sfwd-quizzes', true );

        $count = 0;
        $diff=0;
        
        foreach($users_quiz_data as $data){
            if($data['quiz']==$quiz_id){
                $count++;
                $d1 = $data['started'];
                $d2 = $data['completed'];
                
                $diff+=$d2-$d1;
            }
        }
        $spent = gmdate("H:i:s",$diff);
        ?>

        <div class="<?php echo esc_attr( $course_class ); ?>" id="<?php echo esc_attr( 'ld-course-list-item-' . $quiz_id ); ?>">
            <div class="ld-item-list-item-preview">

                <a href="<?php echo esc_url($course_link); ?>" class="ld-item-name">
                    <?php learndash_status_icon( $status, get_post_type(), null, true ); ?>
                    <span class="ld-course-title"><?php echo esc_html( get_the_title( $quiz_id ) ); ?></span>
                </a> <!--/.ld-course-name-->
                <span>No of attempt: <?php echo $count?></span>
                <span style="margin-left: 15px">Total time: <?php echo $spent ?></span>
                <div class="ld-item-details">

                    <?php
                    $learndash_certificate_link = learndash_get_course_certificate_link( $course->ID, $user_id );
                    if ( ! empty( $learndash_certificate_link ) ) :
                        ?>
                        <a class="ld-certificate-link" target="_blank" href="<?php echo esc_url( $learndash_certificate_link ); ?>" aria-label="<?php esc_attr_e( 'Certificate', 'learndash' ); ?>"><span class="ld-icon ld-icon-certificate"></span></span></a>
                    <?php endif; ?>

                    <?php echo wp_kses_post( learndash_status_bubble( $status ) ); ?>

                    <div class="ld-expand-button ld-primary-background ld-compact ld-not-mobile" data-ld-expands="<?php echo esc_attr( 'ld-course-list-item-' . $quiz_id ); ?>">
                        <span class="ld-icon-arrow-down ld-icon"></span>
                    </div> <!--/.ld-expand-button-->

                    <div class="ld-expand-button ld-button-alternate ld-mobile-only" data-ld-expands="<?php echo esc_attr( 'ld-course-list-item-' . $quiz_id ); ?>"  data-ld-expand-text="<?php esc_html_e( 'Expand', 'learndash' ); ?>" data-ld-collapse-text="<?php esc_html_e( 'Collapse', 'learndash' ); ?>">
                        <span class="ld-icon-arrow-down ld-icon"></span>
                        <span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>
                    </div> <!--/.ld-expand-button-->

                </div> <!--/.ld-course-details-->

            </div> <!--/.ld-course-preview-->
            <div class="ld-item-list-item-expanded" data-ld-expand-id="<?php echo esc_attr( 'ld-course-list-item-' . $quiz_id ); ?>">
                <?php
                echo avg_score($user_id,$quiz_id);

                $assignments = learndash_get_course_assignments( $quiz_id, $user_id );

                if ( $assignments || ! empty( $quiz_attempts[ $id ] ) ) :
                    ?>

			<div class="ld-item-contents">

                        <?php
                        /**
                         * Filters Whether to show profiles quizzes.
                         *
                         * @since 2.5.8
                         *
                         * @param boolean $show_quizzes Whether to show profile quizzes.
                         */
                        if ( ! empty( $quiz_attempts[ $id ] ) && isset( $shortcode_atts['show_quizzes'] ) && true === (bool) $shortcode_atts['show_quizzes'] && apply_filters( 'learndash_show_profile_quizzes', $shortcode_atts['show_quizzes'] ) ) :
                            echo quizzes($user_id,$quiz_id,$id,$quiz_attempts);

                        endif;
                        ?>

                <?php
				if ( $assignments && ! empty( $assignments ) ) :

					learndash_get_template_part(
						'shortcodes/profile/assignments.php',
						array(
							'user_id'     => $user_id,
							'quiz_id'   => $quiz_id,
							'assignments' => $assignments,
						),
						true
					);

				endif;
				?>

			</div> <!--/.ld-course-contents-->

		<?php endif; ?>

	</div> <!--/.ld-course-list-item-expanded-->

</div> <!--/.ld-course-list-item-->
<?php
         return ob_get_clean();
    }
