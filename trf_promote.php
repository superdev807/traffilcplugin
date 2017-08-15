<?php
global $wpdb;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Trf_Promote extends WP_List_Table {
    function __construct() {
        parent::__construct( array(
            'singular'=> 'promote',
            'plural' => 'promotes',
            'ajax'  => false
        ) );

        add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

    function extra_tablenav($which) {
        if ($which == "top") { }
        if ($which == "bottom") { }
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_action($item) {
        $id = $item['id'];
        return "<input type='submit' value='Save' class='button button-primary trf-save' data-id='{$id}' data-nonce='" . wp_create_nonce('trf_'.$id) . "' />";
    }

    function column_date($item) {
        $date = $item['date'];
        return date('Y-m-d', strtotime($date));
    }

    function column_title($item) {
        $id = $item['id'];
        $title = $item['title'];
        return "<a href='" . get_edit_post_link($id) . "'>{$title}</a>";
    }

    function column_traffic_on($item) {
        $id = $item['id'];
        $traffic_on = $item['traffic_on'];
        return "<input class='widefat trf-get-traffic' type='checkbox' name='trf-get-traffic' value='1' " . ($traffic_on ? 'checked' : '') . " />";
    }

    function column_keyword1($item) {
        $keyword1 = $item['keyword1'];
        return "<input type='text' value='{$keyword1}' name='keyword1' />";
    }
    function column_keyword2($item) {
        $keyword2 = $item['keyword2'];
        return "<input type='text' value='{$keyword2}' name='keyword2' />";
    }
    function column_keyword3($item) {
        $keyword3 = $item['keyword3'];
        return "<input type='text' value='{$keyword3}' name='keyword3' />";
    }

    function get_columns() {
        return $columns= array(
            'title'         => 'Title',
            'date'          => 'Date',
            'type'          => 'Type',
            'keyword1'      => 'Keyword1',
            'keyword2'      => 'Keyword2',
            'keyword3'      => 'Keyword3',
            'action'        => 'Action',
            'traffic_on'    => 'Get Traffic'
        );
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'         => array('title', false),
            'date'          => array('date', false),
            'type'          => array('type', false),
            'keyword1'      => array('keyword1', false),
            'keyword2'      => array('keyword2', false),
            'keyword3'      => array('keyword3', false),
            'traffic_on'    => array('traffic_on', false)
        );
        return $sortable_columns;
    }

    function prepare_items() {
        $per_page = 50;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        $data = [];
        if (empty($_POST['checker']) || isset($_POST['show_pages'])) {
            foreach(get_pages() as $p) {
                array_push($data, array(
                    "id" => $p->ID,
                    "title" => $p->post_title,
                    "date" => $p->post_date,
                    "type" => "Page",
                    "keyword1" => get_post_meta($p->ID, 'trf_keyword1', true),
                    "keyword2" => get_post_meta($p->ID, 'trf_keyword2', true),
                    "keyword3" => get_post_meta($p->ID, 'trf_keyword3', true),
                    "traffic_on" => get_post_meta($p->ID, 'trf_get_traffic', true) == 1
                ));
            }
        }
        if (empty($_POST['checker']) || isset($_POST['show_posts'])) {
            foreach(get_posts() as $p) {
                array_push($data, array(
                    "id" => $p->ID,
                    "title" => $p->post_title,
                    "date" => $p->post_date,
                    "type" => "Post",
                    "keyword1" => get_post_meta($p->ID, 'trf_keyword1', true),
                    "keyword2" => get_post_meta($p->ID, 'trf_keyword2', true),
                    "keyword3" => get_post_meta($p->ID, 'trf_keyword3', true),
                    "traffic_on" => get_post_meta($p->ID, 'trf_get_traffic', true) == 1
                ));
            }
        }

        function usort_reorder($a, $b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id';
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

function trf_promote() {
    echo '<div class = "wrap">
        <div class = "fbvahead">' . TRAFFIC_PLUGIN_LOGO . ' </div>
        <h1>Promote</h1>
        <hr />';

    if (isset($_POST['checker'])) {
        update_post_meta(111111113, 'trf_show_posts', isset($_POST['show_posts']));
    }
    if (isset($_POST['checker'])) {
        update_post_meta(111111113, 'trf_show_pages', isset($_POST['show_pages']));
    }

    $show_posts = get_post_meta(111111113, 'trf_show_posts', true) ? 'checked="checked"' : '';
    $show_pages = get_post_meta(111111113, 'trf_show_pages', true) ? 'checked="checked"' : '';

    echo '<form id="filterdata" method="post" action=""><input type="hidden" name="checker" value="1" />';
    echo 'Show &nbsp;&nbsp;<label><input type="checkbox" value="1" name="show_posts" '.$show_posts.' onChange="submit()" /> Posts</label>
        &nbsp;&nbsp;<label><input type="checkbox" value="1" name="show_pages" '.$show_pages.' onChange="submit()" /> Pages</label>';
    echo '</form>';

    echo '<form id="pagedata" method="post" action="">';
    $wp_list_table = new Trf_Promote();
    $wp_list_table->prepare_items();
    $wp_list_table->display();

    echo '</form>';

    $save_url = plugins_url( 'promote-save.php', __FILE__ );
    echo '
    <script type="text/javascript">
    jQuery(document).ready(function () {
        $ = jQuery;
        jQuery(".trf-get-traffic").toggleSwitch({ height: "28px" });
        jQuery(".trf-save").click(function(evt) {
            var self = $(this);
            var parent = $(self.closest("tr"));
            self.attr("disabled", "disabled");

            request = $.ajax({
                type: "post",
                url: "' . $save_url . '",
                data: {
                    id: self.data("id"),
                    trf_traffic_nonce: self.data("nonce"),
                    keyword1: parent.find("[name=keyword1]").val(),
                    keyword2: parent.find("[name=keyword2]").val(),
                    keyword3: parent.find("[name=keyword3]").val(),
                    keyword: "true"
                }
            })
            .done(function(msg) {
                toastr.success("Saved.")
            }).always(function() {
                self.removeAttr("disabled");
            });
            evt.preventDefault();
            return false;
        });

        jQuery(".trf-get-traffic").change(function(evt) {
            var self = $(evt.currentTarget);
            var parent = $(self.closest("tr"));
            parent.addClass("pending");
            var btn = parent.find(".trf-save");

            request = $.ajax({
                type: "post",
                url: "' . $save_url . '",
                data: {
                    id: btn.data("id"),
                    trf_traffic_nonce: btn.data("nonce"),
                    get_traffic: parent.find("[name=trf-get-traffic]").is(":checked") ? "1" : ""
                }
            })
            .done(function(msg) {
                if(msg) toastr.success(msg);
            }).always(function() {
                parent.removeClass("pending");
            });

            evt.preventDefault();
            return false;
        });
    });
    </script>';
}
