<?php
    global $post;

    $postId = $post->ID;
    $postTitle = get_post_meta($postId, "trf_twitter_title", true);
    if ( empty($postTitle) ) $postTitle = $post->post_title;

    $postImage = get_post_meta($postId, "trf_twitter_image", true);
    $postDescription =  get_post_meta($postId, "trf_twitter_description", true);

    if ( empty($postDescription) ) {}

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

    update_post_meta($postId, 'trf_twitter_title', $postTitle );
    update_post_meta($postId, 'trf_twitter_image', $postImage );
    update_post_meta($postId, 'trf_twitter_description', $postDescription );
?>
<div class="twitter-previewer">
    <style>
        .twitter-previewer label {
            display: block;
        }
        .twitter-previewer input.form-control {
            width: 100%;
        }
        .hrtw {
          border-top: 1px solid #e6ecf0;
          border-bottom: 1px solid #e6ecf0;
          cursor: pointer;
          width: 588px;
          padding: 9px 12px;
          background-color: #f5f8fa;
          position: relative;
          font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
          font-size: 14px;
          line-height: 20px;
        }
        .hrtw .hrtw-avatar {
          float: left;
          margin-right: 12px;
          width: 48px;
          height: 48px;
          border-radius: 50%;
          overflow: hidden;
        }
        .hrtw .hrtw-avatar img {
          width: 100%;
        }
        .hrtw .hrtw-content-wrapper {
          padding-left: 60px;
        }
        .hrtw .hrtw-icon {
          position: absolute;
          right: 15px;
          top: 15px;
        }
        .hrtw .hrtw-action-icons {
          margin-top: 20px;
        }
        .hrtw .hrtw-content-wrapper .hrtw-meta-title .hrtw-user {
          display: inline-block;
          font-weight: bold;
        }
        .hrtw .hrtw-content-wrapper .hrtw-meta-title .hrtw-display-name {
          display: inline-block;
          color: #657786;
        }
        .hrtw .hrtw-content-wrapper .hrtw-meta-title .hrtw-date {
          display: inline-block;
          color: #657786;
        }
        .hrtw .hrtw-content-wrapper .hrtw-meta-title .hrtw-date:hover {
          color: #1DA1F2;
        }
        .hrtw .hrtw-content-wrapper .hrtw-content .hrtw-hashtag {
          color: #1DA1F2;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box {
          border-radius: 4px;
          border: 1px solid #E1E8ED;
          overflow: hidden;
          position: relative;
          margin-top: 12px;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box:hover {
          border-color: rgba(136, 153, 166, 0.5);
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box .hrtw-box-image {
          position: absolute;
          width: 125px;
          height: 125px;
          border-right-width: 1px;
          border-right-style: solid;
          background-size: cover;
          background-position: center;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box .hrtw-prev-content {
          padding: 10px 10px 10px 10px;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box .hrtw-box-image + .hrtw-prev-content {
          padding: 10px 10px 10px 135px;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box .hrtw-prev-content .hrtw-box-title {
          font-weight: bold;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box .hrtw-prev-content .hrtw-box-content {
          min-height: 54px;
          max-height: 54px;
        }
        .hrtw .hrtw-content-wrapper .hrtw-link-box .hrtw-prev-content .hrtw-box-url {
          margin-top: 5px;
          text-transform: lowercase;
          color: #8899A6;
        }
    </style>

    <h3>Customize Your Twitter Post</h3>

    <p><label>Custom Title</label><input name="twtitle" class="form-control" value="<?php echo $postTitle; ?>"></p>
    <p><label>Custom Image Link </label><input name="twimage" class="form-control" value="<?php echo $postImage; ?>"></p>
    <p><label>Custom Description </label><textarea name="twdescription" class="form-control" cols='80' rows='5' style='width: 100%;'><?php echo $postDescription; ?></textarea></p>

    <small>Leave empty for default value. Title is Title of Post, Image is Featured Image or first image of the post by default</small>

    <p style="text-align: right; margin-bottom: 5px;"></p>

    <hr />
    <h3>Check The Preview Below. </h3>

    <div class="hrtw">
        <div class="hrtw-avatar">
            <img src="<?php echo plugin_dir_url(__FILE__) . "twitter.jpg" ?>" />
        </div>
        <div class="hrtw-content-wrapper">
            <div class="hrtw-meta-title">
                <div class="hrtw-user">Twitter Dev</div>
                <div class="hrtw-display-name">@twitter</div>
                <div class="hrtw-date">&middot; 2 Aug</div>
            </div>
            <div class="hrtw-content">
                <div class='hrtw-meta'>
                    <?php echo $postTitle ?>&nbsp;
                    <span class="hrtw-hashtag">#hashtag1</span>&nbsp;
                    <span class="hrtw-hashtag">#hashtag1</span>&nbsp;
                    <span class="hrtw-hashtag">#hashtag1</span>
                </div>
                <div class="hrtw-link-box">
                    <?php if ( ! empty($postImage) ) { ?>
                        <div class="hrtw-box-image" style="background-image: url('<?php echo $postImage; ?>')"></div>
                    <?php } ?>
                    <div class="hrtw-prev-content">
                        <div class="hrtw-box-title">
                            <?php echo $postTitle; ?>
                        </div>
                        <div class="hrtw-box-content">
                            <?php echo $postDescription; ?>
                        </div>
                        <div class="hrtw-box-url">
                            <?php echo $_SERVER['HTTP_HOST'] ?>
                        </div>
                    </div>
                </div>
                <div class="hrtw-action-icons">
                    <img src="<?php echo plugin_dir_url(__FILE__) . "tw-actions.png" ?>" />
                </div>
            </div>
        </div>
        <div class="hrtw-icon">
            <img src="<?php echo plugin_dir_url(__FILE__) . "tw-dropdown.png" ?>" />
        </div>
    </div>
</div>
