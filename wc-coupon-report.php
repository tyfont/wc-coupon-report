<?php
/*
Plugin Name: WooCommerce Coupon Report
Description: Displays a report of WooCommerce coupon usage with discount amounts and usage counts.
Version: 1.0
Author: Tyfont
Website: https://github.com/tyfont/wc-coupon-report
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Hook to add admin menu page
add_action( 'admin_menu', 'wc_coupon_report_admin_menu' );

// Add menu item under WooCommerce
function wc_coupon_report_admin_menu() {
    add_submenu_page(
        'woocommerce', // Parent slug
        'Coupon Report', // Page title
        'Coupon Report', // Menu title
        'manage_woocommerce', // Capability
        'wc-coupon-report', // Menu slug
        'wc_coupon_report_page_content' // Callback function
    );
}

// Function to fetch and display coupon report
function wc_coupon_report_page_content() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    
    echo '<div class="wrap"><h1>WooCommerce Coupon Report</h1>';
    
    // Fetch coupon data
    $coupon_data = wc_get_coupon_usage_data();
    
    // Display data in a table
    if ( ! empty( $coupon_data ) ) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Coupon Code</th><th>Total Discount</th><th>Usage Count</th></tr></thead><tbody>';
        
        foreach ( $coupon_data as $data ) {
            echo '<tr>';
            echo '<td>' . esc_html( $data['code'] ) . '</td>';
            echo '<td>' . wc_price( $data['discount_amount'] ) . '</td>';
            echo '<td>' . esc_html( $data['usage_count'] ) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No coupon data available.</p>';
    }
    
    echo '</div>';
}

// Function to retrieve coupon data
function wc_get_coupon_usage_data( $start_date = '', $end_date = '' ) {
    $coupon_data = [];
    
    // Query WooCommerce orders
    $args = [
        'status' => 'completed',
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    
    if ( ! empty( $start_date ) ) {
        $args['date_after'] = $start_date;
    }
    if ( ! empty( $end_date ) ) {
        $args['date_before'] = $end_date;
    }
    
    $orders = wc_get_orders( $args );
    
    foreach ( $orders as $order ) {
        $coupons = $order->get_coupon_codes();
        
        foreach ( $coupons as $coupon_code ) {
            $discount_amount = 0;
            
            foreach ( $order->get_items( 'coupon' ) as $item ) {
                if ( $item->get_code() === $coupon_code ) {
                    $discount_amount += $item->get_discount();
                }
            }
            
            if ( ! isset( $coupon_data[ $coupon_code ] ) ) {
                $coupon_data[ $coupon_code ] = [
                    'code' => $coupon_code,
                    'discount_amount' => 0,
                    'usage_count' => 0,
                ];
            }
            
            $coupon_data[ $coupon_code ]['discount_amount'] += $discount_amount;
            $coupon_data[ $coupon_code ]['usage_count']++;
        }
    }
    
    return $coupon_data;
}
