<?php
/**
 * @WARNING!
 *
 * This is just an example of how we can initialize and use WWOPTIONS library.
 *
 * Here we are initializing single WP options page with 3 tabs, sections and custom controls
 */

/**
 * Add options page
 */
function initialize(){
    $is_license = ! empty( get_option( WUP_Credentials::KEY_OPTION_NAME ) );
    $after =
        '<a href="https://wetail.io/support/" class="page-title-action" target="_blank">' .
        __( 'Support', SLUG ) .
        '</a>' .
        '<a href="https://docs.wetail.io/woocommerce-pacsoft-unifaun-integration/" ' .
        'class="page-title-action" target="_blank">' . __( 'FAQ', SLUG ) . '</a> ' .
        ( $is_license
            ? ''
            : '<a href="https://wetail.se/service/intergrationer/woocommerce-unifaun/" ' .
            'class="button-primary page-title-action" target="_blank">' .
            __( 'Order License', SLUG ) .
            '</a>'
        );

    wwp_options( apply_filters( 'woocommerce_unifaun_nshift_settings', [

        'woocommerce-pacsoft' => [
            'type'          => 'page',
            'title'         => __( 'WooCommerce Pacsoft/Unifaun integration', SLUG ),
            'after'         => $after,
            'menu'          => __( "Pacsoft/Unifaun", SLUG ),
            'save_button'   => __( 'Save changes', SLUG ),
            'save_notice'   => __( 'Your settings updated successfully!', SLUG ),
            'scripts'       => [
                'pacsoft-js-global' => [
                    'type'      => 'file',
                    'source'    => URL . '/assets/scripts/global.js',
                    'deps'      => [ 'jquery' ],
                    'global'    => true,
                    'localize'  => [
                        'pacsoft' => [
                            'choosen_base_country'              => get_option( 'pacsoft_base_country' ),
                            'pacsoft_account_type'              => get_option( 'pacsoft_account_type' ),
                            'pacsoft_username'                  => get_option( 'pacsoft_usern_unif' ),
                            'pacsoft_password'                  => get_option( 'pacsoft_pass_unif' ),
                            'pacsoft_api_id'                    => get_option( 'pacsoft_api_id' ),
                            'pacsoft_api_secret_id'             => get_option( 'pacsoft_api_secret_id' ),
                            'pacsoft_send_customs_declaration'  => get_option( 'pacsoft_send_customs_declaration' ),
                            'pacsoft_services'                  => get_option( 'pacsoft_services' )
                        ]
                    ]
                ],
                'pacsoft-js-chosen' => [
                    'type'      => 'file',
                    'source'    => URL . '/assets/scripts/chosen.min.js',
                    'deps'      => [ 'jquery' ],
                ],
                'pacsoft-js-settings' => [
                    'type'      => 'file',
                    'source'    => URL . '/assets/scripts/settings.js',
                    'deps'      => [ 'jquery', 'pacsoft-js-chosen' ],
                ],
            ],
            'styles'        => [
                'pacsoft-css-global' => [
                    'type'      => 'file',
                    'global'    => true,
                    'source'    => URL . '/assets/styles/global.css'
                ],
                'pacsoft-css-chosen'   => [
                    'type'  => 'file',
                    'source'=> URL . '/assets/styles/chosen.min.css'
                ],
                'pacsoft-css-settings' => [
                    'type'  => 'file',
                    'source'=> URL . '/assets/styles/settings.css'
                ]
            ]
        ],

        'general-settings' => [
            'type'  => 'tab',
            'title' => __( 'General', SLUG )
        ],
        'account-section' => [
            'type'  => 'section',
            'title' => __( 'Account', SLUG )
        ],
        'pacsoft_account_type' => [
            'title' => __( 'Account type', SLUG ),
            'type' => 'select',
            'options' => [
                '' => __( 'Please select...', SLUG ),
                WUP_Plugin::PACSOFT_XML     => 'Pacsoft OnlineConnect',
                WUP_Plugin::UNIFAUN_XML     => 'Unifaun OnlineConnect',
                WUP_Plugin::UNIFAUN_REST    => 'Unifaun APIConnect',
                WUP_Plugin::PACSOFT_REST    => 'Pacsoft APIConnect'
            ]
        ],
        'credentials-section' => [
            'type'      => 'section',
            'title'     => __( 'Credentials OnlineConnect', SLUG )
        ],
        'pacsoft_usern_unif' => [
            'title'     => __( 'User', SLUG )
        ],
        'pacsoft_pass_unif' => [
            'title'     => __( 'Password', SLUG )
        ],
        'api-license-key-section' => [
            'title'         => __( 'Credentials APIConnect', SLUG ),
            'type'          => 'section',
            'description'   => __(
                'If you want to use Unifaun/Pacsoft features like Track and trace and printing from WooCommerce ' .
                'order listing please add Credentials to your Unifaun/Pacsoft account under ' .
                '<b>Credentials Unifaun/Pacsoft</b>',
                SLUG
            ),
        ],
        'pacsoft_api_id' => [
            'title' => __( 'API ID', SLUG )
        ],
        'pacsoft_api_secret_id' => [
            'title' => __( 'API Secret ID', SLUG )
        ],
        'pacsoft-general-section' => [
            'type'  => 'section',
            'title' => __( 'General', SLUG )
        ],
        'pacsoft_license_key' => [
            'title' => __( 'API license key', SLUG ),
            'after' =>
                '<a href="https://wetail.se/service/intergrationer/woocommerce-pacsoft/" '.
                'class="button pacsoft-buy-license" target="_blank" style="display:none">'
                . __( "Buy", SLUG ) . '</a>'
        ],


        'order-settings' => [
            'type'  => 'tab',
            'title' => __( "Order", SLUG )
        ],
        'order-mapping' => [
            'title' => __( 'Order Mapping', SLUG ),
            'type'  => 'section'
        ],
        'pacsoft_services' => [
            'title'         => __( 'Map services', SLUG ),
            'type'          => 'custom',
            'description'   => __(
                'NOTE: Remember to set your customer number for each service added in the list above in '.
                'Pacsoft/Unifaun Admin &#8594; Maintenance &#8594; Senders &#8594; Search ' .
                '(sender quick value) &#8594; Edit<br>',
                SLUG
            ),
            'value'         => __CLASS__ . '::get_orders_services_html'
        ],
        'synchronization-settings-section' => [
            'type'  => 'section',
            'title' => __( 'General', SLUG )
        ],
        'pacsoft_base_country' => [
            'title'     => __( 'Base country', SLUG ),
            'type'      => 'select',
            'options'   => [
                'SE' => __( 'Sweden, SE', SLUG ),
                'NO' => __( 'Norway, NO', SLUG ),
                'DK' => __( 'Denmark, DK', SLUG )
            ]
        ],
        'pacsoft_favorite' => [
            'title' => __( 'Favorite', SLUG )
        ],
        'pacsoft_order_number_prefix' => [
            'title' => __( 'Prefix added to order number', SLUG )
        ],
        'pacsoft_default_product_type' => [
            'title'     => __( 'Default product type', SLUG ),
            'default'   => 'Varor'
        ],
        'pacsoft_default_minimum_order_weight' => [
            'title' => __( 'Default minimum Order weight (grams)', SLUG )
        ],
        'pacsoft_default_sender_quick_id' => [
            'title'     => __( 'Default sender quick id', SLUG ),
            'default'   => '1'
        ],
        'pacsoft_on_order_status' => [
            'title' => __( 'Send on order status', SLUG ),
            'type' => 'select',
            'options' => [
                ''              => __( 'Please select...', SLUG ),
                'processing'    => __( 'Processing', SLUG ),
                'completed'     => __( 'Completed', SLUG )
            ]
        ],
        'pacsoft-order-more-options' => [
            'title' => __( 'More options', SLUG ),
            'type'  => 'group',
            'options' => [
                'pacsoft_sync_with_options' => [
                    'label' => __( 'Show options when syncing (disables auto-sync)', SLUG ),
                    'type'  => 'checkbox'
                ],
                'pacsoft_addon_sms' => [
                    'label' => __( 'Send SMS notification (Addon)', SLUG ),
                    'type'  => 'checkbox'
                ],
                'pacsoft_addon_notemail' => [
                    'type'  => 'checkbox',
                    'label' => __( 'Send email notification (Addon)', SLUG )
                ],
                'pacsoft_addon_enot' => [
                    'type'  => 'checkbox',
                    'label' => __( 'Send pre-notification by e-mail (Addon)', SLUG )
                ],
                'pacsoft_print_freight_label_per_item' => [
                    'type'  => 'checkbox',
                    'label' =>  __( 'Print freight label per item in a box', SLUG )
                ],
                'pacsoft_single_package_per_order' => [
                    'type'  => 'checkbox',
                    'label' =>  __( 'Send single package per order', SLUG )
                ],
                'pacsoft_print_return_labels' =>[
                    'type'  => 'checkbox',
                    'label' =>  __( 'Add return labels to orders', SLUG )
                ],
                'pacsoft_logfile_activated' =>[
                    'type'  => 'checkbox',
                    'label' =>  __( 'Activate log file', SLUG )
                ],
                'pacsoft_prerender_stored_shipment_pdf' =>[
                    'type'  => 'checkbox',
                    'label' =>  __( 'Prerender PDF from stored shipment ', SLUG )
                ]
            ]
        ],
        'pacsoft_track_link_on_order_status' => [
            'title' => __( 'Send track link to customer on order status', SLUG ),
            'type' => 'select',
            'options' => [
                ''              => __( 'Please select...', SLUG ),
                'processing'    => __( 'Processing', SLUG ),
                'completed'     => __( 'Completed', SLUG )
            ]
        ],
        'pacsoft_printer_type' => [
            'title' => __( "Skrivartyp", SLUG ),
            'type' => "select",
            'options' => [
                ''              => __( "Please select...", SLUG ),
                'thermo-se'     => __( "Etikett", SLUG ),
                'laser-ste'     => __( "A4", SLUG ),
                'laser-a5'      => __( "Single A5 label.", SLUG ),
                'laser-2a5'     => __( "Two A5 labels on a  A4-sheet", SLUG ),
                'laser-a4'      => __( "Normal A4", SLUG ),
                'thermo-brev3'  => __( "107 x 72 mm thermo label", SLUG ),
                'thermo-165'    => __( "107 x 165 mm thermo label", SLUG ),
                'thermo-190'    => __( "107 x 190 mm thermo label", SLUG ),
                'thermo-251'    => __( "107 x 251 mm thermo label", SLUG )
            ]
        ],


        'customs-settings' => [
            'type'  => 'tab',
            'title' => __( "Customs", SLUG )
        ],
        'pacsoft-general-customs-declaration-section' => [
            'title' => __( 'Customs Declaration', SLUG ),
            'type'  => 'section'
        ],
        'pacsoft_send_customs_declaration' => [
            'type'  => 'checkbox',
            'title' => __( 'Send customs declaration', SLUG ),
        ],
        'pacsoft_customs_declaration_document_number' => [
            'title'     => __( 'Document number', SLUG ),
            'default'   => 'CN22',
        ],
        'pacsoft_customs_declaration_description' => [
            'title'     => __( 'Default customs declaration description', SLUG ),
            'default'   => 'Varor'
        ],
        'pacsoft_customs_declaration_cart_threshold' => [
            'title'     => __( 'Create customs declaration if cart value is over given value', SLUG ),
            'default'   => '2000'
        ],
        'pacsoft_customs_other_unit' => [
            'title' => __( 'Customs weight unit', SLUG ),
            'type' => 'select',
            'options' => [
                ''      => __( 'Please select...', SLUG ),
                'KGS'   => 'KG (UPS)',
                'KG'    => 'KG'
            ]
        ],
        'pacsoft_customs_import_export_type' => [
            'title' => __( 'Import/Export Type', SLUG ),
            'type' => 'select',
            'options' => [
                ''          => __( 'Please select...', SLUG ),
                'SAMPLE'    => 'Commercial sample',
                'DOCUMENTS' => 'KG',
                'GIFT'      => 'Gift',
                'OTHER'     => 'Other',
                'INTERNAL'  => 'Internal invoicing',
                'PERMANENT' => 'Permanent',
                'RETURN'    => 'Returned Goods',
                'TEMPORARY' => 'Temporary'
            ]
        ],

    ] ) );
}
