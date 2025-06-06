<?php

// Exit if the file is accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class blog_templates_main_menu {

    /**
    * @var array $options Stores the options for this plugin
    *
    * @since 1.0
    */
    var $menu_slug = 'blog_templates_main';

    var $page_id;

    var $updated_message = '';


	function __construct() {
		global $wp_version;
        
		// Add the super admin page
        add_action( 'network_admin_menu', array( $this, 'network_admin_page' ) );

        // Admin notices and data processing
        add_action( 'admin_init', array($this, 'admin_options_page_posted' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );

	}



    public function add_javascript($hook) {

    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
    		wp_enqueue_script( 'nbt-templates-js', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-templates.js', array( 'jquery' ) );
    		
    		wp_enqueue_style( 'nbt-settings-css', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css' );

    		wp_enqueue_script( 'jquery-ui-autocomplete' );

    		wp_enqueue_style( 'nbt-jquery-ui-styles', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/jquery-ui.css' );

			$params = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			);
			wp_localize_script( 'nbt-templates-js', 'export_to_text_js', $params );
    	}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_menu_page( __( 'Blog-Vorlagen', 'blog_templates' ), __( 'Blog-Vorlagen', 'blog_templates' ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'), 'div' );
    }

    /**
     * Adds the options subpanel
     *
     * @since 1.0
     */
    function pre_3_1_network_admin_page() {
        add_menu_page( __( 'Vorlagen', 'blog_templates' ), __( 'Vorlagen', 'blog_templates' ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'filter_plugin_actions' ) );
    }

    private function get_post_categories_list( $template ) {
    	ob_start();
    	?>
    		<ul id="nbt-post-categories-checklist">
				<li id="all-categories"><label class="selectit"><input class="all-selector" value="all-categories" type="checkbox" <?php checked( in_array( 'all-categories', $template['post_category'] ) ); ?> name="post_category[]" id="in-all-categories"> <strong><?php _e( 'Alle Kategorien', 'blog_templates' ); ?></strong></label></li>
				<?php
					switch_to_blog( $template['blog_id'] );
					wp_terms_checklist( 0, array( 'selected_cats' => $template['post_category'], 'checked_ontop' => 0 ) );
					restore_current_blog();
				?>
	 		</ul>
    	<?php
    	return ob_get_clean();
    }

    private function get_pages_list( $template ) {
    	switch_to_blog( $template['blog_id'] );
    	$pages = get_pages();
    	restore_current_blog();

    	ob_start();
    		?>
			<ul id="nbt-pages-checklist">
				<li id="all-nbt-pages"><label class="selectit"><input class="all-selector" value="all-pages" type="checkbox" <?php checked( in_array( 'all-pages', $template['pages_ids'] ) ); ?> name="pages_ids[]" id="in-all-nbt-pages"> <strong><?php _e( 'Alle Seiten', 'blog_templates' ); ?></strong></label></li>
				<?php foreach ( $pages as $page ): ?>
					<li id="page-<?php echo $page->ID; ?>">
						<label class="selectit">
							<input type="checkbox" name="pages_ids[]" id="in-page-<?php echo $page->ID; ?>" value="<?php echo $page->ID; ?>" <?php checked( ! in_array( 'all-pages', $template['pages_ids'] ) && in_array( $page->ID, $template['pages_ids'] ) ); ?>> <?php echo $page->post_title; ?>
						</label>
					</li>
				<?php endforeach; ?>
	 		</ul>
	 	<?php
    	return ob_get_clean();
    }


    /**
     * Adds settings/options page
     *
     * @since 1.0
     */
    function admin_options_page() {

	    $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

	    global $pagenow;
	    $url = $pagenow . '?page=' . $this->menu_slug;
			?>

			<div class="wrap">
			    <form method="post" id="options" enctype="multipart/form-data">
			        <?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); 
			        
			        if ( ! is_numeric( $t ) ) { ?>
			            <h2><?php _e( 'Blog-Vorlagen', 'blog_templates' ); ?></h2>
			            <?php 
			                $templates_table = new NBT_Templates_Table(); 
			                $templates_table->prepare_items();
			                $templates_table->display();
			            ?>
			            
			            <h2><?php _e('Neue Blog-Vorlage erstellen','blog_templates'); ?></h2>
			            <p><?php _e('Erstelle eine Blog-Vorlage basierend auf dem Blog Deiner Wahl! Auf diese Weise kannst Du (und andere Administratoren) alle Einstellungen des ausgewählten Blogs kopieren und andere Blogs erstellen, die fast exakte Kopien dieses Blogs sind. (Der Name, die URL usw. des Blogs ändern sich, sodass es sich nicht um eine 100%ige Kopie handelt.)','blog_templates'); ?></p>
			            <p><?php _e('Fülle einfach das folgende Formular aus und klicke auf "Blog-Vorlage erstellen!" um die Vorlage für die spätere Verwendung zu generieren!','blog_templates'); ?></p>
			            <table class="form-table">
			                <?php ob_start(); ?>
			                    <input name="template_name" type="text" id="template_name" class="regular-text"/>
			                <?php $this->render_row( __( 'Vorlagenname:', 'blog_templates' ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <input name="copy_blog_id" type="text" id="copy_blog_id" size="10" placeholder="<?php _e( 'Blog ID', 'blog_templates' ); ?>"/>
			                    <div class="ui-widget">
				                    <label for="search_for_blog"> <?php _e( 'Oder suche nach Blog-Pfad', 'blog_templates' ); ?> 
										<input type="text" id="search_for_blog" class="medium-text">
										<span class="description"><?php _e( 'Wenn der gesuchte Blog beispielsweise eine URL wie http://ablog.mydomain.com hat, kannst Du "ablog" eingeben.', 'blog_templates' ); ?></span>
				                    </label>
				                </div>
			                <?php $this->render_row( __( 'Blog ID:', 'blog_templates' ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <textarea class="large-text" name="template_description" type="text" id="template_description" cols="45" rows="5"></textarea>
			                <?php $this->render_row( __( 'Vorlagenbeschreibung:', 'blog_templates' ), ob_get_clean() ); ?>

			                <?php 
			                	ob_start();
			                    echo '<strong>' . __( 'Nachdem Du diese Vorlage hinzugefügt hast, werden auf dem Bearbeitungsbildschirm eine Reihe von Optionen angezeigt.', 'blog_templates' ) . '</strong>';
			                ?>
			                <?php $this->render_row( __( 'Mehr Optionen', 'blog_templates' ), ob_get_clean() ); ?>
			                
			            </table>
			            <p><?php _e('Bitte beachte, dass dadurch der von Dir ausgewählte Blog in einen Vorlagenblog umgewandelt wird. Alle Änderungen, die Du an diesem Blog vornimmst, ändern auch die Vorlage! Wir empfehlen, zu diesem Zweck spezielle "Vorlagen-Blogs" zu erstellen, damit Du nicht versehentlich neue Einstellungen, Inhalte oder Benutzer hinzufügst, die Du nicht in Deiner Vorlage haben möchtest.','blog_templates'); ?></p>
			            <p><?php printf( __( 'Wenn Du also ein dediziertes Vorlagenblog für diese Vorlage erstellen möchtest, erstelle <a href="%1$s">einen neuen Blog</a> und besuche diese Seite, um die Vorlage zu erstellen.','blog_templates' ), '<a href="' . ( get_bloginfo('version') >= 3 ) ? network_admin_url('site-new.php') : admin_url('wpmu-blogs.php') . '">'); ?></p>

			            <?php submit_button( __( 'Erstelle eine Blog-Vorlage!', 'blog_templates' ), 'primary', 'save_new_template' ); ?>
			            
			            
			        <?php
			            } else {
			            	$model = nbt_get_model();
			                $template = $model->get_template( $t );
			        ?>
			            
			            <h2><?php _e('Blog-Vorlage bearbeiten', 'blog_templates'); ?></h2>
			            <p><a href="<?php echo $url; ?>">&laquo; <?php _e('Zurück zu Blog-Vorlagen', 'blog_templates'); ?></a></p>
			            <input type="hidden" name="template_id" value="<?php echo $t; ?>" />
			            <div id="nbtpoststuff">
			            	<div id="post-body" class="metabox-holder columns-2">
			            		<div id="post-body-content">
					            	<table class="form-table">
						               	 <?php ob_start(); ?>
						                    <input name="template_name" type="text" id="template_name" class="regular-text" value="<?php esc_attr_e( $template['name'], 'blog_templates' );?>"/>
						                <?php $this->render_row( __( 'Vorlagenname:', 'blog_templates' ), ob_get_clean() ); ?>

						                <?php ob_start(); ?>
						                    <textarea class="widefat" name="template_description" id="template_description" cols="45" rows="5"><?php echo esc_textarea( $template['description'] );?></textarea>
						                <?php $this->render_row( __( 'Vorlagenbeschreibung', 'blog_templates' ), ob_get_clean() ); ?>

						                <?php 
						                    ob_start(); 
						                    $options_to_copy = array(
						                        'settings' => array(
						                        	'title' => __( 'Wordpress-Einstellungen, aktuelles Thema und aktive Plugins', 'blog_templates' ),
						                        	'content' => false
						                        ),
						                        'posts'    => array(
						                        	'title' => __( 'Beiträge', 'blog_templates' ),
						                        	'content' => $this->get_post_categories_list( $template )
						                        ),
						                        'pages'    => array(
						                        	'title' => __( 'Seiten', 'blog_templates' ),
						                        	'content' => $this->get_pages_list( $template )
						                        ),
						                        'terms'    => array(
						                        	'title' => __( 'Kategorien, Tags und Links', 'blog_templates' ),
						                        	'content' => false
						                        ),
						                        'users'    => array(
						                        	'title' => __( 'Benutzer', 'blog_templates' ),
						                        	'content' => false
						                        ),
						                        'menus'    => array(
						                        	'title' => __( 'Menüs', 'blog_templates' ),
						                        	'content' => false
						                        ),
						                        'files'    => array(
						                        	'title' => __( 'Dateien', 'blog_templates' ),
						                        	'content' => false
						                        )
						                    );

						                    foreach ( $options_to_copy as $key => $value ) : ?>
						                            <div id="nbt-<?php echo $key; ?>-to-copy" class="postbox">
														<h3 class="hndle">
															<label><input type="checkbox" name="to_copy[]" id="nbt-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php checked( in_array( $key, $template['to_copy'] ) ); ?>> <?php echo $value['title']; ?></label><br/>
														</h3>
														<?php if ( $value['content'] ): ?>
															<div class="inside">
																<?php echo $value['content']; ?>
															</div>
														<?php endif; ?>
													</div>

						                  	<?php endforeach; ?>
						                <?php $this->render_row( __( 'Was in den neuen Blog kopieren?', 'blog_templates' ), ob_get_clean() ); ?>

						                
						                <?php if ( is_plugin_active( 'ps-multisite-privacy/sitewide-privacy-options.php' ) ): ?>
						                    <?php ob_start(); ?>
						                        <input type='checkbox' name='copy_status' id='nbt-copy-status' <?php checked( ! empty( $template['copy_status'] ) ); ?>>
						                        <label for='nbt-copy-status'><?php _e( 'Überprüfe, ob Du auch den Blog-Status kopieren möchtest (öffentlich oder nicht).', 'blog_templates' ); ?></label>
						                    <?php $this->render_row( __( 'Status übernehmen?', 'blog_templates' ), ob_get_clean() ); ?>
						                <?php endif; ?>

						                <?php if ( apply_filters( 'nbt_activate_block_posts_feature', true ) ): ?>
							                <?php ob_start(); ?>
						                        <input type='checkbox' name='block_posts_pages' id='nbt-block-posts-pages' <?php checked( $template['block_posts_pages'] ); ?>>
						                        <label for='nbt-block-posts-pages'><?php _e( 'Wenn diese Option ausgewählt ist, dürfen die in der Vorlage enthaltenen Seiten und Beiträge nicht bearbeitet werden (auch nicht für den Blog-Administrator). Nur Superadministratoren können den Text kopierter Beiträge/Seiten bearbeiten.', 'blog_templates' ); ?></label>
						                    <?php $this->render_row( __( 'Beiträge/Seiten sperren', 'blog_templates' ), ob_get_clean() ); ?>
						                <?php endif; ?>

					                    <?php ob_start(); ?>
							            	<label>
							            		<input type="checkbox" name="update_dates" <?php checked( ! empty( $template['update_dates'] ) ); ?>>
							            		<?php _e( 'Wenn diese Option ausgewählt ist, werden die Daten der Beiträge/Seiten auf das Datum aktualisiert, an dem der Blog erstellt wird', 'blog_templates' ); ?>
							            	</label>
					                	<?php $this->render_row( __( 'Daten aktualisieren', 'blog_templates' ), ob_get_clean() ); ?>

					                    <?php 
					                    	ob_start();

					                    	if ( empty( $template['screenshot'] ) )
					                    		$img = nbt_get_default_screenshot_url($template['blog_id']);
					                    	else
					                    		$img = $template['screenshot'];

										?>
											<img src="<?php echo $img; ?>" style="max-width:100%;"/><br/>
											<p>
												<label for="screenshot">
													<?php _e( 'Neuen Screenshot hochladen', 'blog_templates' ); ?> 
													<input type="file" name="screenshot">
												</label>
												<?php submit_button( __( 'Screenshot zurücksetzen', 'blog_templates' ), 'secondary', 'reset-screenshot', true ); ?>
											</p>
					                    <?php $this->render_row( __( 'Screenshot', 'blog_templates' ), ob_get_clean() ); ?>
									</table>

						            <br/><br/>
						            <h2><?php _e('Erweiterte Optionen','blog_templates'); ?></h2>
						            
							       	<table class="form-table">

						                <?php ob_start(); ?>

						                <?php global $wpdb; ?>
						                <p><?php printf( __( 'Die hier aufgeführten Tabellen wurden wahrscheinlich von Plugins erstellt, die Du derzeit in diesem Blog aktiv hast oder ausgeführt hast. Wenn Du möchtest, dass die Daten aus diesen Tabellen in Deine neuen Blogs kopiert werden, setze ein Häkchen neben die Tabelle. Beachte, dass die einzigen hier angezeigten Tabellen mit %s beginnen. Dies ist das Standardtabellenpräfix für diesen bestimmten Blog. Bei Plugins, die dieser Konvention nicht folgen, werden die Tabellen hier nicht aufgelistet.','blog_templates' ), $wpdb->prefix ); ?></p><br/>

						                <?php

						                $additional_tables = nbt_get_additional_tables( $template['blog_id'] );

						                if ( ! empty( $additional_tables ) ) {
						                	foreach ( $additional_tables as $table ) {
						                		$table_name = $table['name'];
						                		$value = $table['prefix.name'];
						                		$checked = isset( $template['additional_tables'] ) && is_array( $template['additional_tables'] ) && in_array( $value, $template['additional_tables'] );
						                		?>
						                			<input type='checkbox' name='additional_template_tables[]' <?php checked( $checked ); ?> id="nbt-<?php echo esc_attr( $value ); ?>" value="<?php echo esc_attr( $value ); ?>">
						                			<label for="nbt-<?php echo esc_attr( $value ); ?>"><?php echo $table_name; ?></label><br/>
						                		<?php
						                	}
						                			
						                }
						                else {
						                	?>
						                		<p><?php _e('Für diesen Blog sind keine zusätzlichen Tabellen zum anzeigen vorhanden','blog_templates'); ?></p>
						                	<?php
						                }

						                
						                $this->render_row( __( 'Zusätzliche Tabellen', 'blog_templates' ), ob_get_clean() ); ?>


					            	</table>
					            	
					            </div>

					            <?php if ( apply_filters( 'nbt_activate_categories_feature', true ) ): ?>
						            <?php 
						            	$model = nbt_get_model();
						            	$categories = $model->get_templates_categories(); 
						            	$template_categories_tmp = $model->get_template_categories( $t );

						            	$template_categories = array();
						            	foreach ( $template_categories_tmp as $row ) {
						            		$template_categories[] = absint( $row['ID'] );
						            	}
						            ?>
						            <div id="postbox-container-1" class="postbox-container">
										<div id="side-sortables" class="meta-box-sortables ui-sortable">
											<div id="categorydiv" class="postbox ">
												<div class="handlediv" title=""><br></div><h3 class="hndle"><span><?php _e( 'Vorlagenkategorien', 'blog_templates' ); ?></span></h3>
												<div class="inside">
													<div id="taxonomy-category" class="categorydiv">
														<div id="category-all" class="tabs-panel">
															<ul id="templatecategorychecklist" class="categorychecklist form-no-clear">
																<?php foreach ( $categories as $category ): ?>
																	<li id="template-cat-<?php echo $category['ID']; ?>"><label class="selectit"><input value="<?php echo $category['ID']; ?>" <?php checked( in_array( $category['ID'], $template_categories ) ); ?> type="checkbox" name="template_category[]"> <?php echo $category['name']; ?></label></li>
																<?php endforeach; ?>
															</ul>
														</div>
													</div>
												</div>
											</div>
										</div>			
									</div>
								<?php endif; ?>
							</div>
				        </div>
		            </div>
		            <div class="clear"></div>
		            <?php submit_button( __( 'Vorlage speichern', 'blog_templates' ), 'primary', 'save_updated_template' ); ?>		            
		        <?php } ?>
		        
		        
		    </form>
		<?php
	    }

        /**
        * Adds the Settings link to the plugin activate/deactivate page
        *
        * @param array $links The ID of the blog to copy
        *
        * @since 1.0
        */
        function filter_plugin_actions( $links ) {
            global $wp_version;

            if ( version_compare( $wp_version , '3.0.9', '>' ) )
                $settings_link = '<a href="' . network_admin_url( 'settings.php?page=' . basename(__FILE__) ) . '">' . __( 'Einstellungen', 'blog_templates' ) . '</a>';
            elseif ( version_compare( $wp_version , '3.0', '<' ) )
                $settings_link = '<a href="wpmu-admin.php?page=' . basename(__FILE__) . '">' . __( 'Einstellungen', 'blog_templates' ) . '</a>';
            else
                $settings_link = '<a href="ms-admin.php?page=' . basename(__FILE__) . '">' . __( 'Einstellungen', 'blog_templates' ) . '</a>';
            array_unshift( $links, $settings_link ); // add before other links

            return $links;
        }

        private function render_row( $title, $markup ) {
            ?>
                <tr valign="top">
                    <th scope="row"><label for="site_name"><?php echo $title; ?></label></th>
                    <td>
                        <?php echo $markup; ?>          
                    </td>
                </tr>
            <?php
        }
        

        /**
         * Separated into its own function so we could include it in the init hook
         *
         * @since 1.0
         */
        function admin_options_page_posted() {
            if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug )
                return;

            $model = nbt_get_model();

            $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

            $save_template = ( ! empty( $_POST['reset-screenshot'] ) || ! empty( $_POST['save_updated_template'] ) );
            if( $save_template ) {

                if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                    die( __( 'Hoppla! Es ist ein Problem mit den von Dir veröffentlichten Daten aufgetreten. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );
                

                $args = array( 
	                'name' => stripslashes($_POST['template_name'] ),
	                'description' => stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ),
	                'to_copy' => isset( $_POST['to_copy'] ) ? (array)$_POST['to_copy'] : array(),
	                'additional_tables' => isset( $_POST['additional_template_tables'] ) ? $_POST['additional_template_tables'] : array(),
	                'copy_status' => isset( $_POST['copy_status'] ) ? true : false,
	                'block_posts_pages' => isset( $_POST['block_posts_pages'] ) ? true : false,
	                'update_dates' => isset( $_POST['update_dates'] ) ? true: false
	            );
	            if ( ! empty( $_FILES['screenshot']['tmp_name'] ) ) {
                	$uploaded_file = $_FILES['screenshot'];
                	$wp_filetype = wp_check_filetype_and_ext( $uploaded_file['tmp_name'], $uploaded_file['name'], false );
					if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) )
						wp_die( '<div class="error"><p>' . __( 'Die hochgeladene Datei ist kein gültiges Bild. Bitte versuche es erneut.', 'blog_templates' ) . '</p></div>' );

					$movefile = wp_handle_upload( $uploaded_file, array( 'test_form' => false ) );

					if ( $movefile ) {
					    $args['screenshot'] = $movefile['url'];
					}
                }
                else {
                	$template = $model->get_template( absint( $_POST['template_id'] ) );
                	$args['screenshot'] = ! empty( $template['screenshot'] ) ? $template['screenshot'] : false;
                }

                if ( ! empty( $_POST['reset-screenshot'] ) ) {
            		$args['screenshot'] = false;
            	}

            	// POST CATEGORIES
                $post_category = array( 'all-categories' );
                if ( isset( $_POST['post_category'] ) ) {
                	$categories = $_POST['post_category'];

                	if ( in_array( 'all-categories', $categories ) ) {
                		$post_category = array( 'all-categories' );
                	}
                	else {

                		$post_category = array();
                		foreach( $categories as $category ) {
                			if ( ! is_numeric( $category ) )
                				continue;

                			$post_category[] = absint( $category );
                		}
                	}
                }
                $args['post_category'] = $post_category; 

                // PAGES IDs
                $pages_ids = array( 'all-pages' );

                if ( isset( $_POST['pages_ids'] ) && is_array( $_POST['pages_ids'] ) ) {
                	if ( in_array( 'all-pages', $_POST['pages_ids'] ) ) {
                		$pages_ids = array( 'all-pages' );
                	}
                	else {
                		$pages_ids = array();
                		foreach( $_POST['pages_ids'] as $page_id ) {
                			if ( ! is_numeric( $page_id ) )
                				continue;

                			$pages_ids[] = absint( $page_id );
                		}
                	}
                }
                $args['pages_ids'] = $pages_ids;


                // TEMPLATE CATEGORY
                if ( ! isset( $_POST['template_category'] ) ) {
                	$template_category = array( $model->get_default_category_id() );
                }
                else {
                	$categories = $_POST['template_category'];

                	$template_category = array();
            		foreach( $categories as $category ) {
            			if ( ! is_numeric( $category ) )
            				continue;

            			$template_category[] = absint( $category );
            		}
                }

                $model->update_template_categories( $t, $template_category );

                $model->update_template( $t, $args );

                $this->updated_message =  __( 'Deine Änderungen wurden erfolgreich gespeichert!', 'blog_templates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif( !empty( $_POST['save_new_template'] ) ) {
                if ( ! wp_verify_nonce( $_POST['_nbtnonce'], 'blog_templates-update-options' ) )
                    wp_die( __( 'Hoppla! Es ist ein Problem mit den von Dir veröffentlichten Daten aufgetreten. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );

                if ( ! get_blog_details( (int) $_POST['copy_blog_id'] ) )
                    wp_die( __( 'Hoppla! Die von Dir gepostete Blog-ID ist falsch. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );

                if ( is_main_site( (int) $_POST['copy_blog_id'] ) )
                    wp_die( __( 'Hoppla! Die von Dir gepostete Blog-ID ist falsch. Du kannst die Hauptseite nicht vorlegen. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );

                $name = ( ! empty( $_POST['template_name'] ) ? stripslashes( $_POST['template_name'] ) : __( 'Eine Vorlage', 'blog_templates' ) );
                $description = ( ! empty( $_POST['template_description'] ) ? stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ) : '' );
                $blog_id = (int)$_POST['copy_blog_id'];

                $settings = array(
                    'to_copy' => array(),
                    'post_category' => array( 'all-categories' ),
                    'copy_status' => false,
                    'block_posts_pages' => false,
                    'pages_ids' => array( 'all-pages' ),
                    'update_dates' => false
                );

                $template_id = $model->add_template( $blog_id, $name, $description, $settings );

                $to_url = add_query_arg(
                	array(
                		'page' => $this->menu_slug,
                		't' => $template_id
                	),
                	network_admin_url( 'admin.php' )
                );
                wp_redirect( $to_url );

            } elseif( isset( $_GET['remove_default'] ) ) {

                if ( ! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-remove_default') )
                    wp_die( __( 'Hoppla! Es ist ein Problem mit den von Dir veröffentlichten Daten aufgetreten. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );
               	
               	$model->remove_default_template();
               	$settings = nbt_get_settings();

               	$settings['default'] = '';
               	nbt_update_settings( $settings );

                $this->updated_message = __( 'Die Standardvorlage wurde erfolgreich deaktiviert.', 'blog_templates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {

                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-make_default') )
                    wp_die( __( 'Hoppla! Es ist ein Problem mit den von Dir veröffentlichten Daten aufgetreten. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );

				$default_updated = $model->set_default_template( absint( $_GET['default'] ) );

				if ( $default_updated ) {
					$settings = nbt_get_settings();
					$settings['default'] = $_GET['default'];
	               	nbt_update_settings( $settings );
	            }

                $this->updated_message =  __( 'Die Standardvorlage wurde erfolgreich aktualisiert.', 'blog_templates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) {

                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-delete_template') )
                    wp_die( __( 'Hoppla! Es ist ein Problem mit den von Dir veröffentlichten Daten aufgetreten. Bitte gehe zurück und versuche es erneut. (Erstellt von PS Blog Templates)', 'blog_templates' ) );

                $model->delete_template( absint( $_GET['d'] ) );

                $this->updated_message =  __( 'Erfolg! Die Vorlage wurde erfolgreich gelöscht.', 'blog_templates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );
            }
        }

        public function show_admin_notice() {
        	?>
				<div class="updated">
					<p><?php echo $this->updated_message; ?></p>
				</div>
        	<?php
        }

}