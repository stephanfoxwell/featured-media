<?php
/*
   Plugin Name: Featured Media
   Plugin URI: https://github.com/stephansmith/typekitty
   Description: Creates Featured Audio/Video meta box for posts with the audio/video post type.
   Version: 1.0
   Author: Stephan Smith
   Author URI: http://stephan-smith.com
   License: GPL2
   */


class FeaturedMedia {
	
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	public $options;
	
	public $metaboxes = array(
	    'video_file' => array(
	    	'title'             => 'Featured Video',
	        'applicableto'      => 'post',
	        'location'          => 'normal',
	        'display_condition' => 'post-format-video',
	        'priority'          => 'default',
	        'fields'            => array(
	            'featured_video'  => array(
	                'title'         => 'Video File',
	                'type'          => 'video',
	                'description'   => ''
	            )
	        )
	    ),
	    'audio_file' => array(
	    	'title'             => 'Featured Audio',
	        'applicableto'      => 'post',
	        'location'          => 'normal',
	        'display_condition' => 'post-format-audio',
	        'priority'          => 'default',
	        'fields'            => array(
	            'featured_audio'  => array(
	                'title'         => 'Audio File',
	                'type'          => 'audio',
	                'description'   => ''
	            )
	        )
	    )
	);
	
	public function __construct() {
		
		add_action( 'admin_init', array( $this, 'add_post_format_metabox' ) );

		add_action( 'save_post', array( $this, 'save_metaboxes' ) );
		
		add_action( 'admin_print_scripts', array( $this, 'display_metaboxes' ), 1000 );
	}
	
	public function add_post_format_metabox() {
		
	    $metaboxes = $this->metaboxes;
	 
	    if ( ! empty( $metaboxes ) ) {
	        foreach ( $metaboxes as $id => $metabox ) {
	            add_meta_box( $id, $metabox['title'], array( $this, 'show_metaboxes' ), $metabox['applicableto'], $metabox['location'], $metabox['priority'], $id );
	        }
	    }
	}
	
	
	
	
	public function show_metaboxes( $post, $args ) {
	    
	    $metaboxes = $this->metaboxes;
	 
	    $custom = get_post_custom( $post->ID );
	    
	    $fields = $tabs = $metaboxes[ $args['id'] ]['fields'];
	 
	    /** Nonce **/
	    $output = '<input type="hidden" name="' . $args['id'] . '_post_format_meta_box_nonce" value="' . wp_create_nonce( basename( __FILE__ ) ) . '" />';
	 
	    if ( sizeof( $fields ) ) {
	        foreach ( $fields as $id => $field ) {
	            switch ( $field['type'] ) {
		            
	                case 'video' :
	                case 'audio' :
						
	                    $output .= '<input id="' . esc_attr( $id ) . '" type="hidden" name="' . esc_attr( $id ) . '" value="' . esc_attr( $custom[ $id ][0] ) . '">';
						$output .= '<div style="margin-top: 1em; margin-bottom:1em">' . $this->get_the_player( $post->ID, false ) . '</div>';
						$output .= '<button class="button upload_featured_media_button" type="button" data-mimetype="' . esc_attr( $field['type' ] ) . '" data-field="' . esc_attr( $id ) . '"><span class="dashicons dashicons-format-' . esc_attr( $field['type'] ) . '" style="margin-top: 3px;margin-right: 4px;"></span> Select ' . __( $field['title'] ) . '</button>';
	                							
						if ( $field['description'] != '' ) {
							$output .= '<p><em>' . __( $field['description'] ) . '</p>';
						}
						
	                    break;
	                
	                default:
	                	
	                	break;
	            }
	        }
	    }
	 
	    echo $output;
	}
	
	public function save_metaboxes( $post_id ) {
		
		$metaboxes = $this->metaboxes;
		
		if ( 'post' != $_POST['post_type'] )
			return $post_id;
	 
	    // check autosave
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	        return $post_id;
	 
	    // check permissions
	    if ( 'page' == $_POST['post_type'] ) {
	        if ( ! current_user_can( 'edit_page', $post_id ) )
	            return $post_id;
	    } elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
	        return $post_id;
	    }
	 
	    $post_type = get_post_type();
	 
	    // loop through fields and save the data
	    foreach ( $metaboxes as $id => $metabox ) {
			// verify nonce
			if ( ! wp_verify_nonce( $_POST[ $id . '_post_format_meta_box_nonce' ], basename( __FILE__ ) ) )
	        	return $post_id;
	        	
	        // check if metabox is applicable for current post type
	        if ( $metabox['applicableto'] == $post_type ) {
	            $fields = $metaboxes[$id]['fields'];
				
	            foreach ( $fields as $id => $field ) {
	                $old = get_post_meta( $post_id, $id, true );
	                $new = $_POST[$id];
	                
	                if ( $new && $new != $old ) {
	                    update_post_meta( $post_id, $id, $new );
	                }
	                elseif ( '' == $new && $old || ! isset( $_POST[$id] ) ) {
	                    delete_post_meta( $post_id, $id, $old );
	                }
	            }
	        }
	    }
	}
	
	function display_metaboxes() {
		
	    $metaboxes = $this->metaboxes;
	    
	    if ( get_post_type() == "post" ) :
	        ?>
	        <script type="text/javascript">// <![CDATA[
	            $ = jQuery;
	 
	            <?php
	            $formats = $ids = array();
	            foreach ( $metaboxes as $id => $metabox ) {
	                array_push( $formats, "'" . $metabox['display_condition'] . "': '" . $id . "'" );
	                array_push( $ids, "#" . $id );
	            }
	            ?>
	 
	            var formats = { <?php echo implode( ',', $formats );?> };
	            var ids = "<?php echo implode( ',', $ids ); ?>";
	            
	            function displayMetaboxes() {
	                // Hide all post format metaboxes
	                $(ids).hide();
	                // Get current post format
	                var selectedElt = $("input[name='post_format']:checked").attr("id");
	 
	                // If exists, fade in current post format metabox
	                if ( formats[selectedElt] )
	                    $("#" + formats[selectedElt]).fadeIn();
	            }
	 
	            $(function() {
	                // Show/hide metaboxes on page load
	                displayMetaboxes();
	 
	                // Show/hide metaboxes on change event
	                $("input[name='post_format']").change(function() {
	                    displayMetaboxes();
	                });
	            });
	            
	            var file_frame;
	 
	  jQuery('.upload_featured_media_button').live('click', function( event ){
	 
	    event.preventDefault();
	    
	    var button = jQuery( this );
	 
	    // If the media frame already exists, reopen it.
	    if ( file_frame ) {
	      file_frame.open();
	      return;
	    }
	    
	    // Create the media frame.
	    file_frame = wp.media.frames.file_frame = wp.media({
	      title: jQuery( this ).data( 'uploader_title' ),
	      button: {
	        text: jQuery( this ).data( 'uploader_button_text' ),
	      },
	      library: { type: button.attr( 'data-mimetype' ) },
	      multiple: false  // Set to true to allow multiple files to be selected
	    });
	 
	    // When an image is selected, run a callback.
	    file_frame.on( 'select', function() {
	      // We set multiple to false so only get one image from the uploader
	      attachment = file_frame.state().get('selection').first().toJSON();
	      
		  jQuery( '#' + jQuery( '.upload_featured_media_button' ).attr( 'data-field' ) ).val( attachment.id );
	 
	      // Do something with attachment.id and/or attachment.url here
	    });
	 
	    // Finally, open the modal
	    file_frame.open();
	  });
	 
	        // ]]></script>
	        <?php
	    endif;
	}
	
	
	
	/**
	 * Returns true if a blog has more than 1 category.
	 *
	 * @return bool
	 */
	function get_the_player( $post_ID, $echo = true ) {
		
		$return = '';
		
		if ( get_post_meta( $post_ID, 'featured_video', true ) != '' ) {
			$src = wp_get_attachment_url( get_post_meta( $post_ID, 'featured_video', true ) );
			$return = wp_video_shortcode( array( 'src'=>$src ) );
		}
		else if ( get_post_meta( $post_ID, 'featured_audio', true ) != '' ) {
			$src = wp_get_attachment_url( get_post_meta( $post_ID, 'featured_audio', true ) );
			$return = wp_audio_shortcode( array( 'src'=>$src ) );
		}
		
		if ( $echo ) {
			echo $return;
		}
		else {
			return $return;
		}
	}

}

if ( is_admin() )
	$FeaturedMedia = new FeaturedMedia();

 




