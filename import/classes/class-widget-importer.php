<?php
class Widget_Importer {
    /**
     * Main import method
     */
    public static function import($data) {
        // Verify menus exist before widget import
        self::verify_menu_assignments($data);
        return self::import_data($data);
    }

    /**
     * Verify menu assignments in widget data
     */
    private static function verify_menu_assignments($data) {
        $menu_widgets = [];
        
        // Collect all nav_menu widget references
       foreach ($data as $sidebar => $widgets) {
		foreach ($widgets as $widget_id => $widget) {
			// Check if widget ID starts with 'nav_menu-' OR
			// if $widget is an object and has property 'nav_menu'
			if (strpos($widget_id, 'nav_menu-') === 0 ||
				(is_object($widget) && isset($widget->nav_menu))
			) {
				// Get the menu ID depending on whether $widget is object or array
				$menu_id = is_object($widget) ? $widget->nav_menu : (isset($widget['nav_menu']) ? $widget['nav_menu'] : null);

				if ($menu_id !== null) {
					$menu_widgets[$menu_id][] = $sidebar;
				}
			}
		}
	}
        // Check each referenced menu exists
       foreach ($menu_widgets as $menu_id => $sidebars) {
		$menu = wp_get_nav_menu_object($menu_id);
		if (!$menu) {
			error_log(sprintf(
				'Warning: Menu ID %d referenced in sidebars [%s] does not exist',
				$menu_id,
				implode(', ', $sidebars)
			));
		}
	}
		// Note: This is a simple check, you might want to handle this more gracefully in production code
		if (empty($menu_widgets)) {
			error_log('No nav_menu widgets found in import data.');
		}
    }

    /**
     * Process widget import data
     */
    private static function import_data($data) {
        global $wp_registered_sidebars;

        if (empty($data) || !is_object($data)) {
            return new WP_Error(
                'corrupted_widget_import_data',
                __('Error: Widget import data could not be read.', 'yomooh')
            );
        }

        do_action('som_widget_importer_before_widgets_import');
        $data = apply_filters('som_before_widgets_import_data', $data);

        $available_widgets = self::available_widgets();
        $widget_instances = [];
        $results = [];

        // Prepare existing widget instances
        foreach ($available_widgets as $widget_data) {
            $widget_instances[$widget_data['id_base']] = get_option('widget_' . $widget_data['id_base']);
        }

        // Process each sidebar
        foreach ($data as $sidebar_id => $widgets) {
            if ('wp_inactive_widgets' === $sidebar_id) {
                continue;
            }

            $sidebar_available = isset($wp_registered_sidebars[$sidebar_id]);
            $use_sidebar_id = $sidebar_available ? $sidebar_id : 'wp_inactive_widgets';
            
            $results[$sidebar_id] = [
                'name' => $wp_registered_sidebars[$sidebar_id]['name'] ?? $sidebar_id,
                'message_type' => $sidebar_available ? 'success' : 'error',
                'message' => $sidebar_available ? '' : __('Sidebar does not exist in theme', 'yomooh'),
                'widgets' => []
            ];

            // Process each widget
            foreach ($widgets as $widget_instance_id => $widget) {
                $fail = false;
                $widget_message_type = 'success';
                $widget_message = __('Imported', 'yomooh');

                $id_base = preg_replace('/-[0-9]+$/', '', $widget_instance_id);
                $instance_id_number = str_replace($id_base . '-', '', $widget_instance_id);

                // Check widget support
                if (!isset($available_widgets[$id_base])) {
                    $fail = true;
                    $widget_message_type = 'error';
                    $widget_message = __('Site does not support widget', 'yomooh');
                }

                // Handle nav_menu widget specifically
                if (!$fail && $id_base === 'nav_menu') {
                    $widget = self::process_nav_menu_widget($widget, $widget_instance_id);
                    if (is_wp_error($widget)) {
                        $fail = true;
                        $widget_message_type = 'error';
                        $widget_message = $widget->get_error_message();
                    }
                }

                $widget = apply_filters('som_widget_settings', $widget);
                $widget = json_decode(json_encode($widget), true);
                $widget = apply_filters('som_widget_settings_array', $widget);

                // Check for duplicates
                if (!$fail && isset($widget_instances[$id_base])) {
                    $sidebars_widgets = get_option('sidebars_widgets');
                    $sidebar_widgets = $sidebars_widgets[$use_sidebar_id] ?? [];
                    
                    foreach ($widget_instances[$id_base] as $check_id => $check_widget) {
                        if (in_array("$id_base-$check_id", $sidebar_widgets) && (array) $widget == $check_widget) {
                            $fail = true;
                            $widget_message_type = 'warning';
                            $widget_message = __('Widget already exists', 'yomooh');
                            break;
                        }
                    }
                }

                if (!$fail) {
                    $new_instance_id_number = self::save_widget_instance($id_base, $widget);
                    self::assign_widget_to_sidebar($use_sidebar_id, $id_base, $new_instance_id_number);

                    do_action('som_widget_importer_after_single_widget_import', [
                        'sidebar' => $use_sidebar_id,
                        'widget' => $widget,
                        'widget_type' => $id_base,
                        'widget_id' => "$id_base-$new_instance_id_number"
                    ]);

                    if (!$sidebar_available) {
                        $widget_message_type = 'warning';
                        $widget_message = __('Imported to Inactive', 'yomooh');
                    }
                }

                $results[$sidebar_id]['widgets'][$widget_instance_id] = [
                    'name' => $available_widgets[$id_base]['name'] ?? $id_base,
                    'title' => $widget['title'] ?? __('No Title', 'yomooh'),
                    'message_type' => $widget_message_type,
                    'message' => $widget_message
                ];
            }
        }

        do_action('som_widget_importer_after_widgets_import');
        return apply_filters('som_widget_import_results', $results);
    }

    /**
     * Special handling for nav_menu widgets
     */
    private static function process_nav_menu_widget($widget, $widget_id) {
        $menu_id = is_object($widget) ? $widget->nav_menu : $widget['nav_menu'];
        
        // If menu doesn't exist, try to find a suitable replacement
        if (!wp_get_nav_menu_object($menu_id)) {
            $menus = wp_get_nav_menus();
            if (!empty($menus)) {
                $new_menu_id = $menus[0]->term_id;
                error_log(sprintf(
                    'Menu ID %d not found in widget %s. Using menu ID %d (%s) instead.',
                    $menu_id,
                    $widget_id,
                    $new_menu_id,
                    $menus[0]->name
                ));
                
                if (is_object($widget)) {
                    $widget->nav_menu = $new_menu_id;
                } else {
                    $widget['nav_menu'] = $new_menu_id;
                }
            } else {
                return new WP_Error(
                    'missing_menu',
                    sprintf(__('Referenced menu ID %d does not exist', 'yomooh'), $menu_id)
                );
            }
        }
        
        return $widget;
    }

    /**
     * Save widget instance and return new ID
     */
    private static function save_widget_instance($id_base, $widget) {
        $single_widget_instances = get_option('widget_' . $id_base) ?: ['_multiwidget' => 1];
        $single_widget_instances[] = $widget;

        end($single_widget_instances);
        $new_instance_id_number = key($single_widget_instances);

        // Fix 0 key issue
        if ('0' === strval($new_instance_id_number)) {
            $new_instance_id_number = 1;
            $single_widget_instances[$new_instance_id_number] = $single_widget_instances[0];
            unset($single_widget_instances[0]);
        }

        // Move _multiwidget to end
        if (isset($single_widget_instances['_multiwidget'])) {
            $multiwidget = $single_widget_instances['_multiwidget'];
            unset($single_widget_instances['_multiwidget']);
            $single_widget_instances['_multiwidget'] = $multiwidget;
        }

        update_option('widget_' . $id_base, $single_widget_instances);
        return $new_instance_id_number;
    }

    /**
     * Assign widget to sidebar
     */
    private static function assign_widget_to_sidebar($sidebar_id, $id_base, $instance_id) {
        $sidebars_widgets = get_option('sidebars_widgets') ?: [];
        $sidebars_widgets[$sidebar_id][] = "$id_base-$instance_id";
        update_option('sidebars_widgets', $sidebars_widgets);
    }

    /**
     * Get available widgets
     */
    private static function available_widgets() {
        global $wp_registered_widget_controls;

        $available_widgets = [];
        foreach ($wp_registered_widget_controls as $widget) {
            if (!empty($widget['id_base']) && !isset($available_widgets[$widget['id_base']])) {
                $available_widgets[$widget['id_base']] = [
                    'id_base' => $widget['id_base'],
                    'name' => $widget['name']
                ];
            }
        }

        return apply_filters('som_available_widgets', $available_widgets);
    }

    /**
     * Format results for logging
     */
    private static function format_results_for_log($results) {
        if (empty($results)) {
            esc_html_e('No results for widget import!', 'yomooh');
        }

        foreach ($results as $sidebar) {
            echo esc_html($sidebar['name']) . ' : ' . esc_html($sidebar['message']) . PHP_EOL . PHP_EOL;
            foreach ($sidebar['widgets'] as $widget) {
                echo esc_html($widget['name']) . ' - ' . esc_html($widget['title']) . ' - ' . esc_html($widget['message']) . PHP_EOL;
            }
            echo PHP_EOL;
        }
    }
}