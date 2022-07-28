<?php
function avg_score($user_id, $quiz_id){
    $users_quiz_data = get_user_meta($user_id, '_sfwd-quizzes', true); 

    $count =0;

    foreach($users_quiz_data as $data){
        if($data['quiz']==$quiz_id){
            $count++;
            $percent += $data['percentage'];
        }
    }
    ob_start();
?>
<div class="ld-progress">
	<div class="ld-progress-heading">
		<div class="ld-progress-label"><?php printf( //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
			// translators: Course Progress Overview Label.
			esc_html_x( "Average Score : ", 'Course Progress Overview Label', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		); 
        echo round($percent/$count,2) . "%";
        ?>
        <?php //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeEnd ?>
		</div>
		
	</div> <!--/.ld-course-progress-heading-->

	
</div> <!--/.ld-course-progress-->

<?php
return ob_get_clean();
}