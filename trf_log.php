<?php
global $wpdb;

if (!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Trf_Log extends WP_List_Table {
    function __construct() {
        parent::__construct( array(
            'singular'=> 'log',
            'plural' => 'logs',
            'ajax'  => false
        ) );

        add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

    function extra_tablenav($which) {
        if ($which == "top") { }
        if ($which == "bottom") { }
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="record[]" value="%s" />', "{$item['id']}:{$item['action']}");
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_link($item) {
        $link = $item['link'];
        return "<a href='$link' target='_blank'>{$link}</a>";
    }

    function get_columns() {
        return $columns= array(
            'cb' => '<input type="checkbox" />',
            'action'        => 'Action',
            'title'         => 'title',
            'link'          => 'Link',
            'time'          => 'Time',
            'error'         => 'Error',
        );
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'action'        => array('action', false),
            'title'         => array('title', false),
            'link'          => array('link', false),
            'time'          => array('time', false),
            'error'         => array('error', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array (
            'delete'     => 'Delete'
        );
        return $actions;
    }

    public function process_bulk_action() {
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );
        }

        global $wpdb;
        $wpdb->show_errors();

        $action = $this->current_action();

        switch ( $action ) {
            case 'delete':
                $ids = $_POST['record'];
                foreach($ids as $idstr) {
                    list($id, $table) = explode(':', $idstr);

                    $table_name = $wpdb->prefix . (($table == 'facebook_comment')?'trfcommenthistory':'trfhistory');
                    $wpdb->delete( $table_name, array( 'id' => $id ) );
                }

                break;
            default:
                return;
                break;
        }
        return;
    }

    function prepare_items() {
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        global $wpdb;
        $wpdb->show_errors();
        $table_name = $wpdb->prefix ."trfhistory";

        $data = [];
        $results = $wpdb->get_results("select * from $table_name");
        foreach($results as $r) {
            $newrecord = array(
                'id' => $r->id,
                'post_id' => $r->post_id,
                'title' => $r->title,
                "action" => $r->source,
                "time" => date('Y-m-d H:i', $r->created),
            );

            $jsonres = json_decode($r->raw_result, true);
            if ($r->source == 'facebook' || $r->source == 'facebook_comment_error') {
                if ($jsonres['id']) {
                    $newrecord['error'] = '';
                    if ($jsonres['fb_status_id']) {
                        $newrecord['link'] = 'https://www.facebook.com/' . $jsonres['fb_status_id'];
                    }
                    else {
                        $newrecord['link'] = 'https://www.facebook.com/' . $jsonres['id'];
                    }
                } else {
                    $newrecord['error'] = $jsonres['error']['message'];
                    $newrecord['link'] = '';
                }
            } else if ($r->source == 'twitter') {
                if ($jsonres['id_str']) {
                    $newrecord['error'] = '';
                    $newrecord['link'] = "https://www.twitter.com/" . $jsonres["user"]["id_str"] . "/status/" . $jsonres["id_str"];
                } else {
                    $newrecord['error'] = implode(', ', array_map('map_message', $jsonres['errors']));
                    $newrecord['link'] = '';
                }
            } else if ($r->source == 'reddit') {
                if (count($jsonres['result']['json']['errors']) == 0) {
                    $newrecord['error'] = '';
                    $newrecord['link'] = $jsonres['result']['json']['data']['url'];
                } else {
                    if (is_array($jsonres['result']['json']['errors'][1])) {
                        $newrecord['error'] = $jsonres['result']['json']['errors'][1][1];
                    } else {
                        $newrecord['error'] = $jsonres['result']['json']['errors'][1];
                    }

                    $newrecord['link'] = '';
                }
            }
            array_push($data, $newrecord);
        }

        $table_name = $wpdb->prefix ."trfcommenthistory";
        $results = $wpdb->get_results("select * from $table_name");
        foreach($results as $r) {
            array_push($data, array(
                'id' => $r->id,
                'post_id' => $r->post_id,
                "action" => 'facebook_comment',
                "time" => date('Y-m-d H:i', $r->timesent),
                'link' => 'https://www.facebook.com/' . $r->fb_post,
                'error' => ''
            ));
        }

        function usort_reorder($a, $b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'time';
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
            $result = strcmp($a[$orderby], $b[$orderby]);
            return ($order==='asc') ? $result : -$result;
        }
        usort($data, 'usort_reorder');

        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;

        $this->set_pagination_args(array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ));
    }
}

function trf_log() {
    echo '<div class = "wrap">
        <div class = "fbvahead">' . TRAFFIC_PLUGIN_LOGO . ' </div>
        <h1>Logs</h1>
        <hr />';

    echo '<form id="pagedata" method="post" action="">';
    $wp_list_table = new Trf_Log();
    $wp_list_table->prepare_items();
    $wp_list_table->display();
    echo '</form>';
}

function map_message($err) {
    return $err['message'];
}
