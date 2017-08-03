<?php
    global $post;

    $postId = $post->ID;
    $postTitle = get_post_meta($postId, "trf_reddit_title", true);
    if ( empty($postTitle) ) $postTitle = $post->post_title;

    $postImage = get_post_meta($postId, "trf_reddit_image", true);

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

    update_post_meta($postId, 'trf_reddit_title', $postTitle );
    update_post_meta($postId, 'trf_reddit_image', $postImage );
?>
<div class="reddit-previewer">
    <style>
        .reddit-previewer label {
          display: block;
        }
        .reddit-previewer input.form-control {
          width: 100%;
        }
        .hrrd {
          display: block;
          width: 100%;
          font: normal x-small verdana,arial,helvetica,sans-serif;
          background-color: white;
          z-index: 1;
          min-height: 100%;
        }
        .hrrd .rd-trend {
          display: inline-block;
          vertical-align: top;
        }
        .hrrd .rd-avatar {
          display: inline-block;
          padding-left: 0px;
          margin-right: 10px;
        }
        .hrrd .rd-avatar img {
          width: 70px;
          height: auto;
        }
        .hrrd .rd-content {
          display: inline-block;
          vertical-align: top;
        }
        .hrrd .rd-content .title-wrapper .title {
          outline: none;
          margin-right: .4em;
          padding: 0px;
          overflow: hidden;
          unicode-bidi: isolate;
          color: #0000ff;
          font-size: medium;
          font-weight: normal;
          margin-bottom: 1px;
        }
        .hrrd .rd-content .title-wrapper .url {
          color: #888;
          font-size: x-small;
          white-space: nowrap;
          font-weight: normal;
        }
        .hrrd .rd-content .title-wrapper .url span {
          color: #888;
          display: inline-block;
          overflow: hidden;
          white-space: nowrap;
          text-overflow: ellipsis;
          vertical-align: middle;
          max-width: 19em;
        }
        .hrrd .rd-content .meta-wrapper {
          color: #888;
          font-size: x-small;
        }
        .hrrd .rd-content .meta-wrapper .rd-by {
          color: #369;
          text-decoration: none;
          margin-right: 0.5em;
        }
        .hrrd .rd-content .meta-wrapper .subreddit {
          color: #369;
          text-decoration: none;
          margin-bottom: 10px;
        }
        .hrrd .rd-content .links-wrapper span {
          line-height: 18px;
          color: #888;
          font-weight: bold;
          padding: 0 1px;
          text-decoration: none;
        }
        .hrrd .rd-content .links-wrapper span:hover {
          text-decoration: underline;
        }

    </style>

    <h3>Customize Your Reddit Post</h3>

    <p><label>Custom Title</label><input name="rdtitle" class="form-control" value="<?php echo $postTitle; ?>"></p>
    <p><label>Custom Image Link </label><input name="rdimage" class="form-control" value="<?php echo $postImage; ?>"></p>

    <small>Leave empty for default value. Title is Title of Post, Image is Featured Image or first image of the post by default</small>

    <p style="text-align: right; margin-bottom: 5px;"></p>

    <hr />
    <h3>Check The Preview Below. </h3>

    <div class="hrrd">
      <div class="rd-trend">
        <img src='<?php echo plugin_dir_url(__FILE__) . "trending.png" ?>' />
      </div>
      <div class="rd-avatar">
        <img src='<?php echo $postImage ?>' />
      </div>
      <div class="rd-content">
        <div class="title-wrapper">
          <span class="title"><?php echo $postTitle ?></span>
          <span class="url">
            (<span><?php echo $_SERVER['HTTP_HOST'] ?></span>)
          </span>
        </div>
        <div class="meta-wrapper">
          <span>submitted 1 minute ago by</span>
          <span class="rd-by">username</span>
          to
          <span class="subreddit">r/subreddit</span>
        </div>
        <div class="links-wrapper">
          <span class='link'>1 comment</span>
          <span class='link'>share</span>
          <span class='link'>save</span>
          <span class='link'>hide</span>
          <span class='link'>delete</span>
          <span class='link'>nsfw</span>
          <span class='link'>spoiler</span>
        </div>
      </div>
    </div>
</div>
