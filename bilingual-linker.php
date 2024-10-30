<?php
/*
Plugin Name: Bilingual Linker
Plugin URI: http://wordpress.org/extend/plugins/bilingual-linker/
Description: Allows for the storage and retrieve of custom links for translation of post/pages
Version: 2.4
Author: Yannick Lefebvre
Author URI: http://ylefebvre.home.blog/
*/

require_once( ABSPATH . '/wp-admin/includes/template.php' );

function bilingual_linker_reset_options ( $setoptions = 'return' ) {
	$new_options['numberoflanguages']   = 1;
	$new_options['language1name']       = 'French';
	$new_options['language1langcode']   = 'French';
	$new_options['language1linktext']   = 'French';
	$new_options['language1beforelink'] = '';
	$new_options['language1afterlink']  = '';
	$new_options['language1defaulturl'] = 'wordpress.org';
	$new_options['hidesingle']          = false;
	$new_options['hidefrontpage']       = false;
	$new_options['hidesearchpage']      = false;
	$new_options['hidearchivepages']    = false;
	$new_options['hidecategorypages']   = false;
	$new_options['hidecustomcondition'] = '';

	if ( $setoptions == 'return_and_set' ) {
		add_option( 'BilingualLinkerGeneral', $new_options );
	}

	return $new_options;
}

function bilingual_linker_install() {
	global $wpdb;

	$table_name = $wpdb->get_blog_prefix() . 'posts_extrainfo';

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
		$postextradataquery = "select * from " . $wpdb->get_blog_prefix() . "posts_extrainfo";
		$extradata          = $wpdb->get_results( $postextradataquery, ARRAY_A );

		if ( $extradata ) {
			foreach ( $extradata as $datarec ) {
				update_post_meta( $datarec['post_id'], "bilingual-linker-other-lang-url-1", $datarec['post_otherlang_url'] );
			}
		}

		$wpdb->posts_extrainfo = $wpdb->get_blog_prefix() . 'posts_extrainfo';

		$result = $wpdb->query( "DROP TABLE `$wpdb->posts_extrainfo`" );
	}

	$wpdb->query( 'update ' . $wpdb->get_blog_prefix() . 'postmeta set meta_key = "bilingual-linker-other-lang-url-1" where meta_key = "bilingual-linker-other-lang-url"' );

	if ( get_option( 'BilingualLinkerGeneral' ) === false ) {
		bilingual_linker_reset_options( 'return_and_set' );
	}

	$creation_query =
		'CREATE TABLE IF NOT EXISTS ' . $wpdb->get_blog_prefix() . 'categorymeta (
        `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `category_id` bigint(20) unsigned NOT NULL DEFAULT "0",
        `meta_key` varchar(255) DEFAULT NULL,
        `meta_value` longtext,
        PRIMARY KEY (`meta_id`),
        KEY `meta_key` (`meta_key`)
        );';

	$wpdb->query( $creation_query );

}

if ( ! class_exists( 'BL_Admin' )) {

class BL_Admin {

function __construct() {
	add_action( 'admin_menu', array( $this, 'add_config_page' ), 100 );
	add_action( 'admin_init', array( $this, 'bl_admin_init' ), 100 );
	add_filter( 'admin_enqueue_scripts', array( $this, 'bl_admin_scripts' ) ); // the_posts gets triggered before wp_head
	add_action( 'edit_post', array( $this, 'bl_editsave_post_field' ) );
	add_action( 'save_post', array( $this, 'bl_editsave_post_field' ) );
	add_action( 'category_edit_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'category_add_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'post_tag_edit_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'post_tag_add_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'edited_category', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	add_action( 'edited_post_tag', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	add_action( 'created_category', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	add_action( 'created_post_tag', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
}

function bl_admin_init() {
	$this->bl_add_nav_menu_meta_box();

	add_action( 'admin_post_save_bl_options', array( $this, 'process_bl_options' ) );

	$taxonomy_list = get_taxonomies( );
	unset( $taxonomy_list['category'] );
	unset( $taxonomy_list['post_tag'] );
	unset( $taxonomy_list['nav_menu'] );
	unset( $taxonomy_list['post_format'] );
	unset( $taxonomy_list['link_category'] );

	foreach ( $taxonomy_list as $taxonomy_item ) {
		add_action( $taxonomy_item . '_edit_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
		add_action( $taxonomy_item . '_add_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
		add_action( 'edited_' . $taxonomy_item, array( $this, 'bl_save_category_new_fields' ), 10, 2 );
		add_action( 'edited_' . $taxonomy_item, array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	}
}

function bl_add_nav_menu_meta_box(){
	global $pagenow;
	if ( 'nav-menus.php' !== $pagenow ){
		return;
	}

	add_meta_box(
		'bilingual_linker_item_meta_box',
		__( 'Bilingual Linker', 'bilingual-linker-item' ),
		array( $this, 'bilingual_linker_box_render' ),
		'nav-menus',
		'side',
		'low'
	);
}

function bilingual_linker_box_render(){
	global $_nav_menu_placeholder, $nav_menu_selected_id;

	$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

	$gen_options = get_option( 'BilingualLinkerGeneral' );

	$final_default_url = 'http://test.com';
	?>

	<div id="bilingual-linker" class="posttypediv">
		<div id="tabs-panel-wishlist-login" class="tabs-panel tabs-panel-active">
			<ul id ="wishlist-login-checklist" class="categorychecklist form-no-clear">
				<li>
					<label class="menu-item-title">
						<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" checked value="-1"> Bilingual Link<br />
					</label>
					Language
					<select name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-lang-selector]" id="lang-selector">
					<?php $genoptions = get_option( 'BilingualLinkerGeneral' );

					for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) { ?>
						<option value="<?php echo $langcounter; ?>"><?php echo $genoptions[ 'language' . $langcounter . 'name' ]; ?></option>
					<?php } ?>
					</select>
					<input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
					<input type="hidden" class="menu-item-title-bilingual-linker" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="Bilingual Linker <?php echo $genoptions[ 'language1name' ]; ?> Menu">
					<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="<?php echo $final_default_url; ?>">
					<input type="hidden" class="menu-item-classes-bilingual-link" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-classes]" value="bilingual-link langid-1">
				</li>
			</ul>
		</div>
		<p class="button-controls">
			<span class="add-to-menu">
        				<input type="submit" <?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'bop-nav-search-box-item' ); ?>" name="add-post-type-menu-item" id="submit-bilingual-linker">
        				<span class="spinner"></span>
        			</span>
		</p>
	</div>

	<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( '#lang-selector' ).change( function() {
				var menu_classes = jQuery( '.menu-item-classes-bilingual-link' ).val();
				var classes = menu_classes.split( ' ' );
				var arrayLength = classes.length;
				for (var i = 0; i < arrayLength; i++) {
					if ( classes[i].indexOf( 'langid' ) >= 0 ) {
						var new_class = 'langid-' + jQuery( this ).val();
						classes[i] = new_class;
						var new_menu_label = 'Bilingual Linker ' + jQuery( this ).find("option:selected").text() + ' Menu';
						jQuery( '.menu-item-title-bilingual-linker' ).val( new_menu_label );
					}
				}
				var new_class_list = classes.join( ' ' );
				jQuery( '.menu-item-classes-bilingual-link' ).val( new_class_list );
			});
		});
	</script>
<?php
}

function bl_admin_scripts() {
	wp_enqueue_script( 'tiptip', plugins_url( 'tiptip/jquery.tipTip.minified.js', __FILE__ ), array( 'jquery' ), '', true );
	wp_enqueue_style( 'tiptipcss', plugins_url( 'tiptip/tipTip.css', __FILE__ ), array(), '', 'all' );
}

function bl_editsave_post_field( $post_id ) {
	if ( isset( $_POST['bl_otherlang_link_1'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-1', $_POST['bl_otherlang_link_1'] );
	}

	if ( isset( $_POST['bl_otherlang_link_2'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-2', $_POST['bl_otherlang_link_2'] );
	}

	if ( isset( $_POST['bl_otherlang_link_3'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-3', $_POST['bl_otherlang_link_3'] );
	}

	if ( isset( $_POST['bl_otherlang_link_4'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-4', $_POST['bl_otherlang_link_4'] );
	}

	if ( isset( $_POST['bl_otherlang_link_5'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-5', $_POST['bl_otherlang_link_5'] );
	}

}

function add_config_page() {
	if ( function_exists( 'add_submenu_page' ) ) {
		add_options_page( 'Bilingual Linker for Wordpress', 'Bilingual Linker', 'edit_pages', basename( __FILE__ ), array(
			$this,
			'config_page'
		) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_actions' ), 10, 2 );
	}

	if ( function_exists( 'get_post_types' ) ) {
		$post_types = get_post_types( array(), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( $post_type->show_ui ) {
				add_meta_box( 'bilinguallinker_meta_box', __( 'Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker' ), array(
					$this,
					'bl_postpage_edit_extra'
				), $post_type->name, 'normal', 'high' );
			}
		}
	} else {
		add_meta_box( 'bilinguallinker_meta_box', __( 'Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker' ), array(
			$this,
			'bl_postpage_edit_extra'
		), 'post', 'normal', 'high' );

		add_meta_box( 'bilinguallinker_meta_box', __( 'Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker' ), array(
			$this,
			'bl_postpage_edit_extra'
		), 'page', 'normal', 'high' );
	}
} // end add_BL_config_page()

function filter_plugin_actions( $links, $file ) {
	//Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="options-general.php?page=bilingual-linker.php">' . __( 'Settings', 'bilingual-linker' ) . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}

	return $links;
}

function bl_postpage_edit_extra( $post ) {
	$genoptions = get_option( 'BilingualLinkerGeneral' );

	$otherlangurl    = array();
	$otherlangurl[1] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-1", true );
	$otherlangurl[2] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-2", true );
	$otherlangurl[3] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-3", true );
	$otherlangurl[4] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-4", true );
	$otherlangurl[5] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-5", true );
	?>
	<table>

		<?php for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) { ?>
			<tr>
				<td style='width: 200px'>
					<?php
					$langname = $genoptions[ 'language' . $langcounter . 'name' ];
					if ( empty( $langname ) ) {
						$langname = 'Undefined Language';
					}
					echo $langname; ?> Link
				</td>
				<td>
					<input type="text" id="bl_otherlang_link_<?php echo $langcounter; ?>" name="bl_otherlang_link_<?php echo $langcounter; ?>" size="60" value="<?php echo $otherlangurl[ $langcounter ]; ?>" />
				</td>
			</tr>
		<?php } ?>

	</table>
<?php
}

function bl_category_new_fields( $tag ) {
	if ( is_object( $tag ) ) {
		$mode = "edit";
	} else {
		$mode = 'new';
	}

	$genoptions = get_option( 'BilingualLinkerGeneral' );

	$otherlangurl    = array();
	if ( !empty( $tag->taxonomy ) && !empty( $tag->term_id ) ) {
		$otherlangurl[1] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-1', true );
		$otherlangurl[2] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-2', true );
		$otherlangurl[3] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-3', true );
		$otherlangurl[4] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-4', true );
		$otherlangurl[5] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-5', true );
	} else {
		$otherlangurl[1] = '';
		$otherlangurl[2] = '';
		$otherlangurl[3] = '';
		$otherlangurl[4] = '';
		$otherlangurl[5] = '';
	}

	for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) {
		?>

		<?php if ( $mode == 'edit' ) {
			echo '<tr class="form-field">';
		} elseif ( $mode == 'new' ) {
			echo '<div class="form-field">';
		} ?>

		<?php if ( $mode == 'edit' ) {
			echo '<th scope="row" valign="top">';
		} ?>
		<label for="tag-language<?php echo $langcounter; ?>link">
			<?php $langname = $genoptions[ 'language' . $langcounter . 'name' ];
			if ( empty( $langname ) ) {
				$langname = "Undefined Language";
			}
			echo $langname; ?> Link</label>
		<?php if ( $mode == 'edit' ) {
			echo '</th>';
		} ?>

		<?php if ( $mode == 'edit' ) {
			echo '<td>';
		} ?>
		<input type="text" id="bl_otherlang_link_<?php echo $langcounter; ?>" name="bl_otherlang_link_<?php echo $langcounter; ?>" size="60" value="<?php echo $otherlangurl[ $langcounter ]; ?>" />
		<p class="description">Alternate Language link <?php echo $langcounter; ?> for Bilingual Linker</p>
		<?php if ( $mode == 'edit' ) {
			echo '</td>';
		} ?>
		<?php if ( $mode == 'edit' ) {
			echo '</tr>';
		} elseif ( $mode == 'new' ) {
			echo '</div>';
		} ?>
	<?php
	}
}

function bl_save_category_new_fields( $term_id, $tt_id ) {


	if ( ! $term_id ) {
		return;
	}

	if ( isset( $_POST['bl_otherlang_link_1'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-1', $_POST['bl_otherlang_link_1'] );
	}

	if ( isset( $_POST['bl_otherlang_link_2'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-2', $_POST['bl_otherlang_link_2'] );
	}

	if ( isset( $_POST['bl_otherlang_link_3'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-3', $_POST['bl_otherlang_link_3'] );
	}

	if ( isset( $_POST['bl_otherlang_link_4'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-4', $_POST['bl_otherlang_link_4'] );
	}

	if ( isset( $_POST['bl_otherlang_link_5'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-5', $_POST['bl_otherlang_link_5'] );
	}

}

function config_page() {
$genoptions = get_option( 'BilingualLinkerGeneral' );
$genoptions = wp_parse_args( $genoptions, bilingual_linker_reset_options( 'return' ) );
?>

<div class="wrap" id='bladmin' style='width:1000px'>
	<h2><?php _e( 'Bilingual Linker Configuration', 'bilingual-linker' ); ?> </h2>
	<a href="https://ylefebvre.home.blog/wordpress-plugins/bilingual-linker/" target="bilinguallinker"><img src="<?php echo plugins_url( '/icons/btn_donate_LG.gif', __FILE__ ); ?>" /></a> |
	<a target='blinstructions' href='http://wordpress.org/extend/plugins/bilingual-linker/installation/'><?php _e( 'Installation Instructions', 'bilingual-linker' ); ?></a> |
	<a href='http://wordpress.org/extend/plugins/bilingual-linker/faq/' target='llfaq'><?php _e( 'FAQ', 'bilingual-linker' ); ?></a> | <?php _e( 'Help also in tooltips', 'bilingual-linker' ); ?> |
	<a href='http://ylefebvre.home.blog/contact'><?php _e( 'Contact the Author', 'bilingual-linker' ); ?></a><br /><br />

	<div><strong>Usage Instructions</strong></div>
	<div>To use Bilingual Linker, just assign the web address for the translated version of a page or post when editing it in the Bilingual Linker box, then use the the_bilingual_link function to display a link to the translation version of the page or post.<br /><br />
		The function can be used without any arguments::<br />
		<strong>the_bilingual_link();</strong><br /><br />
		Optionally, it can be called with the following arguments:<br /><br />
		<strong>
			the_bilingual_link($args_array);</strong><br />
		    Where the following array parameters can be sent: language_id, post_id, link_text, before_link, after_link, default_url, echo, href_lang_code, hide_single, hide_front_page, hide_search_page, hide_archive_pages, hide_category_pages, url_only<br /><br />


		When using in The Loop in any template, you can use $post->ID as the second argument to pass the current post ID being processed.
	</div>

	<hr />
	<form method="post" action="admin-post.php">
		<input type="hidden" name="action"
		       value="save_bl_options" />

		<!-- Adding security through hidden referrer field -->
		<?php wp_nonce_field( 'bilinguallinker' ); ?>

		<table>
			<tr>
				<td>Number of languages</td>
				<td><select name="numberoflanguages" id="numberoflanguages">
						<?php for ( $counter = 1; $counter <= 5; $counter ++ ) { ?>
							<option value="<?php echo $counter; ?>" <?php selected( $counter, $genoptions['numberoflanguages'] ); ?>><?php echo $counter; ?></option>
						<?php } ?></select></td>
			</tr>
			<tr>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td><strong>Language Name</strong></td>
				<td><strong>HREFLang</strong></td>
				<td><strong>Default Translation URL</strong></td>
				<td><strong>Before Translation Link</strong></td>
				<td><strong>Translation Link Text</strong></td>
				<td><strong>After Translation Link</strong></td>

			</tr>
			<?php for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) { ?>
				<tr>
					<td>Language # <?php echo $langcounter; ?></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>name" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'name' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( $genoptions[ 'language' . $langcounter . 'name' ] );
						} ?>" /></td>

					<td>
						<input size=4 type="text" name="language<?php echo $langcounter; ?>langcode" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'langcode' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'langcode' ] ) ) {
							echo esc_attr( $genoptions[ 'language' . $langcounter . 'langcode' ] );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>defaulturl" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'defaulturl' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( $genoptions[ 'language' . $langcounter . 'defaulturl' ] );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>beforelink" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'beforelink' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( stripslashes( $genoptions[ 'language' . $langcounter . 'beforelink' ] ) );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>linktext" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'linktext' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( stripslashes( $genoptions[ 'language' . $langcounter . 'linktext' ] ) );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>afterlink" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'afterlink' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( stripslashes( $genoptions[ 'language' . $langcounter . 'afterlink' ] ) );
						} ?>" /></td>


				</tr>
				<tr><td></td></tr>
				<tr>
					<td colspan="2">Hide on single posts / pages if no translation available</td>
					<td><input type="checkbox" id="hidesingle" name="hidesingle" <?php checked( $genoptions['hidesingle'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on front page</td>
					<td><input type="checkbox" id="hidefrontpage" name="hidefrontpage" <?php checked( $genoptions['hidefrontpage'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on search page</td>
					<td><input type="checkbox" id="hidesearchpage" name="hidesearchpage" <?php checked( $genoptions['hidesearchpage'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on archive pages</td>
					<td><input type="checkbox" id="hidearchivepages" name="hidearchivepages" <?php checked( $genoptions['hidearchivepages'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on category pages</td>
					<td><input type="checkbox" id="hidecategorypages" name="hidecategorypages" <?php checked( $genoptions['hidecategorypages'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide when condition is true</td>
					<td colspan="5"><input style="width:100%" type="text" id="hidecustomcondition" name="hidecustomcondition" value="<?php echo stripslashes( $genoptions['hidecustomcondition'] ); ?>" /></td>
				</tr>
			<?php } ?>

		</table>
		<input type="submit" value="Submit" class="button-primary" />
	</form>

	<?php
	} // end config_page()

	function process_bl_options() {
		// Check that user has proper security level
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}

		// Check that nonce field created in configuration form
		// is present
		check_admin_referer( 'bilinguallinker' );

		// Retrieve original plugin options array
		$options = get_option( 'BilingualLinkerGeneral' );

		// Cycle through all text form fields and store their values
		// in the options array
		foreach (
			array(
				'numberoflanguages',
				'language1name',
				'language2name',
				'language3name',
				'language4name',
				'language5name',
				'language1langcode',
				'language2langcode',
				'language3langcode',
				'language4langcode',
				'language5langcode',
				'language1defaulturl',
				'language2defaulturl',
				'language3defaulturl',
				'language4defaulturl',
				'language5defaulturl',
				'language1beforelink',
				'language2beforelink',
				'language3beforelink',
				'language4beforelink',
				'language5beforelink',
				'language1afterlink',
				'language2afterlink',
				'language3afterlink',
				'language4afterlink',
				'language5afterlink',
				'language1linktext',
				'language2linktext',
				'language3linktext',
				'language4linktext',
				'language5linktext',
				'hidecustomcondition'
			) as $option_name
		) {
			if ( isset( $_POST[ $option_name ] ) ) {
				$options[ $option_name ] =
					$_POST[ $option_name ];
			}
		}

		foreach (
			array(
				'hidesingle',
				'hidefrontpage',
				'hidesearchpage',
				'hidearchivepages',
				'hidecategorypages'
			) as $option_name
		) {
			if ( isset( $_POST[ $option_name ] ) ) {
				$options[ $option_name ] = true;
			} else {
				$options[ $option_name ] = false;
			}
		}

		// Store updated options array to database
		update_option( 'BilingualLinkerGeneral', $options );

		// Redirect the page to the configuration form that was
		// processed
		wp_redirect( add_query_arg( 'page', 'bilingual-linker', admin_url( 'options-general.php' ) ) );
		exit;
	}

	} // end class BL_Admin

	} //endif


	function the_bilingual_link(
		$language_id = '', $post_id = '', $link_text = '',
		$before_link = '', $after_link = '',
		$default_url = '', $echo = true,
		$href_lang_code = '', $hide_single = false,
	    $hide_front_page = false, $hide_search_page = false,
	    $hide_archive_pages = false, $hide_category_pages = false
	) {
		$default_args = array(
			'language_id' => '',
			'post_id' => '',
			'link_text' => '',
			'before_link' => '',
			'after_link' => '',
			'default_url' => '',
			'echo' => true,
			'href_lang_code' => '',
			'hide_single' => false,
			'hide_front_page' => false,
			'hide_search_page' => false,
			'hide_archive_pages' => false,
			'hide_category_pages' => false,
			'url_only' => false
		);

		if ( is_array( $language_id ) || func_num_args() == 0 ) {
			if ( func_num_args() == 0 ) {
				$language_id = array();
			}
			$parsed_args = wp_parse_args( $language_id, $default_args );
			extract( $parsed_args );
		}

		$gen_options = get_option( 'BilingualLinkerGeneral' );
		$gen_options = wp_parse_args( $gen_options, bilingual_linker_reset_options( 'return' ) );

		$lang_id = ! empty( $language_id ) ? $language_id : 1;
		$href_lang_code = ! empty( $href_lang_code ) ? $href_lang_code : $gen_options[ 'language' . $lang_id . 'langcode' ];
		$code_before_link = ! empty( $before_link ) ? $before_link : stripslashes( $gen_options[ 'language' . $lang_id . 'beforelink' ] );
		$code_after_link = ! empty( $after_link ) ? $after_link : stripslashes( $gen_options[ 'language' . $lang_id . 'afterlink' ] );
		$final_link_text = ! empty( $link_text ) ? $link_text : stripslashes( $gen_options[ 'language' . $lang_id . 'linktext' ] );
		$final_default_url = ! empty( $default_url ) ? $default_url : $gen_options[ 'language' . $lang_id . 'defaulturl' ];
		$hide_single = ! empty( $hide_single ) ? $hide_single : $gen_options[ 'hidesingle' ];
		$hide_front_page = ! empty( $hide_front_page ) ? $hide_front_page : $gen_options[ 'hidefrontpage' ];
		$hide_search_page = ! empty( $hide_search_page ) ? $hide_search_page : $gen_options[ 'hidesearchpage' ];
		$hide_archive_pages = ! empty( $hide_archive_pages ) ? $hide_archive_pages : $gen_options[ 'hidearchivepages' ];
		$hide_category_pages = ! empty( $hide_category_pages ) ? $hide_category_pages : $gen_options[ 'hidecategorypages' ];
		
		$output = '';
		$url_output = '';

		if ( preg_match( "#https?://#", $final_default_url ) === 0 ) {
			$final_default_url = 'http://' . $final_default_url;
		}

		$other_lang_url = get_post_meta( get_the_ID(), 'bilingual-linker-other-lang-url-' . $lang_id, true );

		if ( is_home() && 0 != get_option( 'page_for_posts' ) ) {
			$other_lang_url = get_post_meta( get_option( 'page_for_posts' ), 'bilingual-linker-other-lang-url-' . $lang_id, true );
		}

		if ( is_front_page() && !$hide_front_page && ( 'page' != get_option( 'show_on_front' ) || ( 'page' == get_option( 'show_on_front' ) && empty( $other_lang_url ) ) ) ) {
			$output = $code_before_link . '<a href="' . $final_default_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $final_default_url;

		} elseif ( is_search() && !$hide_search_page ) {

			$search_url = add_query_arg( 's', $_GET['s'], $final_default_url );
			$output     = $code_before_link . '<a href="' . $search_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $search_url;

		} elseif ( is_home() || is_page() || is_single() || ( is_front_page() && 'page' == get_option( 'show_on_front' ) && !empty( $other_lang_url ) ) ) {
			if ( !empty( $other_lang_url ) ) {
				if ( preg_match( "#https?://#", $other_lang_url ) === 0 ) {
					$other_lang_url = 'http://' . $other_lang_url;
				}

				$output = $code_before_link . '<a href="' . $other_lang_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $other_lang_url;

			} elseif ( empty( $other_lang_url ) && !$hide_single && ! empty( $final_default_url ) ) {

				$output = $code_before_link . '<a href="' . $final_default_url . '" '. ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $final_default_url;
			}
		} elseif ( is_category() && !$hide_category_pages ) {

			$queried_object = get_queried_object();
			if ( !empty( $queried_object ) ) {
				$other_lang_url = get_metadata( $queried_object->taxonomy, get_query_var( 'cat' ), 'bilingual-linker-other-lang-url-' . $lang_id, true );
			} else {
				$other_lang_url = get_metadata( 'category', get_query_var( 'cat' ), 'bilingual-linker-other-lang-url-' . $lang_id, true );
			}

			if ( !empty( $other_lang_url ) ) {

				if ( preg_match( "#https?://#", $other_lang_url ) === 0 ) {
					$other_lang_url = 'http://' . $other_lang_url;
				}

				$output = $code_before_link . '<a href="' . $other_lang_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $other_lang_url;

			} elseif ( empty( $other_lang_url ) && ! empty( $final_default_url ) ) {

				$output = $code_before_link . '<a href="' . $final_default_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $final_default_url;
			}

		} else if ( ( is_archive() && ( is_date() || is_year() || is_month() ) ) && !$hide_archive_pages ) {

			if ( is_archive() ) {
				if ( is_year() ) {
					$archive_url = add_query_arg( 'year', get_query_var( 'year' ), $final_default_url );
				} elseif ( is_month() ) {
					$archive_url = add_query_arg( array(
						'year'     => get_query_var( 'year' ),
						'monthnum' => get_query_var( 'monthnum' )
					), $final_default_url );
				} elseif ( is_day() ) {
					$archive_url = add_query_arg( array(
						'year'     => get_query_var( 'year' ),
						'monthnum' => get_query_var( 'monthnum' ),
						'day'      => get_query_var( 'day' )
					), $final_default_url );
				}
			}

			$output = $code_before_link . '<a href="' . esc_url( $archive_url ) . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $archive_url;

		} elseif ( is_post_type_archive() ) {
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

			if ( $paged > 1 ) {
				$archive_url = add_query_arg( array(
					'post_type' => get_post_type(),
					'paged'     => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1
				), $final_default_url );
			} else {
				$archive_url = add_query_arg( array(
					'post_type' => get_post_type()
				), $final_default_url );
			}

			$output = $code_before_link . '<a href="' . esc_url( $archive_url ) . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $archive_url;
		} elseif ( !is_front_page() && !is_search() && !is_page() && !is_single() && !is_category() && !is_archive() && !is_post_type_archive() ) {
			$output = $code_before_link . '<a href="' . $final_default_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $final_default_url;
		}

		if ( !empty( $gen_options['hidecustomcondition'] ) && eval( stripslashes( $gen_options['hidecustomcondition'] ) ) ) {
			$output = '';
			$url_output = '';
		}

		if ( $echo == true ) {
			if ( !isset( $url_only ) || false == $url_only ) {
				echo $output;
			} elseif ( isset( $url_only ) && true == $url_only ) {
				echo $url_output;
			}
		} else {
			if ( !isset( $url_only ) || false == $url_only ) {
				return $output;
			} elseif ( isset( $url_only ) && true == $url_only ) {
				return $url_output;
			}
		}
	}

	register_activation_hook( __FILE__, 'bilingual_linker_install' );

	if ( is_admin() ) {
		$my_bl_admin = new BL_Admin();
	}

	add_action( 'init', 'bl_init' );

	function bl_init() {
		global $wpdb;

		$wpdb->categorymeta = $wpdb->get_blog_prefix() . 'categorymeta';

		if ( !function_exists( 'register_block_type' ) ) {
			return;
		}
		
		$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php' );
		wp_register_script( 'bl-lang-switcher', plugins_url( 'build/index.js', __FILE__ ), $asset_file['dependencies'], $asset_file['version'] );
		register_block_type( 'bilingual-linker/bl-lang-switcher', 
							 array( 'editor_script' => 'bl-lang-switcher',
							 		'parent' => array( 'core/navigation' ),
									'render_callback' => 'bl_nav_block_callback',
									'attributes' => array(
										'lang_id' => array(
										'type' => 'string',
										'default' => '1',
										),
									
									) ) );
	}

	function bl_nav_block_callback( $atts ) {
		extract( shortcode_atts( array(
			'lang_id' => '1'
			), $atts ) );

		$output = the_bilingual_link( array( 'echo' => false, 'language_id' => $lang_id ) );
		return $output;
	}

	add_shortcode( 'the-bilingual-link', 'bl_shortcode' );

	function bl_shortcode( $atts ) {
		$atts['echo'] = false;
		return the_bilingual_link( $atts );
	}

	add_filter( 'walker_nav_menu_start_el', 'bl_walker_nav_menu_start_el', 1, 4 );

	function bl_walker_nav_menu_start_el( $item_output, $item, $depth, $args ){
		if( $item->type != 'custom' ) {
			return $item_output;
		} elseif ( !in_array( 'bilingual-link', $item->classes ) ) {
			return $item_output;
		}

		$lang_id = 1;
		foreach ( $item->classes as $item_class ) {
			if ( strpos( $item_class, 'langid-' ) !== false ) {
				$lang_id = substr( $item_class, -1 );
			}
		}

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$item_output = $args->before;

		$item_output .= the_bilingual_link( array( 'echo' => false, 'language_id' => $lang_id ) );

		$item_output .= $args->after;
		
		return $item_output;
	}

add_action( 'wp_head', 'bl_wp_head_alternate_lang', 1 );

function bl_wp_head_alternate_lang() {
	$output = array();

	if ( function_exists( 'the_bilingual_link' ) ) {

		$args = array(
			"echo" => false,
			"url_only" => true
		);

		$gen_options = get_option( 'BilingualLinkerGeneral' );

		global $wp;
		$locale_array = explode( '_', get_locale() );
		$output[] = '<link rel="alternate" hreflang="' . $locale_array[0] . '" href="' . esc_url( home_url( $wp->request ) . '/' ) . '" />';

		if ( $gen_options['numberoflanguages'] > 0 ) {

			for ( $i = 1; $i <= $gen_options['numberoflanguages']; $i++ ) {

				$langArgs = $args;
				$langArgs['language_id'] = $i;
				$altLinkResult = the_bilingual_link( $langArgs );

				if ( $altLinkResult !== '' && $altLinkResult !== 'http://' && $altLinkResult !== 'https://' ) {
					$output[] = '<link rel="alternate" hreflang="' . $gen_options['language' . $i . 'langcode'] . '" href="' . esc_url( $altLinkResult ) . '/" />';
				}
			}
		}
	}

	if ( !empty( $output ) ) {
		echo "<!-- alternate languages -->\n" . implode( "\n", $output );
	}
}


add_action( 'rest_api_init', 'bl_rest_api_init' );

function bl_rest_api_init() {
	register_rest_route( 'bilingual-linker/v1', '/languagelist', array( 'methods' => 'GET', 'callback' => 'bl_rest_option_list', 'permission_callback' => '__return_true' ) );
}

function bl_rest_option_list( WP_REST_Request $request ) {
	$genoptions = get_option( 'BilingualLinkerGeneral' );

	$option_array = array();
	for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter++ ) {
		$option_array[$langcounter] = $genoptions[ 'language' . $langcounter . 'name' ];
	}
	$response = new WP_REST_Response( $option_array );
	return $response;
}

if ( ! function_exists('bilingual_linker_write_log')) {
	function bilingual_linker_write_log ( $log )  {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}