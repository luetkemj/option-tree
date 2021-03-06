<?php if ( ! defined( 'OT_VERSION' ) ) exit( 'No direct script access allowed' );
/**
 * Functions used only while viewing the admin UI.
 *
 * Limit loading these function only when needed 
 * and not in the front end.
 *
 * @package   OptionTree
 * @author    Derek Herman <derek@valendesigns.com>
 * @copyright Copyright (c) 2012, Derek Herman
 * @since     2.0
 */

/**
 * Setup the default admin styles
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_admin_styles' ) ) {

  function ot_admin_styles() {
  
    wp_enqueue_style( 'ot-admin-css', OT_URL . 'assets/css/ot-admin.css', false, OT_VERSION );
    
  }
  
}

/**
 * Setup the default admin scripts
 *
 * @uses      add_thickbox()          Include Thickbox for file uploads
 * @uses      wp_enqueue_script()     Add OptionTree scripts
 * @uses      wp_localize_script()    Used to include arbitrary Javascript data
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_admin_scripts' ) ) {

  function ot_admin_scripts() {

    /* enqueue admin scripts */
    add_thickbox();
    
    /* load the colorpicker */
    wp_enqueue_script( 'ot-colorpicker-js', OT_URL . 'assets/js/ot-colorpicker.js', array( 'jquery' ), OT_VERSION );
    
    /* load all the required scripts */
    wp_enqueue_script( 'ot-admin-js', OT_URL . 'assets/js/ot-admin.js', array( 'jquery', 'jquery-ui-tabs', 'jquery-ui-sortable', 'media-upload', 'thickbox' ), OT_VERSION );
    
    /* create localized JS array */
    $localized_array = array( 
      'ajax'                  => admin_url( 'admin-ajax.php' ),
      'upload_text'           => __( 'Send to OptionTree', 'option-tree' ),
      'remove_media_text'     => __( 'Remove Media', 'option-tree' ),
      'reset_agree'           => __( 'Are you sure you want to reset back to the defaults?', 'option-tree' ),
      'remove_no'             => __( 'You can\'t remove this! But you can edit the values.', 'option-tree' ),
      'remove_agree'          => __( 'Are you sure you want to remove this?', 'option-tree' ),
      'activate_layout_agree' => __( 'Are you sure you want to activate this layout?', 'option-tree' )
    );
    
    /* localized script attached to 'option_tree' */
    wp_localize_script( 'ot-admin-js', 'option_tree', $localized_array );

  }
  
}

/**
 * Validate the options by type
 *
 * @return    string
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_validate_setting' ) ) {

  function ot_validate_setting( $input, $type, $setting = null ) {
    
    /* exit early if missing data */
    if ( ! $input || ! $type )
      return $input;
    
    /* validate settings */
    if ( $type == 'text' ) {

      $input = esc_attr( $input );
    
    } else if ( in_array( $type, array( 'textarea', 'textarea-simple' ) ) && is_array( $input ) ) {
    
      $input = esc_html( stripcslashes( $input ) );
      
    } else if ( $type == 'upload' ) {
    
      $input = esc_url( $input );
      
    } else if ( in_array( $type, array( 'checkbox', 'radio', 'select' ) ) && is_array( $input ) ) {
      
      foreach( (array) $input as $key => $value ) {
        
        $input[$key] = esc_attr( $value );
        
      }
    
    } else if ( $type == 'css' ) {
      
      /* insert CSS into dynamic.css */
      ot_insert_css_with_markers( $setting['id'] );

    }
 
    return $input;
  }

}

/**
 * Setup default settings array.
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_default_settings' ) ) {

  function ot_default_settings() {
    global $wpdb, $table_prefix;
    
    if ( ! get_option( 'option_tree_settings' ) ) {
      
      $section_count = 0;
      $settings_count = 0;
      $settings = array();
      
      if ( $old_settings = $wpdb->get_results( "SELECT * FROM {$table_prefix}option_tree" ) ) {
        
        foreach ( $old_settings as $setting ) {
          
          /* heading is a section now */
          if ( $setting->item_type == 'heading' ) {
            
            /* add section to the sections array */
            $settings['sections'][$section_count]['id'] = $setting->item_id;
            $settings['sections'][$section_count]['title'] = $setting->item_title;
            
            /* save the last section id to use in creating settings */
            $section = $setting->item_id;
            
            /* increment the section count */
            $section_count++;
            
          } else {
            
            /* add setting to the settings array */
            $settings['settings'][$settings_count]['id'] = $setting->item_id;
            $settings['settings'][$settings_count]['label'] = $setting->item_title;
            $settings['settings'][$settings_count]['desc'] = $setting->item_desc;
            $settings['settings'][$settings_count]['section'] = $section;
            $settings['settings'][$settings_count]['type'] = ot_map_old_option_types( $setting->item_type );
            $settings['settings'][$settings_count]['std'] = '';
            $settings['settings'][$settings_count]['class'] = '';
            
            /* textarea rows */
            $rows = '';
            if ( in_array( $settings['settings'][$settings_count]['type'], array( 'css', 'textarea' ) ) ) {
              if ( (int) $setting->item_options > 0 ) {
                $rows = (int) $setting->item_options;
              } else {
                $rows = 15;
              }
            }
            $settings['settings'][$settings_count]['rows'] = $rows;
            
            /* post type */
            $post_type = '';
            if ( in_array( $settings['settings'][$settings_count]['type'], array( 'custom-post-type-select', 'custom-post-type-checkbox' ) ) ) {
              if ( '' != $setting->item_options ) {
                $post_type = $setting->item_options;
              } else {
                $post_type = 'post';
              }
            }
            $settings['settings'][$settings_count]['post_type'] = $post_type;
            
            /* choices */
            $choices = array();
            if ( in_array( $settings['settings'][$settings_count]['type'], array( 'checkbox', 'radio', 'select' ) ) ) {
              if ( '' != $setting->item_options ) {
                $choices = ot_convert_string_to_array( $setting->item_options );
              }
            }
            $settings['settings'][$settings_count]['choices'] = $choices;
            
            $settings_count++;
          }
        
        }
        
        /* make sure each setting has a section just incase */
        if ( isset( $settings['sections'] ) && isset( $settings['settings'] ) ) {
          foreach( $settings['settings'] as $k => $setting ) {
            if ( '' == $setting['section'] ) {
              $settings['settings'][$k]['section'] = $settings['sections'][0]['id'];
            }
          }
        }
          
      }
      
      /* if array if not properly formed create fallback settings array */
      if ( ! isset( $settings['sections'] ) || ! isset( $settings['settings'] ) ) {
        
        $settings = array(
          'sections' => array(
            array(
              'id'        => 'general',
              'title'     => __( 'General', 'option-tree' )
            )
          ),
          'settings' => array(
            array(
              'id'        => 'sample_text',
              'label'     => __( 'Sample Text Field Label', 'option-tree' ),
              'desc'      => __( 'Description for the sample text field.', 'option-tree' ),
              'section'   => 'general',
              'type'      => 'text',
              'std'       => '',
              'class'     => '',
              'rows'      => '',
              'post_type' => '',
              'choices'   => array()
            )
          )
        );
        
      }
      
      /* update the DB */
      update_option( 'option_tree_settings', $settings );
      
    }
    
  }

}

/**
 * Helper function to load filters for XML mime type.
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_add_xml_to_upload_filetypes' ) ) {

  function ot_add_xml_to_upload_filetypes() {
    
    add_filter( 'upload_mimes', 'ot_upload_mimes' );
    add_filter( 'wp_mime_type_icon', 'ot_xml_mime_type_icon', 10, 2 );
  
  }

}

/**
 * Filter 'upload_mimes' and add xml. 
 *
 * @param     array     $mimes An array of valid upload mime types
 * @return    array
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_upload_mimes' ) ) {

  function ot_upload_mimes( $mimes ) {
  
    $mimes['xml'] = 'application/xml';
    
    return $mimes;
    
  }

}

/**
 * Filters 'wp_mime_type_icon' and have xml display as a document.
 *
 * @param     string    $icon The mime icon
 * @param     string    $mime The mime type
 * @return    string
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_xml_mime_type_icon' ) ) {

  function ot_xml_mime_type_icon( $icon, $mime ) {
  
    if ( $mime == 'application/xml' || $mime == 'text/xml' )
      return wp_mime_type_icon( 'document' );
      
    return $icon;
    
  }

}

/**
 * Import data before the screen is displayed.
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_import' ) ) {

  function ot_import() {
    
    /* check and verify import xml nonce */
    if ( isset( $_POST['import_xml_nonce'] ) && wp_verify_nonce( $_POST['import_xml_nonce'], 'import_xml_form' ) ) {

      /* import input value */
      $file = isset( $_POST['import_xml'] ) ? esc_url( $_POST['import_xml'] ) : '';
      
      /* validate xml file */
      if ( preg_match( "/(.xml)$/i", $file ) && class_exists( 'SimpleXMLElement' ) && function_exists( 'file_get_contents' ) ) {

        if ( $rawdata = @file_get_contents( $file ) ) {
          
          $section_count = 0;
          $settings_count = 0;
          
          $section = '';
          
          $settings = array();
          $xml = new SimpleXMLElement( $rawdata );
      
          foreach ( $xml->row as $value ) {
            
            /* heading is a section now */
            if ( $value->item_type == 'heading' ) {
              
              /* add section to the sections array */
              $settings['sections'][$section_count]['id'] = (string) $value->item_id;
              $settings['sections'][$section_count]['title'] = (string) $value->item_title;
              
              /* save the last section id to use in creating settings */
              $section = (string) $value->item_id;
              
              /* increment the section count */
              $section_count++;
              
            } else {
              
              /* add setting to the settings array */
              $settings['settings'][$settings_count]['id'] = (string) $value->item_id;
              $settings['settings'][$settings_count]['label'] = (string) $value->item_title;
              $settings['settings'][$settings_count]['desc'] = (string) $value->item_desc;
              $settings['settings'][$settings_count]['section'] = $section;
              $settings['settings'][$settings_count]['type'] = ot_map_old_option_types( (string) $value->item_type );
              $settings['settings'][$settings_count]['std'] = '';
              $settings['settings'][$settings_count]['class'] = '';
              
              /* textarea rows */
              $rows = '';
              if ( in_array( $settings['settings'][$settings_count]['type'], array( 'css', 'textarea' ) ) ) {
                if ( (int) $value->item_options > 0 ) {
                  $rows = (int) $value->item_options;
                } else {
                  $rows = 15;
                }
              }
              $settings['settings'][$settings_count]['rows'] = $rows;
              
              /* post type */
              $post_type = '';
              if ( in_array( $settings['settings'][$settings_count]['type'], array( 'custom-post-type-select', 'custom-post-type-checkbox' ) ) ) {
                if ( '' != (string) $value->item_options ) {
                  $post_type = (string) $value->item_options;
                } else {
                  $post_type = 'post';
                }
              }
              $settings['settings'][$settings_count]['post_type'] = $post_type;
              
              /* choices */
              $choices = array();
              if ( in_array( $settings['settings'][$settings_count]['type'], array( 'checkbox', 'radio', 'select' ) ) ) {
                if ( '' != (string) $value->item_options ) {
                  $choices = ot_convert_string_to_array( (string) $value->item_options );
                }
              }
              $settings['settings'][$settings_count]['choices'] = $choices;
              
              $settings_count++;
            }
 
          }
          
          /* make sure each setting has a section just incase */
          if ( isset( $settings['sections'] ) && isset( $settings['settings'] ) ) {
            foreach( $settings['settings'] as $k => $setting ) {
              if ( '' == $setting['section'] ) {
                $settings['settings'][$k]['section'] = $settings['sections'][0]['id'];
              }
            }
          }
          
        }
      }
      
      /* default message */
      $message = 'failed';
      
      /* cleanup, save, & show success message */
      if ( isset( $settings ) && ! empty( $settings ) ) {
        
        /* delete file */
        if ( $file ) {
          global $wpdb;
          $attachmentid = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE guid='$file'" );
          wp_delete_attachment( $attachmentid, true );
        }
        
        /* update settings */
        update_option( 'option_tree_settings', $settings );
        
        /* set message */
        $message = 'success';
        
      }
      
      /* redirect */
      wp_redirect( add_query_arg( array( 'action' => 'import-xml', 'message' => $message ), $_POST['_wp_http_referer'] ) );
      exit;
      
    }
    
    /* check and verify import settings nonce */
    if ( isset( $_POST['import_settings_nonce'] ) && wp_verify_nonce( $_POST['import_settings_nonce'], 'import_settings_form' ) ) {

      /* textarea value */
      $textarea = isset( $_POST['import_settings'] ) ? unserialize( base64_decode( $_POST['import_settings'] ) ) : '';
      
      /* default message */
      $message = 'failed';
      
      /* is array: save & show success message */
      if ( is_array( $textarea ) ) {
        update_option( 'option_tree_settings', $textarea );
        $message = 'success';
      }
      
      /* redirect */
      wp_redirect( add_query_arg( array( 'action' => 'import-settings', 'message' => $message ), $_POST['_wp_http_referer'] ) );
      exit;
      
    }
    
    /* check and verify import theme options data nonce */
    if ( isset( $_POST['import_data_nonce'] ) && wp_verify_nonce( $_POST['import_data_nonce'], 'import_data_form' ) ) {

      /* textarea value */
      $textarea = isset( $_POST['import_data'] ) ? unserialize( base64_decode( $_POST['import_data'] ) ) : '';
      
      /* default message */
      $message = 'failed';
      
      /* is array: save & show success message */
      if ( is_array( $textarea ) ) {
        update_option( 'option_tree', $textarea );
        $message = 'success';
      }
      
      /* redirect accordingly */
      wp_redirect( add_query_arg( array( 'action' => 'import-data', 'message' => $message ), $_POST['_wp_http_referer'] ) );
      exit;
      
    }
    
    /* check and verify import layouts nonce */
    if ( isset( $_POST['import_layouts_nonce'] ) && wp_verify_nonce( $_POST['import_layouts_nonce'], 'import_layouts_form' ) ) {

      /* textarea value */
      $textarea = isset( $_POST['import_layouts'] ) ? unserialize( base64_decode( $_POST['import_layouts'] ) ) : '';
      
      /* default message */
      $message = 'failed';
      
      /* is array: save & show success message */
      if ( is_array( $textarea ) ) {
        if ( isset( $textarea['active_layout'] ) ) {
          update_option( 'option_tree', unserialize( base64_decode( $textarea[$textarea['active_layout']] ) ) );
        }
        update_option( 'option_tree_layouts', $textarea );
        $message = 'success';
      }
      
      /* redirect accordingly */
      wp_redirect( add_query_arg( array( 'action' => 'import-layouts', 'message' => $message ), $_POST['_wp_http_referer'] ) );
      exit;
      
    }
    
    return false;

  }
  
}

/**
 * Save settings array before the screen is displayed.
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_save_settings' ) ) {

  function ot_save_settings() {

    /* check and verify import settings nonce */
    if ( isset( $_POST['option_tree_settings_nonce'] ) && wp_verify_nonce( $_POST['option_tree_settings_nonce'], 'option_tree_settings_form' ) ) {

      /* settings value */
      $settings = isset( $_POST['option_tree_settings'] ) ? $_POST['option_tree_settings'] : '';
      
      /* validate sections */
      if ( isset( $settings['sections'] ) ) {
        
        /* fix numeric keys since drag & drop will change them */
        $settings['sections'] = array_values( $settings['sections'] );
        
        /* loop through sections */
        foreach( $settings['sections'] as $k => $section ) {
          
          /* remove from array if missing values */
          if ( ! $section['title'] && ! $section['id'] ) {
          
            unset( $settings['sections'][$k] );
            
          } else {
            
            /* missing title set to unfiltered ID */
            if ( ! $section['title'] ) {
              
              $settings['sections'][$k]['title'] = esc_attr( $section['id'] );
            
            /* missing ID set to title */ 
            } else if ( ! $section['id'] ) {
              
              $section['id'] = esc_attr( $section['title'] );
              
            }
            
            /* sanitize ID once everything has been checked first */
            $settings['sections'][$k]['id'] = ot_sanitize_option_id( $section['id'] );
            
          }
          
        }
      
      }
      
      /* validate settings by looping over array as many times as it takes */
      if ( isset( $settings['settings'] ) ) {
        
        $settings['settings'] = ot_validate_settings_array( $settings['settings'] );
        
      }
      
      /* validate contextual_help */
      if ( isset( $settings['contextual_help']['content'] ) ) {
        
        /* fix numeric keys since drag & drop will change them */
        $settings['contextual_help']['content'] = array_values( $settings['contextual_help']['content'] );
        
        /* loop through content */
        foreach( $settings['contextual_help']['content'] as $k => $content ) {
          
          /* remove from array if missing values */
          if ( ! $content['title'] && ! $content['id'] ) {
          
            unset( $settings['contextual_help']['content'][$k] );
            
          } else {
            
            /* missing title set to unfiltered ID */
            if ( ! $content['title'] ) {
              
              $settings['contextual_help']['content'][$k]['title'] = esc_attr( $content['id'] );
            
            /* missing ID set to title */ 
            } else if ( ! $content['id'] ) {
              
              $content['id'] = esc_attr( $content['title'] );
              
            }
            
            /* sanitize ID once everything has been checked first */
            $settings['contextual_help']['content'][$k]['id'] = ot_sanitize_option_id( $content['id'] );
            
          }
          
          /* validate textarea description */
          if ( isset( $content['content'] ) ) {
          
            $settings['contextual_help']['content'][$k]['content'] = esc_html( stripcslashes( $content['content'] ) );
            
          }
          
        }
      
      }
      
      /* validate contextual_help sidebar */
      if ( isset( $settings['contextual_help']['sidebar'] ) ) {
      
        $settings['contextual_help']['sidebar'] = esc_html( stripcslashes( $settings['contextual_help']['sidebar'] ) );
        
      }
      
      /* default message */
      $message = 'failed';
      
      /* is array: save & show success message */
      if ( is_array( $settings ) ) {
        update_option( 'option_tree_settings', $settings );
        $message = 'success';
      }
      
      /* redirect */
      wp_redirect( add_query_arg( array( 'action' => 'save-settings', 'message' => $message ), $_POST['_wp_http_referer'] ) );
      exit;
      
    }
    
    return false;

  }
  
}

/**
 * Validate the settings array before save.
 *
 * This function will loop over the settings array as many 
 * times as it takes to validate every sub setting.
 *
 * @param     array     $settings The array of settings.
 * @return    array
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_validate_settings_array' ) ) {

  function ot_validate_settings_array( $settings = array() ) {
    
    /* validate settings */
    if ( count( $settings ) > 0 ) {
      
      /* fix numeric keys since drag & drop will change them */
      $settings = array_values( $settings );
      
      /* loop through settings */
      foreach( $settings as $k => $setting ) {
        
        /* remove from array if missing values */
        if ( ! $setting['label'] && ! $setting['id'] ) {
        
          unset( $settings[$k] );
          
        } else {
          
          /* missing label set to unfiltered ID */
          if ( ! $setting['label'] ) {
            
            $settings[$k]['label'] = esc_attr( $setting['id'] );
          
          /* missing ID set to label */ 
          } else if ( ! $setting['id'] ) {
            
            $setting['id'] = esc_attr( $setting['label'] );
            
          }
          
          /* sanitize ID once everything has been checked first */
          $settings[$k]['id'] = ot_sanitize_option_id( $setting['id'] );
          
        }
        
        /* validate textarea description */
        if ( isset( $setting['desc'] ) ) {
        
          $settings[$k]['desc'] = esc_html( stripcslashes( $setting['desc'] ) );
          
        }
        
        /* validate choices */
        if ( isset( $setting['choices'] ) ) {
          
          /* loop through choices */
          foreach( $setting['choices'] as $ck => $choice ) {
            
            /* remove from array if missing values */
            if ( ! $choice['label'] && ! $choice['value'] ) {
        
              unset( $setting['choices'][$ck] );
              
            } else {
              
              /* missing label set to unfiltered ID */
              if ( ! $choice['label'] ) {
                
                $setting['choices'][$ck]['label'] = esc_attr( $choice['value'] );
              
              /* missing value set to label */ 
              } else if ( ! $choice['value'] ) {
                
                $setting['choices'][$ck]['value'] = ot_sanitize_option_id( $choice['label'] );
                
              }
              
            }
            
          }
          
          /* update keys and push new array values */
          $settings[$k]['choices'] = array_values( $setting['choices'] );
          
        }
        
        /* validate sub settings */
        if ( isset( $setting['settings'] ) ) {

          $settings[$k]['settings'] = ot_validate_settings_array( $setting['settings'] );
          
        }

      }
    
    }
    
    return $settings;
    
  }

}

/**
 * Save layouts array before the screen is displayed.
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_modify_layouts' ) ) {

  function ot_modify_layouts() {

    /* check and verify modify layouts nonce */
    if ( isset( $_POST['option_tree_modify_layouts_nonce'] ) && wp_verify_nonce( $_POST['option_tree_modify_layouts_nonce'], 'option_tree_modify_layouts_form' ) ) {
      
      /* previous layouts value */
      $option_tree_layouts = get_option( 'option_tree_layouts' );
      
      /* new layouts value */
      $layouts = isset( $_POST['option_tree_layouts'] ) ? $_POST['option_tree_layouts'] : '';
      
      /* rebuild layout array */
      $rebuild = array();
      
      /* validate layouts */
      if ( is_array( $layouts ) && ! empty( $layouts ) ) {
        
        /* setup active layout */
        if ( isset( $layouts['active_layout'] ) && '' != $layouts['active_layout'] ) {
          $rebuild['active_layout'] = $layouts['active_layout'];
        }
        
        /* add new and overwrite active layout */
        if ( isset( $layouts['_add_new_layout_'] ) && '' != $layouts['_add_new_layout_'] ) {
          $rebuild['active_layout'] = ot_sanitize_layout_id( $layouts['_add_new_layout_'] );
          $rebuild[$rebuild['active_layout']] = base64_encode( serialize( get_option( 'option_tree' ) ) );
        }
        
        $first_layout = '';
        
        /* loop through layouts */
        foreach( $layouts as $key => $layout ) {
          
          /* skip over active layout key */
          if ( $key == 'active_layout' )
            continue;
          
          /* check if the key exists then set value */
          if ( isset( $option_tree_layouts[$key] ) ) {
            $rebuild[$key] = $option_tree_layouts[$key];
            if ( '' == $first_layout ) {
              $first_layout = $key;
            }
          }
          
        }
        
        
        if ( ! isset( $rebuild[$rebuild['active_layout']] ) && '' != $first_layout ) {
          $rebuild['active_layout'] = $first_layout;
        }
        
      }
      
      /* default message */
      $message = 'failed';
      
      /* is array: save & show success message */
      if ( count( $rebuild ) > 1 ) {
        
        /* rebuild the theme options */
        $rebuild_option_tree = unserialize( base64_decode( $rebuild[$rebuild['active_layout']] ) );
        if ( is_array( $rebuild_option_tree ) ) {
          update_option( 'option_tree', $rebuild_option_tree );
        }
        
        /* rebuild the layouts */
        update_option( 'option_tree_layouts', $rebuild );
        
        /* change message */
        $message = 'success';
        
      } else if ( count( $rebuild ) <= 1 ) {
        
        /* delete layouts option */
        delete_option( 'option_tree_layouts' );
        
        /* change message */
        $message = 'deleted';
        
      }
      
      /* redirect */
      if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'ot-theme-options' ) {
        $query_args = add_query_arg( array( 'settings-updated' => 'true' ), remove_query_arg( array( 'action', 'message' ), $_POST['_wp_http_referer'] ) );
      } else {
        $query_args = add_query_arg( array( 'action' => 'save-layouts', 'message' => $message ), $_POST['_wp_http_referer'] );
      }
      wp_redirect( $query_args );
      exit;
      
    }
    
    return false;

  }
  
}

/**
 * Helper function to display alert messages.
 *
 * @param     array     Page array
 * @return    mixed
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_alert_message' ) ) {

  function ot_alert_message( $page = array() ) {
    
    if ( empty( $page ) )
      return false;
    
    $current_page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
    $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
    $message = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '';
    
    /* theme options messages */
    if ( $current_page == 'ot-theme-options' ) {
    
      if ( isset( $_POST['action'] ) && $_POST['action'] == 'reset' ) {
      
        return '<div id="message" class="updated fade below-h2"><p>' . $page['reset_message'] . '</p></div>';
        
      } else if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true ) {  
       
        return '<div id="message" class="updated fade below-h2"><p>' . $page['updated_message'] . '</p></div>';
        
      }
      
    }
    
    /* settings messages */
    if ( $current_page == 'ot-settings' ) {
      
      if ( $action == 'save-settings' ) {
      
        if ( $message == 'success' ) {
          
          return '<div id="message" class="updated fade below-h2"><p>' . __( 'Settings updated.', 'option-tree' ) . '</p></div>';
          
        } else if ( $message == 'failed' ) {
          
          return '<div id="message" class="error fade below-h2"><p>' . __( 'Settings could not be saved.', 'option-tree' ) . '</p></div>';
          
        }
        
      } else if ( $action == 'import-xml' || $action == 'import-settings' ) {
        
        if ( $message == 'success' ) {
          
          return '<div id="message" class="updated fade below-h2"><p>' . __( 'Settings Imported.', 'option-tree' ) . '</p></div>';
          
        } else if ( $message == 'failed' ) {
          
          return '<div id="message" class="error fade below-h2"><p>' . __( 'Settings could not be imported.', 'option-tree' ) . '</p></div>';
          
        }
      } else if ( $action == 'import-data' ) {
        
        if ( $message == 'success' ) {
          
          return '<div id="message" class="updated fade below-h2"><p>' . __( 'Data Imported.', 'option-tree' ) . '</p></div>';
          
        } else if ( $message == 'failed' ) {
          
          return '<div id="message" class="error fade below-h2"><p>' . __( 'Data could not be imported.', 'option-tree' ) . '</p></div>';
          
        }
      
      } else if ( $action == 'import-layouts' ) {
        
        if ( $message == 'success' ) {
          
          return '<div id="message" class="updated fade below-h2"><p>' . __( 'Layouts Imported.', 'option-tree' ) . '</p></div>';
          
        } else if ( $message == 'failed' ) {
          
          return '<div id="message" class="error fade below-h2"><p>' . __( 'Layouts could not be imported.', 'option-tree' ) . '</p></div>';
          
        }
             
      } else if ( $action == 'save-layouts' ) {
        
        if ( $message == 'success' ) {
          
          return '<div id="message" class="updated fade below-h2"><p>' . __( 'Layouts Updated.', 'option-tree' ) . '</p></div>';
          
        } else if ( $message == 'failed' ) {
          
          return '<div id="message" class="error fade below-h2"><p>' . __( 'Layouts could not be updated.', 'option-tree' ) . '</p></div>';
          
        }
             
      }
      
    }
    
    return false;
  }
  
}

/**
 * Setup the default option types.
 *
 * The returned option types are filterable so you can add your own.
 * This is not a task for a beginner as you'll need to add the function
 * that displays the option to the user and validate the saved data.
 *
 * @return    array
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_option_types_array' ) ) {

  function ot_option_types_array() {
  
    return apply_filters( 'ot_option_types_array', array( 
      'background'                => 'Background',
      'category-checkbox'         => 'Category Checkbox',
      'category-select'           => 'Category Select',
      'checkbox'                  => 'Checkbox',
      'colorpicker'               => 'Colorpicker',
      'css'                       => 'CSS',
      'custom-post-type-checkbox' => 'Custom Post Type Checkbox',
      'custom-post-type-select'   => 'Custom Post Type Select',
      'list-item'                 => 'List Item',
      'measurement'               => 'Measurement',
      'page-checkbox'             => 'Page Checkbox',
      'page-select'               => 'Page Select',
      'post-checkbox'             => 'Post Checkbox',
      'post-select'               => 'Post Select',
      'radio'                     => 'Radio',
      'radio-image'               => 'Radio Image',
      'select'                    => 'Select',
      'tag-checkbox'              => 'Tag Checkbox',
      'tag-select'                => 'Tag Select',
      'text'                      => 'Text',
      'textarea'                  => 'Textarea',
      'textarea-simple'           => 'Textarea Simple',
      'textblock'                 => 'Textblock',
      'textblock-titled'          => 'Textblock Titled',
      'typography'                => 'Typography',
      'upload'                    => 'Upload'
    ) );
    
  }
}

/**
 * Map old option types for rebuilding XML and Table data.
 *
 * @param     string      $type The old option type
 * @return    string      The new option type
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_map_old_option_types' ) ) {

  function ot_map_old_option_types( $type = '' ) {
    
    if ( ! $type ) 
      return 'text';
      
    $types = array(
      'background'	      => 'background',
      'category'          => 'category-select',
      'categories'        => 'category-checkbox',
      'checkbox'          => 'checkbox',
      'colorpicker'       => 'colorpicker',
      'css'	              => 'css',
      'custom_post'       => 'custom-post-type-select',
      'custom_posts'      => 'custom-post-type-checkbox',                     
      'input'             => 'text',
      'measurement'       => 'measurement',
      'page'              => 'page-select',
      'pages'             => 'page-checkbox',
      'post'              => 'post-select',
      'posts'             => 'post-checkbox',
      'radio'             => 'radio',
      'select'            => 'select',
      'slider'            => 'list-item',
      'tag'               => 'tag-select',
      'tags'              => 'tag-checkbox',
      'textarea'          => 'textarea',
      'textblock'         => 'textblock',
      'typography'	      => 'typography',
      'upload'            => 'upload'
    );
    
    if ( isset( $types[$type] ) )
      return $types[$type];
    
    return false;
    
  }
}


/**
 * Recognized font styles
 *
 * Returns an array of all recognized font styles.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_font_styles' ) ) {

  function ot_recognized_font_styles( $field_id = '' ) {
  
    return apply_filters( 'ot_recognized_font_styles', array(
      'normal'  => 'Normal',
      'italic'  => 'Italic',
      'oblique' => 'Oblique',
      'inherit' => 'Inherit'
    ), $field_id );
    
  }

}

/**
 * Recognized font weights
 *
 * Returns an array of all recognized font weights.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_font_weights' ) ) {

  function ot_recognized_font_weights( $field_id = '' ) {
    
    return apply_filters( 'ot_recognized_font_weights', array(
      'normal'    => 'Normal',
      'bold'      => 'Bold',
      'bolder'    => 'Bolder',
      'lighter'   => 'Lighter',
      '100'       => '100',
      '200'       => '200',
      '300'       => '300',
      '400'       => '400',
      '500'       => '500',
      '600'       => '600',
      '700'       => '700',
      '800'       => '800',
      '900'       => '900',
      'inherit'   => 'Inherit'
    ), $field_id );
  
  }
  
}

/**
 * Recognized font variants
 *
 * Returns an array of all recognized font variants.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_font_variants' ) ) {

  function ot_recognized_font_variants( $field_id = '' ) {
  
    return apply_filters( 'ot_recognized_font_variants', array(
      'normal'      => 'Normal',
      'small-caps'  => 'Small Caps',
      'inherit'     => 'Inherit'
    ), $field_id );
  
  }
  
}

/**
 * Recognized font families
 *
 * Returns an array of all recognized font families.
 * Keys are intended to be stored in the database
 * while values are ready for display in html.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_font_families' ) ) {

  function ot_recognized_font_families( $field_id = '' ) {
  
    return apply_filters( 'ot_recognized_font_families', array(
      'arial'     => 'Arial',
      'georgia'   => 'Georgia',
      'helvetica' => 'Helvetica',
      'palatino'  => 'Palatino',
      'tahoma'    => 'Tahoma',
      'times'     => '"Times New Roman", sans-serif',
      'trebuchet' => 'Trebuchet',
      'verdana'   => 'Verdana'
    ), $field_id );
    
  }

}

/**
 * Recognized background repeat
 *
 * Returns an array of all recognized background repeat values.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_background_repeat' ) ) {
  
  function ot_recognized_background_repeat( $field_id = '' ) {
  
    return apply_filters( 'ot_recognized_background_repeat', array(
      'no-repeat' => 'No Repeat',
      'repeat' 		=> 'Repeat All',
      'repeat-x'  => 'Repeat Horizontally',
      'repeat-y' 	=> 'Repeat Vertically',
      'inherit'   => 'Inherit'
    ), $field_id );
    
  }
  
}

/**
 * Recognized background attachment
 *
 * Returns an array of all recognized background attachment values.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_background_attachment' ) ) {

  function ot_recognized_background_attachment( $field_id = '' ) {
  
    return apply_filters( 'ot_recognized_background_attachment', array(
      "fixed"   => "Fixed",
      "scroll"  => "Scroll",
      "inherit" => "Inherit"
    ), $field_id );
    
  }

}

/**
 * Recognized background position
 *
 * Returns an array of all recognized background position values.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_recognized_background_position' ) ) {

  function ot_recognized_background_position( $field_id = '' ) {
  
    return apply_filters( 'ot_recognized_background_position', array(
      "left top"      => "Left Top",
      "left center"   => "Left Center",
      "left bottom"   => "Left Bottom",
      "center top"    => "Center Top",
      "center center" => "Center Center",
      "center bottom" => "Center Bottom",
      "right top"     => "Right Top",
      "right center"  => "Right Center",
      "right bottom"  => "Right Bottom"
    ), $field_id );
    
  }

}

/**
 * Measurement Units
 *
 * Returns an array of all available unit types.
 * Renamed in version 2.0 to avoid name collisions.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_measurement_unit_types' ) ) {
  
  function ot_measurement_unit_types( $field_id = '' ) {
  
    return apply_filters( 'ot_measurement_unit_types', array(
      'px' => 'px',
      '%'  => '%',
      'em' => 'em',
      'pt' => 'pt'
    ), $field_id );
    
  }

}

/**
 * Radio Images default array.
 *
 * Returns an array of all available radio images.
 * You can filter this function to change the images
 * on a per option basis.
 *
 * @uses      apply_filters()
 *
 * @return    array
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_radio_images' ) ) {
  
  function ot_radio_images( $field_id = '' ) {
  
    return apply_filters( 'ot_radio_images', array(
      array(
        'value'   => 'left-sidebar',
        'label'   => __( 'Left Sidebar', 'option-tree' ),
        'src'     => OT_URL . 'assets/images/layout/left-sidebar.png'
      ),
      array(
        'value'   => 'right-sidebar',
        'label'   => __( 'Right Sidebar', 'option-tree' ),
        'src'     => OT_URL . 'assets/images/layout/right-sidebar.png'
      ),
      array(
        'value'   => 'full-width',
        'label'   => __( 'Full Width (no sidebar)', 'option-tree' ),
        'src'     => OT_URL . 'assets/images/layout/full-width.png'
      ),
      array(
        'value'   => 'dual-sidebar',
        'label'   => __( 'Dual Sidebar', 'option-tree' ),
        'src'     => OT_URL . 'assets/images/layout/dual-sidebar.png'
      ),
      array(
        'value'   => 'left-dual-sidebar',
        'label'   => __( 'Left Dual Sidebar', 'option-tree' ),
        'src'     => OT_URL . 'assets/images/layout/left-dual-sidebar.png'
      ),
      array(
        'value'   => 'right-dual-sidebar',
        'label'   => __( 'Right Dual Sidebar', 'option-tree' ),
        'src'     => OT_URL . 'assets/images/layout/right-dual-sidebar.png'
      )
    ), $field_id );
    
  }

}

/**
 * Inserts CSS with field_id markers.
 *
 * Inserts CSS into a dynamic.css file, placing it between
 * BEGIN and END field_id markers. Replaces existing marked info, 
 * but still retains surrounding data.
 *
 * @param     string  $field_id The CSS option field ID.
 * @return    bool    True on write success, false on failure.
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_insert_css_with_markers' ) ) {

  function ot_insert_css_with_markers( $field_id = '' ) {
    
    /* missing $field_id string */
    if ( '' == $field_id )
      return;
    
    /* path to the dynamic.css file */
    $filepath = get_stylesheet_directory() . '/dynamic.css';
    
    /* allow filter on path */
    $filepath = apply_filters( 'css_option_file_path', $filepath, $field_id );
        
    /* insert CSS into file */
    if ( file_exists( $filepath ) && $f = @fopen( $filepath, 'w' ) ) {
      
      /* Get options & set CSS value */
      $options     = get_option( 'option_tree' );
      $insertion   = ot_normalize_css( $options[$field_id] );
      $regex       = "/{{([a-zA-Z0-9\_\-\#\|\=]+)}}/";
      $marker      = $field_id;
      
      /* Match custom CSS */
      preg_match_all( $regex, $insertion, $matches );
      
      /* Loop through CSS */
      foreach( $matches[0] as $option ) {
        
        $set_flag     = true;
        $value        = '';
        $option_id    = str_replace( array( '{{', '}}' ), '', $option );
        $option_array = explode( '|', $option_id );
        
        /* get value array by key or option_id */
        if ( is_array( $option_array ) && isset( $options[$option_array[0]] ) ) {
          
          $value = $options[$option_array[0]];

        } else if ( isset( $options[$option_id] ) ) {
          
          $value = $options[$option_id];
          
        }
        
        if ( is_array( $value ) ) {
          
          if ( ! isset( $option_array[1] ) ) {
          
            /* Measurement */
            if ( isset( $value[0] ) && isset( $value[1] ) ) {
              
              /* set $value with measurement properties */
              $value = $value[0].$value[1];
              
            /* typography */
            } else if ( ot_array_keys_exists( $value, array( 'font-color', 'font-family', 'font-style', 'font-variant', 'font-weight', 'font-size' ) ) ) {
              $font = array();
              
              if ( ! empty( $value['font-color'] ) )
                $font[] = "font-color: " . $value['font-color'] . ";";
              
              if ( ! empty( $value['font-family'] ) ) {
                foreach ( ot_recognized_font_families() as $key => $v ) {
                  if ( $key == $value['font-family'] ) {
                    $font[] = "font-family: " . $v . ";";
                  }
                }
              }
              
              if ( ! empty( $value['font-size'] ) )
                $font[] = "font-size: " . $value['font-size'] . ";";
              
              if ( ! empty( $value['font-style'] ) )
                $font[] = "font-style: " . $value['font-style'] . ";";
              
              if ( ! empty( $value['font-variant'] ) )
                $font[] = "font-variant: " . $value['font-variant'] . ";";
              
              if ( ! empty( $value['font-weight'] ) )
                $font[] = "font-weight: " . $value['font-weight'] . ";";
              
              /* set $value with font properties or empty string */
              $value = ! empty( $font ) ? implode( "\n", $font ) : '';
              
            /* background */
            } else if ( ot_array_keys_exists( $value, array( 'background-color', 'background-image', 'background-repeat', 'background-attachment', 'background-position' ) ) ) {
              $bg = array();
              
              if ( ! empty( $value['background-color'] ) )
                $bg[] = $value['background-color'];
                
              if ( ! empty( $value['background-image'] ) )
                $bg[] = 'url("' . $value['background-image'] . '")';
                
              if ( ! empty( $value['background-repeat'] ) )
                $bg[] = $value['background-repeat'];
                
              if ( ! empty( $value['background-attachment'] ) )
                $bg[] = $value['background-attachment'];
                
              if ( ! empty( $value['background-position'] ) )
                $bg[] = $value['background-position'];
              
              /* set $re$valueturn with background properties or empty string */
              $value = ! empty( $bg ) ? 'background: ' . implode( " ", $bg ) . ';' : '';
            }
          
          } else {
          
            $value = $value[$option_array[1]];
            
          }
         
        }
        
        /* insert CSS, even if the value is empty */
       	$insertion = stripslashes( str_replace( $option, $value, $insertion ) );
      }
  	
      /* file doesn't exist */
      if ( ! file_exists( $filepath ) ) {
        $markerdata = '';
      /* file exist, create array from the lines of code */
      } else {
        $markerdata = explode( "\n", implode( '', file( $filepath ) ) );
      }
      
      $foundit = false;
      
      /* has array of lines */
      if ( $markerdata ) {
        
        $searching = true;
        
        /* foreach line of code */
        foreach ( $markerdata as $n => $markerline ) {
          
          /* found begining of marker, set $searching to false  */
          if ( strpos( $markerline, '/* BEGIN ' . $marker . ' */' ) !== false )
            $searching = false;
          
          /* $searching is true, keep rewrite each line of CSS  */
          if ( $searching ) {
            if ( $n + 1 < count( $markerdata ) )
              fwrite( $f, "{$markerline}\n" );
            else
              fwrite( $f, "{$markerline}" );
          }
          
          /* found end marker write code */
          if ( strpos( $markerline, '/* END ' . $marker . ' */' ) !== false ) {
            fwrite( $f, "/* BEGIN {$marker} */\n" );
            fwrite( $f, "{$insertion}\n" );
            fwrite( $f, "/* END {$marker} */\n" );
            $searching = true;
            $foundit = true;
          }
          
        }
        
      }
      
      /* nothing inserted, write code. DO IT, DO IT! */
      if ( ! $foundit ) {
        fwrite( $f, "/* BEGIN {$marker} */\n" );
        fwrite( $f, "{$insertion}\n" );
        fwrite( $f, "/* END {$marker} */\n" );
      }
      
      /* close file */
      fclose( $f );
      return true;
    }
    
    return false;

  }

}

/**
 * Remove old CSS.
 *
 * Removes CSS when the textarea is empty, but still retains surrounding styles.
 *
 * @param     string  $field_id The CSS option field ID.
 * @return    bool    True on write success, false on failure.
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_remove_css_with_markers' ) ) {

  function ot_remove_old_css( $field_id = '' ) {
    
    /* missing $field_id string */
    if ( '' == $field_id )
      return false;
    
    /* path to the dynamic.css file */
    $filepath = get_stylesheet_directory() . '/dynamic.css';
    
    /* allow filter on path */
    $filepath = apply_filters( 'css_option_file_path', $filepath, $field_id );
    
    /* remove CSS from file */
    if ( is_writeable( $filepath ) ) {
      
      /* get each line in the file */
      $markerdata = explode( "\n", implode( '', file( $filepath ) ) );
      
      /* can't write to the file return false */
      if ( ! $f = @fopen( $filepath, 'w' ) )
        return false;
      
      /* has array of lines */
      if ( $markerdata ) {
        
        $searching = true;
        
        /* foreach line of code */
        foreach ( $markerdata as $n => $markerline ) {
          
          /* found begining of marker, set $searching to false  */
          if ( strpos( $markerline, '/* BEGIN ' . $field_id . ' */' ) !== false )
            $searching = false;
          
          /* $searching is true, keep rewrite each line of CSS  */
          if ( $searching ) {
            if ( $n + 1 < count( $markerdata ) )
              fwrite( $f, "{$markerline}\n" );
            else
              fwrite( $f, "{$markerline}" );
          }
          
          /* found end marker delete old CSS */
          if ( strpos( $markerline, '/* END ' . $field_id . ' */' ) !== false ) {
            fwrite( $f, "" );
            $searching = true;
          }
          
        }
        
      }
      
      /* close file */
      fclose( $f );
      return true;
      
    }
    
    return false;
    
  }
  
}

/**
 * Normalize CSS
 *
 * Normalize & Convert all line-endings to UNIX format.
 *
 * @param     string    $css
 * @return    string
 *
 * @access    public
 * @since     1.1.8
 * @updated   2.0
 */
if ( ! function_exists( 'ot_normalize_css' ) ) {

  function ot_normalize_css( $css ) {
    
    /* Normalize & Convert */
    $css = str_replace( "\r\n", "\n", $css );
    $css = str_replace( "\r", "\n", $css );
    
    /* Don't allow out-of-control blank lines */
    $css = preg_replace( "/\n{2,}/", "\n\n", $css );
    
    return $css;
  }
  
}

/**
 * Helper function to loop over the option types.
 *
 * @param    array    $type The current option type.
 *
 * @return   string
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_loop_through_option_types' ) ) {

  function ot_loop_through_option_types( $type = '', $child = false ) {
  
    $content = '';
    $types = ot_option_types_array();
    
    if ( $child )
      unset($types['list-item']);
    
    foreach( $types as $key => $value )
      $content.= '<option value="' . $key . '" ' . selected( $type, $key, false ) . '>'  . $value . '</option>';
    
    return $content;
    
  }
  
}

/**
 * Helper function to loop over choices.
 *
 * @param    string     $name The form element name.
 * @param    array      $choices The array of choices.
 *
 * @return   string
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_loop_through_choices' ) ) {

  function ot_loop_through_choices( $name, $choices = array() ) {
    
    $content = '';
    
    foreach( $choices as $key => $choice )
      $content.= '<li class="ui-state-default list-choice">' . ot_choices_view( $name, $key, $choice ) . '</li>';
    
    return $content;
  }
  
}

/**
 * Helper function to loop over sub settings.
 *
 * @param    string     $name The form element name.
 * @param    array      $settings The array of settings.
 *
 * @return   string
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_loop_through_sub_settings' ) ) {

  function ot_loop_through_sub_settings( $name, $settings = array() ) {
    
    $content = '';
    
    foreach( $settings as $key => $setting )
      $content.= '<li class="ui-state-default list-sub-setting">' . ot_settings_view( $name, $key, $setting ) . '</li>';
    
    return $content;
  }
  
}

/**
 * Helper function to display sections.
 *
 * This function is used in AJAX to add a new section
 * and when section have already been added and saved.
 *
 * @param    int      $key The array key for the current element.
 * @param    array    An array of values for the current section.
 *
 * @return   void
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_sections_view' ) ) {

  function ot_sections_view( $name, $key, $section = array() ) {
  
    return '
    <div class="option-tree-setting is-section">
      <div class="open">' . ( isset( $section['title'] ) ? esc_attr( $section['title'] ) : 'Section ' . ( $key + 1 ) ) . '</div>
      <div class="button-section">
        <a href="javascript:void(0);" class="option-tree-setting-edit option-tree-ui-button left-item" title="Edit">
          <span class="icon pencil">Edit</span>
        </a>
        <a href="javascript:void(0);" class="option-tree-setting-remove option-tree-ui-button right-item" title="Delete">
          <span class="icon trash-can">Delete</span>
        </a>
      </div>
      <div class="option-tree-setting-body">
        <div class="format-settings">
          <div class="format-setting type-text">
            <div class="description"><strong>Section Title</strong>: Displayed as a menu item on the Theme Options page.</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][title]" value="' . ( isset( $section['title'] ) ? $section['title'] : '' ) . '" class="widefat option-tree-ui-input option-tree-setting-title section-title" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-text">
            <div class="description"><strong>Section ID</strong>: A unique lower case alphanumeric string, underscores allowed [a-z0-9_].</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][id]" value="' . ( isset( $section['id'] ) ? $section['id'] : '' ) . '" class="widefat option-tree-ui-input section-id" autocomplete="off" />
            </div>
          </div>
        </div>
      </div>
    </div>';
    
  }

}

/**
 * Helper function to display settings.
 *
 * This function is used in AJAX to add a new setting
 * and when settings have already been added and saved.
 *
 * @param    int      $key The array key for the current element.
 * @param    array    An array of values for the current section.
 *
 * @return   void
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_settings_view' ) ) {

  function ot_settings_view( $name, $key, $setting = array() ) {
    
    $child = ( strpos( $name, '][settings]') !== false ) ? true : false;

    return '
    <div class="option-tree-setting">
      <div class="open">' . ( isset( $setting['label'] ) ? esc_attr( $setting['label'] ) : 'Setting ' . ( $key + 1 ) ) . '</div>
      <div class="button-section">
        <a href="javascript:void(0);" class="option-tree-setting-edit option-tree-ui-button left-item" title="Edit">
          <span class="icon pencil">Edit</span>
        </a>
        <a href="javascript:void(0);" class="option-tree-setting-remove option-tree-ui-button right-item" title="Delete">
          <span class="icon trash-can">Delete</span>
        </a>
      </div>
      <div class="option-tree-setting-body">
        <div class="format-settings">
          <div class="format-setting type-text wide-desc">
            <div class="description"><strong>Label</strong>: Displayed as the label of a form element on the Theme Options page.</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][label]" value="' . ( isset( $setting['label'] ) ? esc_attr( $setting['label'] ) : '' ) . '" class="widefat option-tree-ui-input option-tree-setting-title" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-text wide-desc">
            <div class="description"><strong>ID</strong>: A unique lower case alphanumeric string, underscores allowed.</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][id]" value="' . ( isset( $setting['id'] ) ? esc_attr( $setting['id'] ) : '' ) . '" class="widefat option-tree-ui-input" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-select wide-desc">
            <div class="description"><strong>Type</strong>: Choose one of the available option types from the dropdown.</div>
            <div class="format-setting-inner">
              <select name="' . $name . '[' . $key . '][type]" value="' . ( isset( $setting['type'] ) ? esc_attr( $setting['type'] ) : '' ) . '" class="option-tree-ui-select">
              ' . ( isset( $setting['type'] ) ? ot_loop_through_option_types( $setting['type'], $child ) : ot_loop_through_option_types( '',$child ) ) . '                     
              
              </select>
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-textarea wide-desc">
            <div class="description"><strong>Description</strong>: Enter a detailed description for the users to read on the Theme Options page, HTML is allowed. This is also where you enter content for both the Textblock & Textblock Titled option types.</div>
            <div class="format-setting-inner">
              <textarea class="textarea" rows="10" cols="40" name="' . $name . '[' . $key . '][desc]">' . ( isset( $setting['desc'] ) ? esc_html( $setting['desc'] ) : '' ) . '</textarea>
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-textblock wide-desc">
            <div class="description"><strong>Choices</strong>: This will only affect the following option types: Checkbox, Radio, Select & Select Image.</div>
            <div class="format-setting-inner">
              <ul class="option-tree-setting-wrap option-tree-sortable" rel="' . $name . '[' . $key . ']">
                ' . ( isset( $setting['choices'] ) ? ot_loop_through_choices( $name . '[' . $key . ']', $setting['choices'] ) : '' ) . '
              </ul>
              <a href="javascript:void(0);" class="option-tree-choice-add option-tree-ui-button hug-left">Add Choice</a>
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-textblock wide-desc">
            <div class="description"><strong>Settings</strong>: This will only affect the List Item option type.</div>
            <div class="format-setting-inner">
              <ul class="option-tree-setting-wrap option-tree-sortable" rel="' . $name . '[' . $key . ']">
                ' . ( isset( $setting['settings'] ) ? ot_loop_through_sub_settings( $name . '[' . $key . '][settings]', $setting['settings'] ) : '' ) . '
              </ul>
              <a href="javascript:void(0);" class="option-tree-list-item-setting-add option-tree-ui-button hug-left">Add Setting</a>
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-text wide-desc">
            <div class="description">' . __( '<strong>Standard</strong>: Setting the standard value for your option only works for some option types. Read the <code>OptionTree->Documentation</code> for more information on which ones.', 'option-tree' ) . '</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][std]" value="' . ( isset( $setting['std'] ) ? esc_attr( $setting['std'] ) : '' ) . '" class="widefat option-tree-ui-input" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-text wide-desc">
            <div class="description"><strong>Rows</strong>: Enter a numeric value for the number of rows in your textarea. This will only affect the following option types: CSS, Textarea, & Textarea Simple.</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][rows]" value="' . ( isset( $setting['rows'] ) ? esc_attr( $setting['rows'] ) : '' ) . '" class="widefat option-tree-ui-input" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-text wide-desc">
            <div class="description"><strong>Post Type</strong>: Add a comma separated list of post type like \'post,page\'. This will only affect the following option types: Custom Post Type Checkbox, & Custom Post Type Select.</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][post_type]" value="' . ( isset( $setting['post_type'] ) ? esc_attr( $setting['post_type'] ) : '' ) . '" class="widefat option-tree-ui-input" autocomplete="off" />
            </div>
          </div>
        </div>
      </div>
    </div>
    ' . ( ! $child ? '<input type="hidden" class="hidden-section" name="' . $name . '[' . $key . '][section]" value="' . ( isset( $setting['section'] ) ? $setting['section'] : '' ) . '" />' : '' );
  
  }

}

/**
 * Helper function to display setting choices.
 *
 * This function is used in AJAX to add a new choice
 * and when choices have already been added and saved.
 *
 * @param    string   $name The form element name.
 * @param    array    $key The array key for the current element.
 * @param    array    An array of values for the current choice.
 *
 * @return   void
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_choices_view' ) ) {

  function ot_choices_view( $name, $key, $choice = array() ) {
  
    return '
    <div class="option-tree-setting">
      <div class="open">' . ( isset( $choice['label'] ) ? esc_attr( $choice['label'] ) : 'Choice ' . ( $key + 1 ) ) . '</div>
      <div class="button-section">
        <a href="javascript:void(0);" class="option-tree-setting-edit option-tree-ui-button left-item" title="Edit">
          <span class="icon pencil">Edit</span>
        </a>
        <a href="javascript:void(0);" class="option-tree-setting-remove option-tree-ui-button right-item" title="Delete">
          <span class="icon trash-can">Delete</span>
        </a>
      </div>
      <div class="option-tree-setting-body">
        <div class="format-settings">
          <div class="format-setting-label">
            <h5>Label</h5>
          </div>
          <div class="format-setting type-text wide-desc">
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[choices][' . $key . '][label]" value="' . ( isset( $choice['label'] ) ? esc_attr( $choice['label'] ) : '' ) . '" class="widefat option-tree-ui-input option-tree-setting-title" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting-label">
            <h5>Value</h5>
          </div>
          <div class="format-setting type-text wide-desc">
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[choices][' . $key . '][value]" value="' . ( isset( $choice['value'] ) ? esc_attr( $choice['value'] ) : '' ) . '" class="widefat option-tree-ui-input" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting-label">
            <h5>Image Source (Radio Image only)</h5>
          </div>
          <div class="format-setting type-text wide-desc">
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[choices][' . $key . '][src]" value="' . ( isset( $choice['src'] ) ? esc_attr( $choice['src'] ) : '' ) . '" class="widefat option-tree-ui-input" autocomplete="off" />
            </div>
          </div>
        </div>
    </div>';
    
  }

}

/**
 * Helper function to display sections.
 *
 * This function is used in AJAX to add a new section
 * and when section have already been added and saved.
 *
 * @param    int      $key The array key for the current element.
 * @param    array    An array of values for the current section.
 *
 * @return   void
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_contextual_help_view' ) ) {

  function ot_contextual_help_view( $name, $key, $content = array() ) {
  
    return '
    <div class="option-tree-setting">
      <div class="open">' . ( isset( $content['title'] ) ? esc_attr( $content['title'] ) : 'Content ' . ( $key + 1 ) ) . '</div>
      <div class="button-section">
        <a href="javascript:void(0);" class="option-tree-setting-edit option-tree-ui-button left-item" title="Edit">
          <span class="icon pencil">Edit</span>
        </a>
        <a href="javascript:void(0);" class="option-tree-setting-remove option-tree-ui-button right-item" title="Delete">
          <span class="icon trash-can">Delete</span>
        </a>
      </div>
      <div class="option-tree-setting-body">
        <div class="format-settings">
          <div class="format-setting type-text no-desc">
            <div class="description"><strong>Title</strong>: Displayed as a contextual help menu item on the Theme Options page.</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][title]" value="' . ( isset( $content['title'] ) ? $content['title'] : '' ) . '" class="widefat option-tree-ui-input option-tree-setting-title" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-text no-desc">
            <div class="description"><strong>ID</strong>: A unique lower case alphanumeric string, underscores allowed [a-z0-9_].</div>
            <div class="format-setting-inner">
              <input type="text" name="' . $name . '[' . $key . '][id]" value="' . ( isset( $content['id'] ) ? $content['id'] : '' ) . '" class="widefat option-tree-ui-input" autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="format-settings">
          <div class="format-setting type-textarea no-desc">
            <div class="description"><strong>Content</strong>: Enter the HTML content about this contextual help item displayed on the Theme Option page for end users to read.</div>
            <div class="format-setting-inner">
              <textarea class="textarea" rows="15" cols="40" name="' . $name . '[' . $key . '][content]">' . ( isset( $content['content'] ) ? esc_html( $content['content'] ) : '' ) . '</textarea>
            </div>
          </div>
        </div>
      </div>
    </div>';
    
  }

}

/**
 * Helper function to display sections.
 *
 * @param     string      $key
 * @param     string      $data
 * @param     string      $active_layout
 *
 * @return    void
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_layouts_view' ) ) {

  function ot_layout_view( $key, $data, $active_layout ) {
  
    return '
    <div class="option-tree-setting">
      <div class="open">' . ( isset( $key ) ? esc_attr( $key ) : __( 'Layout', 'option-tree' ) ) . '</div>
      <div class="button-section">
        <a href="javascript:void(0);" class="option-tree-layout-activate option-tree-ui-button left-item' . ( $active_layout == $key ? ' active' : '' ) . '" title="Activate">
          <span class="icon check">Activate</span>
        </a>
        <a href="javascript:void(0);" class="option-tree-setting-remove option-tree-ui-button right-item" title="Delete">
          <span class="icon trash-can">Delete</span>
        </a>
      </div>
      <input type="hidden" name="option_tree_layouts[' . $key . ']" value="' . ( isset( $data ) ? $data : '' ) . '" />
    </div>';
    
  }

}

/**
 * Helper function to display list items.
 *
 * This function is used in AJAX to add a new list items
 * and when they have already been added and saved.
 *
 * @param     string    $name The form field name.
 * @param     int       $key The array key for the current element.
 * @param     array     An array of values for the current list item.
 *
 * @return   void
 *
 * @access   public
 * @since    2.0
 */
if ( ! function_exists( 'ot_list_item_view' ) ) {

  function ot_list_item_view( $name, $key, $list_item = array() ) {
    
    /* required list item setting */
    $required_setting = array(
      array(
        'id'        => 'title',
        'label'     => __( 'Title', 'option-tree' ),
        'desc'      => '',
        'std'       => '',
        'type'      => 'text',
        'rows'      => '',
        'class'     => 'option-tree-setting-title',
        'post_type' => '',
        'choices'   => array()
      )
    );
    
    /* get saved settings */
    $option_tree_settings = get_option( 'option_tree_settings' );
    
    
    /* loop through settings and find the list items settings array */
    foreach( $option_tree_settings['settings'] as $setting ) {
    
      if ( $setting['id'] == $name && isset( $setting['settings'] ) ) {
      
        $settings = $setting['settings'];
        
      }
      
    }

    /* if no settings array load the filterable list item settings */
    if ( ! isset( $settings ) && empty( $settings ) ) {
    
      $settings = apply_filters( 'list_item_fields', array(
        array(
          'id'        => 'image',
          'label'     => __( 'Image', 'option-tree' ),
          'desc'      => '',
          'std'       => '',
          'type'      => 'upload',
          'rows'      => '',
          'class'     => '',
          'post_type' => '',
          'choices'   => array()
        ),
        array(
          'id'        => 'link',
          'label'     => __( 'Link', 'option-tree' ),
          'desc'      => '',
          'std'       => '',
          'type'      => 'text',
          'rows'      => '',
          'class'     => '',
          'post_type' => '',
          'choices'   => array()
        ),
        array(
          'id'        => 'caption',
          'label'     => __( 'Caption', 'option-tree' ),
          'desc'      => '',
          'std'       => '',
          'type'      => 'textarea-simple',
          'rows'      => 10,
          'class'     => '',
          'post_type' => '',
          'choices'   => array()
        )
      ), $name );
      
    }
    
    /* merge the two settings array */
    $settings = array_merge( $required_setting, $settings );
    
    echo '
    <div class="option-tree-setting">
      <div class="open">' . ( isset( $list_item['title'] ) ? esc_attr( $list_item['title'] ) : 'List Item ' . ( $key + 1 ) ) . '</div>
      <div class="button-section">
        <a href="javascript:void(0);" class="option-tree-setting-edit option-tree-ui-button left-item" title="' . __( 'Edit', 'option-tree' ) . '">
          <span class="icon pencil">' . __( 'Edit', 'option-tree' ) . '</span>
        </a>
        <a href="javascript:void(0);" class="option-tree-setting-remove option-tree-ui-button right-item" title="' . __( 'Delete', 'option-tree' ) . '">
          <span class="icon trash-can">' . __( 'Delete', 'option-tree' ) . '</span>
        </a>
      </div>
      <div class="option-tree-setting-body">';
        
      foreach( $settings as $field ) {
        
        /* set default to standard value */
        if ( ! isset( $list_item[$field['id']] ) && isset( $field['std'] ) ) {  
          $list_item[$field['id']] = trim( $field['std'] );
        }
              
        /* build the arguments array */
        $_args = array(
          'type'              => $field['type'],
          'field_id'          => $field['id'] . '_' . $key,
          'field_name'        => 'option_tree[' . $name . '][' . $key . '][' . $field['id'] . ']',
          'field_value'       => isset( $list_item[$field['id']] ) ? $list_item[$field['id']] : '',
          'field_desc'        => isset( $field['desc'] ) ? $field['desc'] : '',
          'field_std'         => isset( $field['std'] ) ? $field['std'] : '',
          'field_rows'        => isset( $rows ) ? $rows : 10,
          'field_class'       => isset( $field['class'] ) ? ' ' . $field['class'] : '',
          'field_choices'     => isset( $field['choices'] ) && ! empty( $field['choices'] ) ? $field['choices'] : array(),
          'field_settings'    => isset( $field['settings'] ) && ! empty( $field['settings'] ) ? $field['settings'] : array()
        );
          
        /* option label */
        echo '<div class="format-settings">';
          
          echo '<div class="format-setting-label">';
            echo '<h3 class="label">' . esc_attr( $field['label'] ) . '</h3>';
          echo '</div>';
        
        /* only allow simple textarea inside a list-item due to known DOM issues with wp_editor() */
        if ( $_args['type'] == 'textarea' )
          $_args['type'] = 'textarea-simple';
          
        /* option body, list-item is not allowed inside another list-item */
        if ( $_args['type'] !== 'list-item' ) {
          echo ot_display_by_type( $_args );
        }
        
        echo '</div>';
      
      }
        
    echo
      '</div>
    </div>';
    
  }
  
}

/**
 * Helper function to display Theme Options layouts form.
 *
 * @return    string
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_theme_options_layouts_form' ) ) {

  function ot_theme_options_layouts_form() {
    
    echo '<form method="post" id="option-tree-options-layouts-form">';
        
      /* form nonce */
      wp_nonce_field( 'option_tree_modify_layouts_form', 'option_tree_modify_layouts_nonce' );
        
      /* get the saved layouts */
      $layouts = get_option( 'option_tree_layouts' );
      
      /* set active layout */
      $active_layout = isset( $layouts['active_layout'] ) ? $layouts['active_layout'] : '';
      
      /* new layout wrapper */
      echo '<div class="option-tree-save-layout">';
        
        /* add new layout */
        echo '<input type="text" name="option_tree_layouts[_add_new_layout_]" value="" class="widefat option-tree-ui-input" autocomplete="off" />';
        
        echo '<button type="submit" class="option-tree-ui-button blue light save-layout" title="' . __( 'New Layout', 'option-tree' ) . '">' . __( 'New Layout', 'option-tree' ) . '</button>';
      
      echo '</div>';
    
      if ( is_array( $layouts ) && count( $layouts ) > 1 ) {
        
        $active_layout = $layouts['active_layout'];
        
        echo '<input type="hidden" id="the_current_layout" value="' . $active_layout . '" />';
        
        echo '<select name="option_tree_layouts[active_layout]" class="option-tree-active-layout">';
    
          foreach( $layouts as $key => $data ) { 
            
            if ( $key == 'active_layout' )
              continue;
            
            echo '<option' . selected( $key, $active_layout, false ) . ' value="' . esc_attr( $key ) . '">' . esc_attr( $key ) . '</option>';
          }
     		
        echo '</select>';
     		
        foreach( $layouts as $key => $data ) {
          
          if ( $key == 'active_layout' )
              continue;
              
          echo '<input type="hidden" name="option_tree_layouts[' . $key . ']" value="' . ( isset( $data ) ? $data : '' ) . '" />';
          
        }
   		
      }
      
    echo '</form>';
    
  }

}

/**
 * Helper function to validate option ID's
 *
 * @param     string      $input The string to sanitize.
 * @return    string
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_sanitize_option_id' ) ) {

  function ot_sanitize_option_id( $input ) {
  
    return preg_replace( '/[^a-z0-9]/', '_', trim( strtolower( $input ) ) );
      
  }

}

/**
 * Helper function to validate layout ID's
 *
 * @param     string      $input The string to sanitize.
 * @return    string
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_sanitize_layout_id' ) ) {

  function ot_sanitize_layout_id( $input ) {
  
    return preg_replace( '/[^a-z0-9]/', '-', trim( strtolower( $input ) ) );
      
  }

}

/**
 * Convert choices array to string
 *
 * @return    string
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_convert_array_to_string' ) ) {

  function ot_convert_array_to_string( $input ) {

    if ( is_array( $input ) ) {

      foreach( $input as $k => $choice ) {
        $choices[$k] = $choice['value'] . '|' . $choice['label'];
        
        if ( isset( $choice['src'] ) )
          $choices[$k].= '|' . $choice['src'];
          
      }
      
      return implode( ',', $choices );
    }
    
    return false;
  }
}

/**
 * Convert choices string to array
 *
 * @return    array
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_convert_string_to_array' ) ) {

  function ot_convert_string_to_array( $input ) {
    
    if ( '' !== $input ) {
    
      /* empty choices array */
      $choices = array();
      
      /* exlode the string into an array */
      foreach( explode( ',', $input ) as $k => $choice ) {
        
        /* if ":" is splitting the string go deeper */
        if ( preg_match( '/\|/', $choice ) ) {
          $split = explode( '|', $choice );
          $choices[$k]['value'] = trim( $split[0] );
          $choices[$k]['label'] = trim( $split[1] );
          
          /* if radio image there are three values */
          if ( isset( $split[2] ) )
            $choices[$k]['src'] = trim( $split[2] );
            
        } else {
          $choices[$k]['value'] = $choice;
          $choices[$k]['label'] = $choice;
        }
        
      }
      
      /* return a formated choices array */
      return $choices;
    
    }
    
    return false;
    
  }
}

/**
 * Helper function - strpos() with arrays.
 *
 * @param     string    $haystack
 * @param     array     $needles
 * @return    bool
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_strpos_array' ) ) {

  function ot_strpos_array( $haystack, $needles = array() ) {
  
    foreach( $needles as $needle ) {
      $pos = strpos( $haystack, $needle );
      if ( $pos !== false ) {
        return true;
      }
    }
    
    return false;
  }

}

/**
 * Helper function - strpos() with arrays.
 *
 * @param     string    $haystack
 * @param     array     $needles
 * @return    bool
 *
 * @access    public
 * @since     2.0
 */
if ( ! function_exists( 'ot_array_keys_exists' ) ) {
  
  function ot_array_keys_exists( $array, $keys ) {
    
    foreach($keys as $k) {
      if ( isset($array[$k]) ) {
        return true;
      }
    }
    
    return false;
  }
  
}

/* End of file ot-functions-admin.php */
/* Location: ./includes/ot-functions-admin.php */