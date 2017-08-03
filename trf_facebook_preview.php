<?php
    global $post;

    $postId = $post->ID;

    // $pagename = get_post_meta('111111119', "stppagename", true);
    $postTitle = get_post_meta($postId, "trf_facebook_title", true);
    $postMessage = get_post_meta($postId, "trf_facebook_message", true);

    if ( empty($postTitle) ) $postTitle = $post->post_title;
    if ( empty($postMessage) ) $postMessage = $post->post_title;

    $postImage = get_post_meta($postId, "trf_facebook_image", true);
    $postDescription =  get_post_meta($postId, "trf_facebook_description", true);

    if ( empty($postDescription) ) {
    }

    if ( empty($postImage) ) {
        $feat_image = wp_get_attachment_url( get_post_thumbnail_id($postId) );
        if ( empty($feat_image) ) {
            $match = array();
            preg_match( "/<img.+src=[\'\"](?P<src>.+?)[\'\"].*>/i", $post->post_content, $match );

            if ( sizeof($match) > 0 ) {
                $postImage = $match["src"];
            }
        }
        else {
            $postImage = $feat_image;
        }
    }

    update_post_meta($postId, 'trf_facebook_title', $postTitle );
    update_post_meta($postId, 'trf_facebook_image', $postImage );
    update_post_meta($postId, 'trf_facebook_description', $postDescription );
    update_post_meta($postId, 'trf_facebook_message', $postMessage );

    // $logo = plugin_dir_url ( __FILE__ ) . '200px.png';
?>
<div class="facebook-previewer">
    <style>
        .facebook-previewer label {
            display: block;
        }
        .facebook-previewer input.form-control {
            width: 100%;
        }
        .hrfp {
            border: 1px solid;
            border-color: #e5e6e9 #dfe0e4 #d0d1d5;
            border-radius: 3px;

            width: 500px;
            padding: 10px;
        }
        .hrfp-meta-wrapper {
        }
        .hrfp .hrfp-avatar {
            float: left;
            margin-right: 5px;
            width: 40px; height: 40px;
        }
        .hrfp .hrfp-avatar img {
            width: 100%;
        }
        .hrfp .hrfp-meta {
        }
        .hrfp .hrfp-meta .hrfp-meta-title {
            color: #365899;
            font-size: 14px;
            line-height: 1.38;
            font-weight: bold;
        }
        .hrfp .hrfp-meta .hrfp-meta-content {
            color: #90949c;
            font-size: 12px;
        }
        .hrfp .hrfp-icon {
            float: right;
        }
        .hrfp-color {
            color: #4267b2;
        }
        .hrfp-box {
            border: 1px solid;
            border-color: #e9ebee #dadada #ccc;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.15) inset, 0 1px 4px rgba(0, 0, 0, 0.1);
        }
        .hrfp-box-image {
            height: 250px;
            background-size: cover;
            background-repeat: no-repeat;
        }
        .hrfp-box-wrapper {
            padding: 10px;
        }
        .hrfp-box-title {
            font-family: Georgia, serif;
            font-size: 18px;
            font-weight: 500;
            line-height: 22px;
            margin-bottom: 5px;
            max-height: 110px;
            overflow: hidden;
            word-wrap: break-word;
        }
        .hrfp-box-content {
            line-height: 16px;
            max-height: 80px;
            overflow: hidden;
            font-size: 12px;
        }
        .trafficbtn{
            -webkit-border-radius: 60;
            -moz-border-radius: 60;
            border-radius: 60px;
            font-family: Arial;
            color: #000000;
            font-size: 30px;
            background: #34d955;
            padding: 10px 20px 10px 20px;
            border: solid #000000 2px;
            text-decoration: none;
        }
        .trafficbtn:hover {
              background: #3cb0fd;
              background-image: -webkit-linear-gradient(top, #3cb0fd, #3498db);
              background-image: -moz-linear-gradient(top, #3cb0fd, #3498db);
              background-image: -ms-linear-gradient(top, #3cb0fd, #3498db);
              background-image: -o-linear-gradient(top, #3cb0fd, #3498db);
              background-image: linear-gradient(to bottom, #3cb0fd, #3498db);
              text-decoration: none;
              cursor: pointer;
        }
    </style>

    <h3>Customize Your Facebook Post</h3>
    <p><label>Custom Message</label><input name="fbmessage" class="form-control" value="<?php echo $postMessage; ?>"></p>
    <p><label>Custom Title</label><input name="fbtitle" class="form-control" value="<?php echo $postTitle; ?>"></p>
    <p><label>Custom Image Link </label><input name="fbimage" class="form-control" value="<?php echo $postImage; ?>"></p>
    <p><label>Custom Description </label><textarea name="fbdescription" class="form-control" cols='80' rows='5' style='width: 100%;'><?php echo $postDescription; ?></textarea></p>

    <small>Leave empty for default value. Title is Title of Post, Image is Featured Image or first image of the post by default</small>

    <p style="text-align: right; margin-bottom: 5px;"></p>

    <hr />
    <h3>Check The Preview Below. </h3>

    <div class="hrfp">
        <div class="hrfp-meta-wrapper">
            <div class="hrfp-avatar">
                <img src="<?php echo plugin_dir_url(__FILE__) . "empty-avatar.png" ?>" />
            </div>
            <div class="hrfp-icon">
                <img src="<?php echo plugin_dir_url(__FILE__) . "dropdown.png" ?>" />
            </div>
            <div class="hrfp-meta">
                <div class="hrfp-meta-title">Page Name</div>
                <div class="hrfp-meta-content">Published by <span class="hrfp-color">appname</span> [?] - 1 min</div>
            </div>
        </div>
        <div class="hrfp-content">
            <p><?php echo $postMessage; ?></p>

            <div class="hrfp-box">
                <?php if ( ! empty($postImage) ) { ?>
                <div class="hrfp-box-image" style="background-image: url('<?php echo $postImage; ?>')"></div>
                <?php } ?>
                <div class="hrfp-box-wrapper">
                    <div class="hrfp-box-title">
                        <?php echo $postTitle; ?>
                    </div>
                    <div class="hrfp-box-content">
                        <?php echo $postDescription; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
